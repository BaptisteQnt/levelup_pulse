<?php

namespace App\Http\Controllers;

use App\Models\CompatibilityScan;
use App\Models\Game;
use App\Services\CompatibilityScanScript;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class CompatibilityScanController extends Controller
{
    public function store(Request $request, Game $game, CompatibilityScanScript $scriptBuilder): Response
    {
        $this->ensureSecureProductionRequest($request);

        $plainToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $scan = CompatibilityScan::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $request->user()->id,
            'game_id' => $game->id,
            'token_hash' => hash('sha256', $plainToken),
            'status' => CompatibilityScan::STATUS_CREATED,
            'upload_expires_at' => now()->addMinutes(config('compatibility.scan.upload_ttl_minutes')),
            'purge_at' => now()->addHours(config('compatibility.scan.retention_hours')),
        ]);

        $scan->setRelation('game', $game);
        $script = $scriptBuilder->render($scan, $plainToken);
        $filename = 'LevelUpPulse-'.Str::slug($game->title).'.ps1';
        $disposition = (new ResponseHeaderBag)->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
        );

        return response($script, 200, [
            'Content-Type' => 'application/x-powershell; charset=UTF-8',
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'X-Scan-ID' => $scan->uuid,
            'X-Scan-Expires-At' => $scan->upload_expires_at->toIso8601String(),
            'X-Script-SHA256' => hash('sha256', $script),
        ]);
    }

    public function show(Request $request, Game $game, CompatibilityScan $compatibilityScan): JsonResponse
    {
        abort_unless(
            $compatibilityScan->user_id === $request->user()->id
            && $compatibilityScan->game_id === $game->id,
            404,
        );

        if (
            $compatibilityScan->status === CompatibilityScan::STATUS_CREATED
            && $compatibilityScan->upload_expires_at->isPast()
        ) {
            $compatibilityScan->update([
                'status' => CompatibilityScan::STATUS_EXPIRED,
                'error_code' => 'upload_expired',
                'token_hash' => null,
            ]);
        }

        $requirements = $compatibilityScan->requirements_payload;

        return response()->json([
            'id' => $compatibilityScan->uuid,
            'status' => $compatibilityScan->status,
            'result' => $compatibilityScan->status === CompatibilityScan::STATUS_COMPLETED
                ? $compatibilityScan->result_payload
                : null,
            'sources' => $compatibilityScan->status === CompatibilityScan::STATUS_COMPLETED
                ? ($requirements['sources'] ?? [])
                : [],
            'researched_at' => $compatibilityScan->researched_at?->toIso8601String(),
            'completed_at' => $compatibilityScan->completed_at?->toIso8601String(),
            'expires_at' => $compatibilityScan->purge_at->toIso8601String(),
            'error_code' => in_array($compatibilityScan->status, [
                CompatibilityScan::STATUS_FAILED,
                CompatibilityScan::STATUS_EXPIRED,
            ], true) ? $compatibilityScan->error_code : null,
        ])->header('Cache-Control', 'no-store, private');
    }

    private function ensureSecureProductionRequest(Request $request): void
    {
        abort_if(
            app()->environment('production') && ! $request->isSecure(),
            426,
            'HTTPS is required.',
        );
    }
}
