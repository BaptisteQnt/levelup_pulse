<?php

namespace App\Services;

use App\Models\IotTelemetryAiAnalysis;
use App\Models\IotTelemetryMeasurement;

/**
 * Decides, with deterministic rules only, whether a stored measurement deserves a (billable) AI analysis.
 */
class IotAiAnalysisSelector
{
    /**
     * @return string|null One of IotTelemetryAiAnalysis::TRIGGER_* or null when no analysis is required.
     */
    public function reasonFor(IotTelemetryMeasurement $measurement): ?string
    {
        if ($this->isRateLimited($measurement)) {
            return null;
        }

        $history = IotTelemetryMeasurement::query()
            ->where('iot_device_id', $measurement->iot_device_id)
            ->where('id', '<', $measurement->id)
            ->latest('id')
            ->limit(max(1, (int) config('iot.ai.history_size', 10)))
            ->get(['temperature', 'humidity']);
        $previous = $history->first();

        if ($previous) {
            if ($this->crossesThreshold($measurement, $previous)) {
                return IotTelemetryAiAnalysis::TRIGGER_THRESHOLD;
            }

            if ($this->isSpike($measurement, $previous)) {
                return IotTelemetryAiAnalysis::TRIGGER_SPIKE;
            }

            if ($this->isAnomalous($measurement, $history->avg('temperature'))) {
                return IotTelemetryAiAnalysis::TRIGGER_ANOMALY;
            }
        }

        return $this->periodicAnalysisIsDue($measurement) ? IotTelemetryAiAnalysis::TRIGGER_PERIODIC : null;
    }

    private function isRateLimited(IotTelemetryMeasurement $measurement): bool
    {
        $seconds = (int) config('iot.ai.min_seconds_between_analyses', 60);

        return $seconds > 0 && $this->lastAnalysisCreatedAfter($measurement, now()->subSeconds($seconds));
    }

    private function periodicAnalysisIsDue(IotTelemetryMeasurement $measurement): bool
    {
        $minutes = (int) config('iot.ai.periodic_interval_minutes', 15);

        return $minutes > 0 && ! $this->lastAnalysisCreatedAfter($measurement, now()->subMinutes($minutes));
    }

    private function lastAnalysisCreatedAfter(IotTelemetryMeasurement $measurement, \DateTimeInterface $since): bool
    {
        return IotTelemetryAiAnalysis::query()
            ->where('iot_telemetry_measurement_id', '!=', $measurement->id)
            ->where('created_at', '>=', $since)
            ->whereHas('measurement', fn ($query) => $query->where('iot_device_id', $measurement->iot_device_id))
            ->exists();
    }

    private function isOutsideSafeRange(float $temperature): bool
    {
        return $temperature < (float) config('iot.display.safe_temperature_min', 5)
            || $temperature > (float) config('iot.display.safe_temperature_max', 35);
    }

    private function crossesThreshold(IotTelemetryMeasurement $measurement, IotTelemetryMeasurement $previous): bool
    {
        return (bool) config('iot.ai.trigger_on_threshold_crossing', true)
            && $this->isOutsideSafeRange((float) $measurement->temperature)
            && ! $this->isOutsideSafeRange((float) $previous->temperature);
    }

    private function isSpike(IotTelemetryMeasurement $measurement, IotTelemetryMeasurement $previous): bool
    {
        $temperatureDelta = (float) config('iot.ai.temperature_spike_delta', 5);
        $humidityDelta = (float) config('iot.ai.humidity_spike_delta', 25);

        return ($temperatureDelta > 0 && abs((float) $measurement->temperature - (float) $previous->temperature) >= $temperatureDelta)
            || ($humidityDelta > 0 && abs((float) $measurement->humidity - (float) $previous->humidity) >= $humidityDelta);
    }

    private function isAnomalous(IotTelemetryMeasurement $measurement, int|float|string|null $average): bool
    {
        $deviation = (float) config('iot.ai.anomaly_deviation', 8);

        return $average !== null && $deviation > 0
            && abs((float) $measurement->temperature - (float) $average) >= $deviation;
    }
}
