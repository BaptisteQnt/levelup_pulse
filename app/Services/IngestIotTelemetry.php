<?php

namespace App\Services;

use App\Models\IotDevice;
use App\Models\IotTelemetryMeasurement;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IngestIotTelemetry
{
    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DUPLICATE = 'duplicate';

    public const STATUS_REPLAYED = 'replayed_sequence';

    public const STATUS_CONFLICT = 'sequence_conflict';

    /**
     * @param  array{device_id: string, temperature: int|float|string, humidity: int|float|string, sequence: int, measured_at?: string|null}  $payload
     * @return array{status: string, measurement: IotTelemetryMeasurement|null}
     */
    public function handle(IotDevice $authenticatedDevice, array $payload): array
    {
        return DB::transaction(function () use ($authenticatedDevice, $payload): array {
            $device = IotDevice::query()
                ->whereKey($authenticatedDevice->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $existing = $device->telemetryMeasurements()
                ->where('sequence', $payload['sequence'])
                ->first();

            if ($existing) {
                $status = $this->sameMeasurement($existing, $payload)
                    ? self::STATUS_DUPLICATE
                    : self::STATUS_CONFLICT;

                if ($status === self::STATUS_DUPLICATE) {
                    $device->update(['last_seen_at' => now()]);
                }

                Log::info('IoT telemetry sequence already exists.', [
                    'device_id' => $device->device_id,
                    'sequence' => $payload['sequence'],
                    'status' => $status,
                ]);

                return ['status' => $status, 'measurement' => $existing];
            }

            if ($device->last_sequence !== null && $payload['sequence'] <= $device->last_sequence) {
                Log::warning('IoT telemetry replay rejected.', [
                    'device_id' => $device->device_id,
                    'sequence' => $payload['sequence'],
                    'last_sequence' => $device->last_sequence,
                ]);

                return ['status' => self::STATUS_REPLAYED, 'measurement' => null];
            }

            $receivedAt = now();
            $measuredAt = filled($payload['measured_at'] ?? null)
                ? CarbonImmutable::parse($payload['measured_at'])->utc()
                : null;

            $measurement = $device->telemetryMeasurements()->create([
                'temperature' => $payload['temperature'],
                'humidity' => $payload['humidity'],
                'sequence' => $payload['sequence'],
                'measured_at' => $measuredAt,
                'received_at' => $receivedAt,
            ]);

            $device->update([
                'last_sequence' => $payload['sequence'],
                'last_seen_at' => $receivedAt,
            ]);

            Log::info('IoT telemetry accepted.', [
                'device_id' => $device->device_id,
                'sequence' => $measurement->sequence,
                'measurement_id' => $measurement->id,
            ]);

            return ['status' => self::STATUS_ACCEPTED, 'measurement' => $measurement];
        }, 3);
    }

    /**
     * @param  array{temperature: int|float|string, humidity: int|float|string, measured_at?: string|null}  $payload
     */
    private function sameMeasurement(IotTelemetryMeasurement $measurement, array $payload): bool
    {
        $measuredAt = filled($payload['measured_at'] ?? null)
            ? CarbonImmutable::parse($payload['measured_at'])->utc()
            : null;

        $sameTimestamp = $measurement->measured_at === null
            ? $measuredAt === null
            : $measuredAt !== null && $measurement->measured_at->utc()->equalTo($measuredAt);

        return $measurement->temperature === number_format((float) $payload['temperature'], 2, '.', '')
            && $measurement->humidity === number_format((float) $payload['humidity'], 2, '.', '')
            && $sameTimestamp;
    }
}
