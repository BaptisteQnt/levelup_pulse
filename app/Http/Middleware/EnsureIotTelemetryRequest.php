<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JsonException;
use Symfony\Component\HttpFoundation\Response;

class EnsureIotTelemetryRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! $request->isSecure()) {
            return $this->error('https_required', 'HTTPS is required.', 426);
        }

        if (! $request->isJson()) {
            return $this->error('unsupported_media_type', 'Content-Type must be application/json.', 415);
        }

        if (strlen($request->getContent()) > (int) config('iot.telemetry.max_payload_bytes', 4096)) {
            return $this->error('payload_too_large', 'The JSON payload is too large.', 413);
        }

        try {
            $payload = json_decode($request->getContent(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->error('invalid_json', 'The request body is not valid JSON.', 400);
        }

        if (! is_array($payload) || array_is_list($payload)) {
            return $this->error('invalid_json', 'The JSON payload must be an object.', 400);
        }

        return $next($request);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'code' => $code,
        ], $status)->header('Cache-Control', 'no-store');
    }
}
