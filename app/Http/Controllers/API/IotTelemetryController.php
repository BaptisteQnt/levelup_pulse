<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateIotDevice;
use App\Http\Requests\Iot\StoreTelemetryRequest;
use App\Models\IotDevice;
use App\Models\IotTelemetryMeasurement;
use App\Services\IngestIotTelemetry;
use Illuminate\Http\JsonResponse;

class IotTelemetryController extends Controller
{
    public function __construct(private readonly IngestIotTelemetry $ingestTelemetry) {}

    public function store(StoreTelemetryRequest $request): JsonResponse
    {
        /** @var IotDevice $device */
        $device = $request->attributes->get(AuthenticateIotDevice::REQUEST_ATTRIBUTE);
        $result = $this->ingestTelemetry->handle($device, $request->validated());

        if ($result['status'] === IngestIotTelemetry::STATUS_REPLAYED) {
            return $this->conflict(
                'replayed_sequence',
                'The sequence is older than the last accepted sequence.',
            );
        }

        if ($result['status'] === IngestIotTelemetry::STATUS_CONFLICT) {
            return $this->conflict(
                'sequence_conflict',
                'The sequence already exists with different measurement data.',
            );
        }

        /** @var IotTelemetryMeasurement $measurement */
        $measurement = $result['measurement'];
        $status = $result['status'] === IngestIotTelemetry::STATUS_ACCEPTED ? 201 : 200;

        return response()->json([
            'status' => $result['status'],
            'data' => [
                'id' => $measurement->id,
                'device_id' => $device->device_id,
                'sequence' => $measurement->sequence,
                'measured_at' => $measurement->measured_at?->toIso8601String(),
                'received_at' => $measurement->received_at->toIso8601String(),
            ],
        ], $status)->header('Cache-Control', 'no-store');
    }

    private function conflict(string $code, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
        ], 409)->header('Cache-Control', 'no-store');
    }
}
