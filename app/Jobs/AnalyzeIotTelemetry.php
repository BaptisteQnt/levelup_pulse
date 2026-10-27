<?php

namespace App\Jobs;

use App\Contracts\IotTelemetryAiProvider;
use App\Models\IotTelemetryAiAnalysis;
use App\Models\IotTelemetryMeasurement;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class AnalyzeIotTelemetry implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public bool $failOnTimeout = true;

    public int $uniqueFor = 600;

    public function __construct(public int $measurementId) {}

    public function uniqueId(): string
    {
        return (string) $this->measurementId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function handle(IotTelemetryAiProvider $provider): void
    {
        $measurement = IotTelemetryMeasurement::query()
            ->with(['device:id,user_id,device_id', 'aiAnalysis'])
            ->find($this->measurementId);

        if (! $measurement || $measurement->aiAnalysis?->status === IotTelemetryAiAnalysis::STATUS_COMPLETED) {
            return;
        }

        $analysis = $measurement->aiAnalysis()->firstOrCreate([], [
            'status' => IotTelemetryAiAnalysis::STATUS_PENDING,
        ]);

        $analysis->update([
            'status' => IotTelemetryAiAnalysis::STATUS_PROCESSING,
            'error_code' => null,
        ]);

        $recentMeasurements = $this->recentMeasurements($measurement);
        $result = $provider->analyze(
            $this->measurementPayload($measurement),
            $recentMeasurements,
            $measurement->device->user_id,
        );

        $reliability = $result['reliability'] ?? null;
        $riskLevel = $result['risk_level'] ?? null;

        if (! in_array($reliability, $this->reliabilityValues(), true)) {
            throw new RuntimeException('The AI reliability verdict is invalid.');
        }

        if (! in_array($riskLevel, $this->riskValues(), true)) {
            throw new RuntimeException('The AI risk level is invalid.');
        }

        $reliabilityReason = (string) ($result['reliability_reason'] ?? '');
        $minimumHistory = max(1, (int) config('iot.ai.minimum_history_size', 3));

        if ($reliability === IotTelemetryAiAnalysis::RELIABILITY_RELIABLE && count($recentMeasurements) < $minimumHistory) {
            $reliability = IotTelemetryAiAnalysis::RELIABILITY_UNKNOWN;
            $reliabilityReason = "Historique insuffisant : {$minimumHistory} mesures antérieures sont requises avant approbation.";
        }

        $analysis->update([
            'status' => IotTelemetryAiAnalysis::STATUS_COMPLETED,
            'reliability' => $reliability,
            'approved_for_use' => $reliability === IotTelemetryAiAnalysis::RELIABILITY_RELIABLE,
            'risk_level' => $riskLevel,
            'risk_summary' => (string) ($result['risk_summary'] ?? ''),
            'reliability_reason' => $reliabilityReason,
            'recommendations' => $this->stringList($result['recommendations'] ?? []),
            'disclaimer' => (string) ($result['disclaimer'] ?? 'Analyse IA informative, sans garantie médicale ni métrologique.'),
            'model' => $provider->modelName(),
            'error_code' => null,
            'analyzed_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        IotTelemetryAiAnalysis::query()
            ->where('iot_telemetry_measurement_id', $this->measurementId)
            ->where('status', '!=', IotTelemetryAiAnalysis::STATUS_COMPLETED)
            ->update([
                'status' => IotTelemetryAiAnalysis::STATUS_FAILED,
                'approved_for_use' => false,
                'error_code' => 'ai_analysis_failed',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function measurementPayload(IotTelemetryMeasurement $measurement): array
    {
        return [
            'temperature_celsius' => (float) $measurement->temperature,
            'humidity_percent' => (float) $measurement->humidity,
            'measured_at' => $measurement->measured_at?->toIso8601String(),
            'received_at' => $measurement->received_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function recentMeasurements(IotTelemetryMeasurement $measurement): array
    {
        return IotTelemetryMeasurement::query()
            ->where('iot_device_id', $measurement->iot_device_id)
            ->where('id', '<', $measurement->id)
            ->latest('id')
            ->limit(max(1, (int) config('iot.ai.history_size', 10)))
            ->get()
            ->reverse()
            ->values()
            ->map(fn (IotTelemetryMeasurement $item) => $this->measurementPayload($item))
            ->all();
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $values): array
    {
        return array_slice(array_values(array_filter(
            is_array($values) ? $values : [],
            fn ($value) => is_string($value) && $value !== '',
        )), 0, 5);
    }

    /**
     * @return list<string>
     */
    private function reliabilityValues(): array
    {
        return [
            IotTelemetryAiAnalysis::RELIABILITY_RELIABLE,
            IotTelemetryAiAnalysis::RELIABILITY_SUSPICIOUS,
            IotTelemetryAiAnalysis::RELIABILITY_UNKNOWN,
        ];
    }

    /**
     * @return list<string>
     */
    private function riskValues(): array
    {
        return [
            IotTelemetryAiAnalysis::RISK_LOW,
            IotTelemetryAiAnalysis::RISK_MODERATE,
            IotTelemetryAiAnalysis::RISK_HIGH,
            IotTelemetryAiAnalysis::RISK_CRITICAL,
            IotTelemetryAiAnalysis::RISK_UNKNOWN,
        ];
    }
}
