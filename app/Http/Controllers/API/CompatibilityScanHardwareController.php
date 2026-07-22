<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\AnalyzeCompatibilityScan;
use App\Models\CompatibilityScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CompatibilityScanHardwareController extends Controller
{
    public function store(Request $request, CompatibilityScan $compatibilityScan): JsonResponse
    {
        $this->ensureSecureProductionRequest($request);

        abort_unless($request->isJson(), 415, 'A JSON payload is required.');
        abort_if(
            strlen($request->getContent()) > config('compatibility.scan.max_payload_bytes'),
            413,
            'The hardware payload is too large.',
        );

        $plainToken = (string) $request->header('X-LevelUp-Scan-Token', '');
        abort_if($plainToken === '', 401, 'The scan token is required.');

        $payload = $request->json()->all();
        $validator = Validator::make(['payload' => $payload], $this->rules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Les informations matérielles sont invalides.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $outcome = DB::transaction(function () use ($compatibilityScan, $plainToken, $payload): string {
            $scan = CompatibilityScan::query()
                ->whereKey($compatibilityScan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($scan->status !== CompatibilityScan::STATUS_CREATED || $scan->token_hash === null) {
                return 'conflict';
            }

            if ($scan->upload_expires_at->isPast()) {
                $scan->update([
                    'status' => CompatibilityScan::STATUS_EXPIRED,
                    'error_code' => 'upload_expired',
                    'token_hash' => null,
                ]);

                return 'expired';
            }

            if (! hash_equals($scan->token_hash, hash('sha256', $plainToken))) {
                return 'invalid';
            }

            $scan->update([
                'status' => CompatibilityScan::STATUS_UPLOADED,
                'hardware_payload' => $payload,
                'token_hash' => null,
                'uploaded_at' => now(),
                'error_code' => null,
            ]);

            return 'accepted';
        });

        abort_if($outcome === 'expired', 410, 'The scan token has expired.');
        abort_if($outcome === 'invalid', 401, 'The scan token is invalid.');
        abort_if($outcome === 'conflict', 409, 'This scan has already been submitted.');

        AnalyzeCompatibilityScan::dispatch($compatibilityScan->id);

        return response()->json([
            'scan_id' => $compatibilityScan->uuid,
            'status' => CompatibilityScan::STATUS_UPLOADED,
        ], 202)->header('Cache-Control', 'no-store');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'payload' => 'required|array:schema_version,collected_at,os,cpu,gpu,memory,storage',
            'payload.schema_version' => 'required|integer|in:1',
            'payload.collected_at' => 'required|date',
            'payload.os' => 'required|array:caption,version,architecture,directx_version',
            'payload.os.caption' => 'nullable|string|max:255',
            'payload.os.version' => 'nullable|string|max:100',
            'payload.os.architecture' => 'nullable|string|max:100',
            'payload.os.directx_version' => 'nullable|string|max:100',
            'payload.cpu' => 'present|array|max:4',
            'payload.cpu.*' => 'required|array:name,cores,logical_processors,max_clock_mhz',
            'payload.cpu.*.name' => 'required|string|max:255',
            'payload.cpu.*.cores' => 'nullable|integer|min:1|max:512',
            'payload.cpu.*.logical_processors' => 'nullable|integer|min:1|max:1024',
            'payload.cpu.*.max_clock_mhz' => 'nullable|integer|min:1|max:100000',
            'payload.gpu' => 'present|array|max:8',
            'payload.gpu.*' => 'required|array:name,vram_bytes,vram_is_estimate,driver_version',
            'payload.gpu.*.name' => 'required|string|max:255',
            'payload.gpu.*.vram_bytes' => 'nullable|integer|min:0',
            'payload.gpu.*.vram_is_estimate' => 'required|boolean',
            'payload.gpu.*.driver_version' => 'nullable|string|max:100',
            'payload.memory' => 'required|array:total_bytes',
            'payload.memory.total_bytes' => 'required|integer|min:0',
            'payload.storage' => 'required|array:volumes,physical_disks',
            'payload.storage.volumes' => 'present|array|max:26',
            'payload.storage.volumes.*' => 'required|array:drive,filesystem,total_bytes,free_bytes',
            'payload.storage.volumes.*.drive' => ['required', 'string', 'regex:/^[A-Z]:$/i'],
            'payload.storage.volumes.*.filesystem' => 'nullable|string|max:32',
            'payload.storage.volumes.*.total_bytes' => 'nullable|integer|min:0',
            'payload.storage.volumes.*.free_bytes' => 'nullable|integer|min:0',
            'payload.storage.physical_disks' => 'present|array|max:16',
            'payload.storage.physical_disks.*' => 'required|array:model,media_type,total_bytes',
            'payload.storage.physical_disks.*.model' => 'nullable|string|max:255',
            'payload.storage.physical_disks.*.media_type' => 'nullable|string|max:100',
            'payload.storage.physical_disks.*.total_bytes' => 'nullable|integer|min:0',
        ];
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
