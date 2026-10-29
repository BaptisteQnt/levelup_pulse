<?php

use App\Contracts\IotTelemetryAiProvider;
use App\Jobs\AnalyzeIotTelemetry;
use App\Models\IotDevice;
use App\Models\IotTelemetryAiAnalysis;
use App\Models\IotTelemetryMeasurement;
use App\Models\User;
use App\Services\IotAiAnalysisSelector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

const IOT_AI_TEST_TOKEN = 'lup_iot_fake_test_token_that_is_never_used_outside_tests';

function iotAiProvider(array $result = [], ?Throwable $failure = null): IotTelemetryAiProvider
{
    return new class($result, $failure) implements IotTelemetryAiProvider
    {
        public int $calls = 0;

        public function __construct(private array $result, private ?Throwable $failure) {}

        public function analyze(array $currentMeasurement, array $recentMeasurements, int $userId): array
        {
            $this->calls++;

            if ($this->failure) {
                throw $this->failure;
            }

            return $this->result + [
                'reliability' => 'reliable',
                'risk_level' => 'low',
                'risk_summary' => 'Résumé.',
                'reliability_reason' => 'Cohérent.',
                'recommendations' => ['Aérer.'],
                'disclaimer' => 'Analyse informative.',
            ];
        }

        public function modelName(): string
        {
            return 'fake-iot-model';
        }
    };
}

function iotAiDevice(?User $user = null): IotDevice
{
    return IotDevice::factory()->for($user ?? User::factory()->create())->create([
        'device_id' => 'LUP-ESP32-001',
        'token_hash' => hash('sha256', IOT_AI_TEST_TOKEN),
    ]);
}

function iotAiMeasurement(IotDevice $device, int $sequence, float $temperature = 24.0, float $humidity = 50.0, ?DateTimeInterface $at = null): IotTelemetryMeasurement
{
    return IotTelemetryMeasurement::factory()->for($device, 'device')->create([
        'sequence' => $sequence,
        'temperature' => $temperature,
        'humidity' => $humidity,
        'received_at' => $at ?? now(),
    ]);
}

function iotAiAnalysis(IotTelemetryMeasurement $measurement, string $status, ?DateTimeInterface $createdAt = null): IotTelemetryAiAnalysis
{
    return IotTelemetryAiAnalysis::unguarded(fn () => $measurement->aiAnalysis()->create(array_filter([
        'status' => $status,
        'created_at' => $createdAt,
    ])));
}

function iotAiPost(array $overrides = [])
{
    return test()->withToken(IOT_AI_TEST_TOKEN)->postJson('/api/internal/iot/v1/telemetry', array_merge([
        'device_id' => 'LUP-ESP32-001',
        'temperature' => 24.0,
        'humidity' => 50.0,
        'sequence' => 1,
    ], $overrides));
}

beforeEach(function () {
    config([
        'iot.ai.enabled' => true,
        'iot.ai.minimum_history_size' => 3,
        'iot.ai.periodic_interval_minutes' => 15,
        'iot.ai.min_seconds_between_analyses' => 60,
    ]);
});

it('stores a valid measurement and keeps it when OpenAI is unavailable', function () {
    $device = iotAiDevice();
    Queue::fake();

    iotAiPost()->assertCreated();

    $measurement = $device->telemetryMeasurements()->firstOrFail();
    $failing = iotAiProvider(failure: new RuntimeException('OpenAI down'));

    expect(fn () => (new AnalyzeIotTelemetry($measurement->id))->handle($failing))->toThrow(RuntimeException::class);
    (new AnalyzeIotTelemetry($measurement->id))->failed(new RuntimeException('OpenAI down'));

    expect($measurement->fresh())->not->toBeNull()
        ->and($measurement->aiAnalysis->fresh()->status)->toBe(IotTelemetryAiAnalysis::STATUS_FAILED)
        ->and($measurement->aiAnalysis->fresh()->approved_for_use)->toBeFalse();
});

it('shows a measurement as pending analysis before the AI has answered', function () {
    $device = iotAiDevice();
    Queue::fake();

    iotAiPost()->assertCreated();

    $this->actingAs($device->user)
        ->getJson(route('iot.telemetry.latest'))
        ->assertOk()
        ->assertJsonPath('data.ai_analysis.status', 'pending')
        ->assertJsonPath('data.ai_analysis.trigger_reason', 'periodic')
        ->assertJsonPath('data.ai_analysis.approved_for_use', null);
});

