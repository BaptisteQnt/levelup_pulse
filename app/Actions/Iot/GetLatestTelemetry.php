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
     *     device_last_seen_at: string|null,
     *     status: 'safe'|'danger',
     *     message: string,
     *     safe_temperature_min: float,
     *     safe_temperature_max: float,
     *     ai_enabled: bool,
     *     ai_analysis: array<string, mixed>|null
     * }|null
     */
    public function handle(User $user): ?array
    {
        $measurement = IotTelemetryMeasurement::query()
            ->select('iot_telemetry_measurements.*')
            ->with(['device:id,device_id,last_seen_at', 'aiAnalysis'])
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
            'device_last_seen_at' => $measurement->device->last_seen_at?->toIso8601String(),
            'status' => $isDangerous ? 'danger' : 'safe',
            'message' => $isDangerous
                ? 'Température dangereuse : vérifiez immédiatement l’environnement du capteur.'
                : 'Température normale : aucune conséquence détectée.',
            'safe_temperature_min' => $safeMin,
            'safe_temperature_max' => $safeMax,
            'ai_enabled' => (bool) config('iot.ai.enabled'),
            'ai_analysis' => $this->presentAiAnalysis($measurement->aiAnalysis),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function presentAiAnalysis(mixed $analysis): ?array
    {
        if (! $analysis) {
            return null;
        }

        return [
            'status' => $analysis->status,
            'reliability' => $analysis->reliability,
            'approved_for_use' => $analysis->approved_for_use,
            'risk_level' => $analysis->risk_level,
            'risk_summary' => $analysis->risk_summary,
            'reliability_reason' => $analysis->reliability_reason,
            'recommendations' => $analysis->recommendations ?? [],
            'disclaimer' => $analysis->disclaimer,
            'analyzed_at' => $analysis->analyzed_at?->toIso8601String(),
        ];
    }
}
