<?php

namespace App\Http\Controllers;

use App\Actions\Iot\GetLatestTelemetry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IotTelemetryReadingController extends Controller
{
    public function __invoke(Request $request, GetLatestTelemetry $latestTelemetry): JsonResponse
    {
        return response()
            ->json(['data' => $latestTelemetry->handle($request->user())])
            ->header('Cache-Control', 'no-store, private');
    }
}
