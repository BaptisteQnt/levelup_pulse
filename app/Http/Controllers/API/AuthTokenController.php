<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthTokenController extends Controller
{
    /**
     * Exchange valid credentials for a Sanctum personal access token.
     */
    public function store(ApiTokenRequest $request): JsonResponse
    {
        $user = $request->resolveUser();

        $tokenName = $request->string('device_name')->isNotEmpty()
            ? $request->string('device_name')->toString()
            : ($request->userAgent() ?? 'api-token');

        $token = $user->createToken($tokenName);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Revoke the currently authenticated access token.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }
}
