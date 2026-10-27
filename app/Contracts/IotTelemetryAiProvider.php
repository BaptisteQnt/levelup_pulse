<?php

namespace App\Contracts;

interface IotTelemetryAiProvider
{
    /**
     * @param  array<string, mixed>  $currentMeasurement
     * @param  list<array<string, mixed>>  $recentMeasurements
     * @return array{
     *     reliability: 'reliable'|'suspicious'|'unknown',
     *     risk_level: 'low'|'moderate'|'high'|'critical'|'unknown',
     *     risk_summary: string,
     *     reliability_reason: string,
     *     recommendations: list<string>,
     *     disclaimer: string
     * }
     */
    public function analyze(array $currentMeasurement, array $recentMeasurements, int $userId): array;

    public function modelName(): string;
}
