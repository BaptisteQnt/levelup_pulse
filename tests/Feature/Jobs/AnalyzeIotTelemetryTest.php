<?php

use App\Contracts\IotTelemetryAiProvider;
use App\Jobs\AnalyzeIotTelemetry;
use App\Models\IotDevice;
use App\Models\IotTelemetryAiAnalysis;
use App\Models\IotTelemetryMeasurement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('analyzes risk and reliability without replacing the raw measurement', function () {
    $user = User::factory()->create();
    $device = IotDevice::factory()->for($user)->create();

    foreach ([24.0, 24.2, 24.4] as $index => $temperature) {
        IotTelemetryMeasurement::factory()->for($device, 'device')->create([
            'sequence' => $index + 1,
            'temperature' => $temperature,
            'received_at' => now()->subSeconds(30 - ($index * 10)),
        ]);
    }

    $measurement = IotTelemetryMeasurement::factory()->for($device, 'device')->create([
        'sequence' => 4,
        'temperature' => 24.6,
        'humidity' => 48,
        'received_at' => now(),
    ]);
    $measurement->aiAnalysis()->create(['status' => IotTelemetryAiAnalysis::STATUS_PENDING]);

    $provider = new class implements IotTelemetryAiProvider
    {
        public array $current = [];

        public array $history = [];

        public int $userId = 0;

        public function analyze(array $currentMeasurement, array $recentMeasurements, int $userId): array
        {
            $this->current = $currentMeasurement;
            $this->history = $recentMeasurements;
            $this->userId = $userId;

            return [
                'reliability' => 'reliable',
                'risk_level' => 'low',
                'risk_summary' => 'Aucun risque thermique notable dans les données reçues.',
                'reliability_reason' => 'Le relevé suit la tendance récente.',
                'recommendations' => ['Conserver une ventilation normale.'],
                'disclaimer' => 'Analyse informative.',
            ];
        }

        public function modelName(): string
        {
            return 'fake-iot-model';
        }
    };

    (new AnalyzeIotTelemetry($measurement->id))->handle($provider);

    $analysis = $measurement->aiAnalysis()->firstOrFail();

    expect($measurement->fresh()->temperature)->toBe('24.60')
        ->and($provider->current['temperature_celsius'])->toBe(24.6)
        ->and($provider->history)->toHaveCount(3)
        ->and($provider->userId)->toBe($user->id)
        ->and($analysis->status)->toBe(IotTelemetryAiAnalysis::STATUS_COMPLETED)
        ->and($analysis->approved_for_use)->toBeTrue()
        ->and($analysis->risk_level)->toBe(IotTelemetryAiAnalysis::RISK_LOW)
        ->and($analysis->model)->toBe('fake-iot-model')
        ->and($analysis->analyzed_at)->not->toBeNull();
});

it('marks an analysis as failed after the queue exhausts its retries', function () {
    $measurement = IotTelemetryMeasurement::factory()->create();
    $analysis = $measurement->aiAnalysis()->create([
        'status' => IotTelemetryAiAnalysis::STATUS_PROCESSING,
    ]);

    (new AnalyzeIotTelemetry($measurement->id))->failed(new RuntimeException('Provider unavailable'));

    expect($analysis->fresh()->status)->toBe(IotTelemetryAiAnalysis::STATUS_FAILED)
        ->and($analysis->fresh()->approved_for_use)->toBeFalse()
        ->and($analysis->fresh()->error_code)->toBe('ai_analysis_failed');
});

it('does not approve an isolated measurement even when the provider calls it reliable', function () {
    config(['iot.ai.minimum_history_size' => 3]);
    $measurement = IotTelemetryMeasurement::factory()->create();
    $measurement->aiAnalysis()->create(['status' => IotTelemetryAiAnalysis::STATUS_PENDING]);

    $provider = new class implements IotTelemetryAiProvider
    {
        public function analyze(array $currentMeasurement, array $recentMeasurements, int $userId): array
        {
            return [
                'reliability' => 'reliable',
                'risk_level' => 'low',
                'risk_summary' => 'Valeur dans la plage habituelle.',
                'reliability_reason' => 'Valeur physiquement plausible.',
                'recommendations' => [],
                'disclaimer' => 'Analyse informative.',
            ];
        }

        public function modelName(): string
        {
            return 'fake-iot-model';
        }
    };

    (new AnalyzeIotTelemetry($measurement->id))->handle($provider);

    $analysis = $measurement->aiAnalysis()->firstOrFail();

    expect($analysis->reliability)->toBe(IotTelemetryAiAnalysis::RELIABILITY_UNKNOWN)
        ->and($analysis->approved_for_use)->toBeFalse()
        ->and($analysis->reliability_reason)->toContain('Historique insuffisant');
});
