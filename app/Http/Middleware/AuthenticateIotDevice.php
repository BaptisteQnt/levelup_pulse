<?php

namespace App\Http\Middleware;

use App\Models\IotDevice;
use App\Services\IotDeviceTokenManager;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateIotDevice
{
    public const REQUEST_ATTRIBUTE = 'authenticated_iot_device';

    public function __construct(private readonly IotDeviceTokenManager $tokens) {}

    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->input('device_id');
        $plainTextToken = $request->bearerToken();

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return $this->authenticationFailed($request, $deviceId, 'missing_token');
        }

        if (! is_string($deviceId) || $deviceId === '') {
            return $this->authenticationFailed($request, null, 'missing_device_id');
        }

        $device = IotDevice::query()->where('device_id', $deviceId)->first();

        if (! $device || ! hash_equals($device->token_hash, $this->tokens->hash($plainTextToken))) {
            return $this->authenticationFailed($request, $deviceId, 'invalid_credentials');
        }

        if (! $device->is_active || $device->revoked_at !== null) {
            Log::warning('IoT telemetry rejected for an unavailable device.', [
                'device_id' => $device->device_id,
                'reason' => $device->revoked_at !== null ? 'revoked' : 'inactive',
                'ip_address' => $request->ip(),
            ]);

            return $this->error('device_unavailable', 'The device is disabled or revoked.', 403);
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE, $device);

        return $next($request);
    }

    private function authenticationFailed(Request $request, mixed $deviceId, string $reason): JsonResponse
    {
        Log::warning('IoT telemetry authentication failed.', [
            'device_id' => is_string($deviceId) ? $deviceId : null,
            'reason' => $reason,
            'ip_address' => $request->ip(),
        ]);

        return $this->error('invalid_device_credentials', 'Device credentials are invalid.', 401)
            ->header('WWW-Authenticate', 'Bearer');
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
        ], $status)->header('Cache-Control', 'no-store');
    }
}