it('keeps the deterministic threshold alert when the AI calls the measurement suspicious', function () {
    $device = iotAiDevice();
    $measurement = iotAiMeasurement($device, 1, 41.0);
    $measurement->aiAnalysis()->create(['status' => 'pending']);

    (new AnalyzeIotTelemetry($measurement->id))->handle(iotAiProvider(['reliability' => 'suspicious', 'risk_level' => 'low']));

    $this->actingAs($device->user)
        ->getJson(route('iot.telemetry.latest'))
        ->assertOk()
        ->assertJsonPath('data.status', 'danger')
        ->assertJsonPath('data.temperature', 41)
        ->assertJsonPath('data.ai_analysis.reliability', 'suspicious')
        ->assertJsonPath('data.ai_analysis.approved_for_use', false);
});

it('does not approve unknown or suspicious verdicts', function (string $verdict) {
    $device = iotAiDevice();
    foreach (range(1, 3) as $sequence) {
        iotAiMeasurement($device, $sequence);
    }
    $measurement = iotAiMeasurement($device, 4);
    $measurement->aiAnalysis()->create(['status' => 'pending']);

    (new AnalyzeIotTelemetry($measurement->id))->handle(iotAiProvider(['reliability' => $verdict]));

    $analysis = $measurement->aiAnalysis()->firstOrFail();

    expect($analysis->status)->toBe('completed')
        ->and($analysis->approved_for_use)->toBeFalse();
})->with(['unknown', 'suspicious']);

it('approves a reliable measurement only with enough history', function () {
    $device = iotAiDevice();
    foreach (range(1, 3) as $sequence) {
        iotAiMeasurement($device, $sequence);
    }
    $measurement = iotAiMeasurement($device, 4);
    $measurement->aiAnalysis()->create(['status' => 'pending']);

    (new AnalyzeIotTelemetry($measurement->id))->handle(iotAiProvider());

    $analysis = $measurement->aiAnalysis()->firstOrFail();

    expect($analysis->status)->toBe('completed')
        ->and($analysis->reliability)->toBe('reliable')
        ->and($analysis->approved_for_use)->toBeTrue();
});

it('never approves while an analysis is pending or processing', function () {
    $device = iotAiDevice();
    $measurement = iotAiMeasurement($device, 1);
    $analysis = $measurement->aiAnalysis()->create(['status' => 'processing']);

    $provider = iotAiProvider();
    (new AnalyzeIotTelemetry($measurement->id))->handle($provider);

    expect($provider->calls)->toBe(0)
        ->and($analysis->fresh()->approved_for_use)->not->toBeTrue();
});

it('distinguishes a measurement not selected for analysis from a pending one', function () {
    $device = iotAiDevice();
    iotAiMeasurement($device, 1, 24.0, 50.0, now()->subSeconds(20))->aiAnalysis()->create(['status' => 'completed']);
    Queue::fake();

    iotAiPost(['sequence' => 2, 'temperature' => 24.2])->assertCreated();

    $this->actingAs($device->user)
        ->getJson(route('iot.telemetry.latest'))
        ->assertOk()
        ->assertJsonPath('data.ai_analysis.status', 'not_required');

    Queue::assertNothingPushed();
});

it('reports no analysis state when the AI is disabled', function () {
    config(['iot.ai.enabled' => false]);
    $device = iotAiDevice();
    iotAiMeasurement($device, 1);

    $this->actingAs($device->user)
        ->getJson(route('iot.telemetry.latest'))
        ->assertOk()
        ->assertJsonPath('data.ai_enabled', false)
        ->assertJsonPath('data.ai_analysis', null);

    Queue::fake();
    iotAiPost(['sequence' => 2])->assertCreated();
    Queue::assertNothingPushed();
});

