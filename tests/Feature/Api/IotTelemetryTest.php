<?php

namespace Tests\Feature\Api;

use App\Models\IotDevice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class IotTelemetryTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/internal/iot/v1/telemetry';

    private const TOKEN = 'lup_iot_fake_test_token_that_is_never_used_outside_tests';

    public function test_valid_telemetry_is_stored_with_server_received_time(): void
    {
        $device = $this->createDevice();

        $response = $this->withToken(self::TOKEN)->postJson(self::ENDPOINT, $this->payload());

        $response
            ->assertCreated()
            ->assertHeader('Cache-Control')
            ->assertJsonPath('status', 'accepted')
            ->assertJsonPath('data.device_id', $device->device_id)
            ->assertJsonPath('data.sequence', 1)
            ->assertJsonPath('data.measured_at', '2026-10-22T14:30:00+00:00')
            ->assertJsonStructure(['data' => ['received_at']]);

        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));

        $this->assertDatabaseHas('iot_telemetry_measurements', [
            'iot_device_id' => $device->id,
            'temperature' => '24.00',
            'humidity' => '50.00',
            'sequence' => 1,
        ]);
    }

    public function test_measured_at_is_optional(): void
    {
        $device = $this->createDevice();
        $payload = $this->payload();
        unset($payload['measured_at']);

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $payload)
            ->assertCreated()
            ->assertJsonPath('data.measured_at', null);

        $this->assertDatabaseHas('iot_telemetry_measurements', [
            'iot_device_id' => $device->id,
            'measured_at' => null,
        ]);
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->createDevice();

        $this->postJson(self::ENDPOINT, $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath('code', 'invalid_device_credentials');

        $this->assertDatabaseCount('iot_telemetry_measurements', 0);
    }

    public function test_invalid_token_is_rejected_without_leaking_it(): void
    {
        $this->createDevice();
        $presentedToken = 'lup_iot_secret_that_must_not_leak';
        Log::spy();

        $response = $this->withToken($presentedToken)->postJson(self::ENDPOINT, $this->payload());

        $response->assertUnauthorized()->assertDontSee($presentedToken);
        Log::shouldHaveReceived('warning')->once()->withArgs(
            fn (string $message, array $context): bool => ! str_contains(
                $message.json_encode($context, JSON_THROW_ON_ERROR),
                $presentedToken,
            ),
        );
        $this->assertDatabaseCount('iot_telemetry_measurements', 0);
    }

    public function test_unknown_device_is_rejected(): void
    {
        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath('code', 'invalid_device_credentials');
    }

    public function test_inactive_device_is_rejected(): void
    {
        $this->createDevice(['is_active' => false]);

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload())
            ->assertForbidden()
            ->assertJsonPath('code', 'device_unavailable');
    }

    public function test_revoked_device_is_rejected(): void
    {
        $this->createDevice(['revoked_at' => now()]);

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload())
            ->assertForbidden()
            ->assertJsonPath('code', 'device_unavailable');
    }

    public function test_token_cannot_be_used_for_another_device_id(): void
    {
        $this->createDevice();
        IotDevice::factory()->create(['device_id' => 'LUP-ESP32-002']);
        $payload = $this->payload(['device_id' => 'LUP-ESP32-002']);

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $payload)
            ->assertUnauthorized();

        $this->assertDatabaseCount('iot_telemetry_measurements', 0);
    }

    public function test_malformed_json_is_rejected_without_writing_data(): void
    {
        $this->createDevice();

        $response = $this->call(
            'POST',
            self::ENDPOINT,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
            ],
            '{"device_id":"LUP-ESP32-001",',
        );

        $response->assertBadRequest()->assertJsonPath('code', 'invalid_json');
        $this->assertDatabaseCount('iot_telemetry_measurements', 0);
    }

    public function test_invalid_payload_does_not_write_partial_data(): void
    {
        $this->createDevice();

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['humidity' => 101]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('humidity');

        $this->assertDatabaseCount('iot_telemetry_measurements', 0);
        $this->assertNull(IotDevice::firstOrFail()->last_seen_at);
    }

    public function test_temperature_outside_sensor_limits_is_rejected(): void
    {
        $this->createDevice();

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['temperature' => -40.01]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('temperature');
    }

    public function test_humidity_outside_sensor_limits_is_rejected(): void
    {
        $this->createDevice();

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['humidity' => -0.01]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('humidity');
    }

    public function test_exact_duplicate_is_idempotent(): void
    {
        $device = $this->createDevice();

        $first = $this->withToken(self::TOKEN)->postJson(self::ENDPOINT, $this->payload());
        $duplicate = $this->withToken(self::TOKEN)->postJson(self::ENDPOINT, $this->payload());

        $first->assertCreated();
        $duplicate
            ->assertOk()
            ->assertJsonPath('status', 'duplicate')
            ->assertJsonPath('data.id', $first->json('data.id'));
        $this->assertSame(1, $device->telemetryMeasurements()->count());
    }

    public function test_same_sequence_with_different_data_is_rejected(): void
    {
        $this->createDevice();

        $this->withToken(self::TOKEN)->postJson(self::ENDPOINT, $this->payload())->assertCreated();
        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['temperature' => 25]))
            ->assertConflict()
            ->assertJsonPath('code', 'sequence_conflict');

        $this->assertDatabaseCount('iot_telemetry_measurements', 1);
    }

    public function test_old_unseen_sequence_is_rejected_as_replay(): void
    {
        $this->createDevice();

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['sequence' => 10]))
            ->assertCreated();

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['sequence' => 9]))
            ->assertConflict()
            ->assertJsonPath('code', 'replayed_sequence');

        $this->assertDatabaseMissing('iot_telemetry_measurements', ['sequence' => 9]);
    }

    public function test_last_activity_and_sequence_are_updated(): void
    {
        Carbon::setTestNow('2026-10-22 16:45:00');
        $device = $this->createDevice();

        try {
            $this->withToken(self::TOKEN)
                ->postJson(self::ENDPOINT, $this->payload(['sequence' => 42]))
                ->assertCreated();

            $device->refresh();
            $this->assertSame(42, $device->last_sequence);
            $this->assertTrue($device->last_seen_at->equalTo(now()));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_rate_limit_is_applied_per_authenticated_device(): void
    {
        config([
            'iot.telemetry.rate_limit_per_minute' => 2,
            'iot.telemetry.ip_rate_limit_per_minute' => 100,
        ]);
        $device = $this->createDevice();
        RateLimiter::clear('iot-device:'.$device->id);
        RateLimiter::clear('iot-ip:127.0.0.1');

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['sequence' => 1]))
            ->assertCreated();
        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['sequence' => 2]))
            ->assertCreated();
        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload(['sequence' => 3]))
            ->assertTooManyRequests();

        $this->assertDatabaseCount('iot_telemetry_measurements', 2);
    }

    public function test_ip_rate_limit_also_protects_failed_authentication(): void
    {
        config([
            'iot.telemetry.rate_limit_per_minute' => 100,
            'iot.telemetry.ip_rate_limit_per_minute' => 2,
        ]);
        $this->createDevice();
        RateLimiter::clear('iot-ip:127.0.0.1');

        $this->withToken('invalid-token-1')
            ->postJson(self::ENDPOINT, $this->payload())
            ->assertUnauthorized();
        $this->withToken('invalid-token-2')
            ->postJson(self::ENDPOINT, $this->payload())
            ->assertUnauthorized();
        $this->withToken('invalid-token-3')
            ->postJson(self::ENDPOINT, $this->payload())
            ->assertTooManyRequests();

        $this->assertDatabaseCount('iot_telemetry_measurements', 0);
    }

    public function test_http_is_rejected_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->createDevice();

        $this->withToken(self::TOKEN)
            ->postJson(self::ENDPOINT, $this->payload())
            ->assertStatus(426)
            ->assertJsonPath('code', 'https_required');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createDevice(array $attributes = []): IotDevice
    {
        return IotDevice::factory()->create(array_merge([
            'user_id' => User::factory(),
            'device_id' => 'LUP-ESP32-001',
            'token_hash' => hash('sha256', self::TOKEN),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'device_id' => 'LUP-ESP32-001',
            'temperature' => 24.00,
            'humidity' => 50.00,
            'sequence' => 1,
            'measured_at' => '2026-10-22T14:30:00Z',
        ], $overrides);
    }
}
