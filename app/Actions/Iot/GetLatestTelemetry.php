<?php

namespace App\Actions\Iot;

use App\Models\IotTelemetryMeasurement;
use App\Models\User;

class GetLatestTelemetry
{
    /**
     * @return array{
     *     device_id: string,
     *     temperature: float,
     *     humidity: float,
     *     measured_at: string|null,
     *     received_at: string|null,
     *     status: 'safe'|'danger',
     *     message: string,
     *     safe_temperature_min: float,
     *     safe_temperature_max: float
     * }|null
     */
    public function handle(User $user): ?array
    {
        $measurement = IotTelemetryMeasurement::query()
            ->select('iot_telemetry_measurements.*')
            ->with('device:id,device_id')
            ->whereHas('device', fn ($query) => $query->where('user_id', $user->id))
            ->latest('received_at')
            ->latest('id')
            ->first();

        if (! $measurement) {
            return null;
        }

        $temperature = (float) $measurement->temperature;
        $safeMin = (float) config('iot.display.safe_temperature_min', 5);
        $safeMax = (float) config('iot.display.safe_temperature_max', 35);
        $isDangerous = $temperature < $safeMin || $temperature > $safeMax;

        return [
            'device_id' => $measurement->device->device_id,
            'temperature' => $temperature,
            'humidity' => (float) $measurement->humidity,
            'measured_at' => $measurement->measured_at?->toIso8601String(),
            'received_at' => $measurement->received_at?->toIso8601String(),
            'status' => $isDangerous ? 'danger' : 'safe',
            'message' => $isDangerous
                ? 'Température dangereuse : vérifiez immédiatement l’environnement du capteur.'
                : 'Température normale : aucune conséquence détectée.',
            'safe_temperature_min' => $safeMin,
            'safe_temperature_max' => $safeMax,
        ];
    }
}