describe('analysis triggers', function () {
    it('triggers on a threshold crossing', function () {
        $device = iotAiDevice();
        iotAiAnalysis(iotAiMeasurement($device, 1, 30.0), 'completed', now()->subMinutes(5));
        iotAiMeasurement($device, 2, 31.0);
        $crossing = iotAiMeasurement($device, 3, 36.0);

        expect(app(IotAiAnalysisSelector::class)->reasonFor($crossing))->toBe('threshold');
    });

    it('triggers on a sudden temperature variation', function () {
        $device = iotAiDevice();
        iotAiAnalysis(iotAiMeasurement($device, 1, 22.0), 'completed', now()->subMinutes(5));
        $spike = iotAiMeasurement($device, 2, 28.0);

        expect(app(IotAiAnalysisSelector::class)->reasonFor($spike))->toBe('spike');
    });

    it('triggers on a value far from the recent average', function () {
        config(['iot.ai.temperature_spike_delta' => 0]);
        $device = iotAiDevice();
        iotAiAnalysis(iotAiMeasurement($device, 1, 20.0), 'completed', now()->subMinutes(5));
        iotAiMeasurement($device, 2, 20.0);
        iotAiMeasurement($device, 3, 20.0);
        $outlier = iotAiMeasurement($device, 4, 29.0);

        expect(app(IotAiAnalysisSelector::class)->reasonFor($outlier))->toBe('anomaly');
    });

    it('triggers periodically once the interval has elapsed', function () {
        $device = iotAiDevice();
        iotAiAnalysis(iotAiMeasurement($device, 1), 'completed', now()->subMinutes(20));
        $next = iotAiMeasurement($device, 2);

        expect(app(IotAiAnalysisSelector::class)->reasonFor($next))->toBe('periodic');
    });

    it('does not trigger for a stable measurement inside the periodic interval', function () {
        $device = iotAiDevice();
        iotAiAnalysis(iotAiMeasurement($device, 1), 'completed', now()->subMinutes(5));
        $next = iotAiMeasurement($device, 2);

        expect(app(IotAiAnalysisSelector::class)->reasonFor($next))->toBeNull();
    });

    it('rate limits analyses per device even on a trigger', function () {
        $device = iotAiDevice();
        iotAiMeasurement($device, 1, 22.0)->aiAnalysis()->create(['status' => 'pending']);
        $spike = iotAiMeasurement($device, 2, 30.0);

        expect(app(IotAiAnalysisSelector::class)->reasonFor($spike))->toBeNull();

        $other = iotAiMeasurement(IotDevice::factory()->create(), 1, 22.0);

        expect(app(IotAiAnalysisSelector::class)->reasonFor($other))->toBe('periodic');
    });
});

it('does not let a finished or concurrent analysis be overwritten', function () {
    $device = iotAiDevice();
    $measurement = iotAiMeasurement($device, 1);
    $measurement->aiAnalysis()->create(['status' => 'pending']);

    (new AnalyzeIotTelemetry($measurement->id))->handle(iotAiProvider(['reliability' => 'suspicious']));
    $late = iotAiProvider(['reliability' => 'reliable']);
    (new AnalyzeIotTelemetry($measurement->id))->handle($late);

    expect($late->calls)->toBe(0)
        ->and($measurement->aiAnalysis()->firstOrFail()->reliability)->toBe('suspicious');
});

it('releases the claim after a transient error so the queue can retry', function () {
    $device = iotAiDevice();
    $measurement = iotAiMeasurement($device, 1);
    $measurement->aiAnalysis()->create(['status' => 'pending']);

    expect(fn () => (new AnalyzeIotTelemetry($measurement->id))->handle(iotAiProvider(failure: new RuntimeException('timeout'))))
        ->toThrow(RuntimeException::class);
    expect($measurement->aiAnalysis()->firstOrFail()->status)->toBe('pending');

    (new AnalyzeIotTelemetry($measurement->id))->handle(iotAiProvider());
    expect($measurement->aiAnalysis()->firstOrFail()->status)->toBe('completed');
});

it('does not expose another user measurements or AI verdicts', function () {
    $device = IotDevice::factory()->for(User::factory()->create())->create();
    $measurement = iotAiMeasurement($device, 1, 40.0);
    $measurement->aiAnalysis()->create([
        'status' => 'completed',
        'reliability' => 'suspicious',
        'risk_summary' => 'Résumé privé du propriétaire.',
        'analyzed_at' => now(),
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('iot.telemetry.latest'))
        ->assertOk()
        ->assertJsonPath('data', null);
});

it('exposes the last completed verdict when the latest measurement was not analysed', function () {
    $device = iotAiDevice();
    iotAiMeasurement($device, 1, 24.0, 50.0, now()->subMinutes(2))->aiAnalysis()->create([
        'status' => 'completed',
        'reliability' => 'reliable',
        'risk_summary' => 'Conditions normales.',
        'analyzed_at' => now()->subMinutes(2),
    ]);
    iotAiMeasurement($device, 2);

    $this->actingAs($device->user)
        ->getJson(route('iot.telemetry.latest'))
        ->assertOk()
        ->assertJsonPath('data.ai_analysis.status', 'not_required')
        ->assertJsonPath('data.last_ai_verdict.reliability', 'reliable');
});
