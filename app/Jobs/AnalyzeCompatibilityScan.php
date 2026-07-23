<?php

namespace App\Jobs;

use App\Contracts\CompatibilityAiProvider;
use App\Models\CompatibilityScan;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class AnalyzeCompatibilityScan implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 240;

    public bool $failOnTimeout = true;

    public function __construct(public int $scanId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [15, 60];
    }

    public function handle(CompatibilityAiProvider $provider): void
    {
        $scan = CompatibilityScan::query()->with('game')->find($this->scanId);

        if (! $scan || ! in_array($scan->status, [
            CompatibilityScan::STATUS_UPLOADED,
            CompatibilityScan::STATUS_RESEARCHING,
            CompatibilityScan::STATUS_ANALYZING,
        ], true)) {
            return;
        }

        $hardware = $scan->hardware_payload;

        if (! is_array($hardware)) {
            $scan->update([
                'status' => CompatibilityScan::STATUS_FAILED,
                'error_code' => 'hardware_unavailable',
            ]);

            return;
        }

        $requirements = $scan->requirements_payload;

        if (! is_array($requirements)) {
            $scan->update([
                'status' => CompatibilityScan::STATUS_RESEARCHING,
                'error_code' => null,
            ]);

            $requirements = $provider->researchRequirements($scan->game, $scan->user_id);

            $scan->update([
                'requirements_payload' => $requirements,
                'researched_at' => now(),
            ]);
        }

        $scan->update(['status' => CompatibilityScan::STATUS_ANALYZING]);
        $result = $provider->analyze($scan->game, $hardware, $requirements, $scan->user_id);

        $scan->update([
            'status' => CompatibilityScan::STATUS_COMPLETED,
            'result_payload' => $result,
            'hardware_payload' => null,
            'completed_at' => now(),
            'error_code' => null,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        CompatibilityScan::query()
            ->whereKey($this->scanId)
            ->whereNotIn('status', [
                CompatibilityScan::STATUS_COMPLETED,
                CompatibilityScan::STATUS_EXPIRED,
            ])
            ->update([
                'status' => CompatibilityScan::STATUS_FAILED,
                'error_code' => 'analysis_failed',
                'hardware_payload' => null,
            ]);
    }
}
