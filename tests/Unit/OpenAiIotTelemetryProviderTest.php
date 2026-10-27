<?php

use App\Services\OpenAiIotTelemetryProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('requests a private structured IoT risk and reliability analysis', function () {
    config([
        'app.key' => 'base64:test-app-key',
        'iot.ai.api_key' => 'test-openai-key',
        'iot.ai.model' => 'gpt-5.4-mini',
        'iot.ai.reasoning_effort' => 'low',
        'iot.ai.max_output_tokens' => 1_000,
        'iot.ai.timeout_seconds' => 30,
    ]);

    Http::fake([
        'https://api.openai.com/v1/responses' => Http::response(iotOpenAiResponse([
            'reliability' => 'reliable',
            'risk_level' => 'moderate',
            'risk_summary' => 'La pièce est chaude et mérite des pauses régulières.',
            'reliability_reason' => 'La valeur est cohérente avec les relevés récents.',
            'recommendations' => ['Aérer la pièce.', 'Boire régulièrement.'],
            'disclaimer' => 'Analyse informative.',
        ])),
    ]);

    $result = app(OpenAiIotTelemetryProvider::class)->analyze(
        [
            'temperature_celsius' => 31.2,
            'humidity_percent' => 55,
            'measured_at' => null,
            'received_at' => '2026-10-27T12:00:00+00:00',
        ],
        [[
            'temperature_celsius' => 31,
            'humidity_percent' => 54,
            'measured_at' => null,
            'received_at' => '2026-10-27T11:59:50+00:00',
        ]],
        42,
    );

    expect($result['reliability'])->toBe('reliable')
        ->and($result['risk_level'])->toBe('moderate');

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();

        return $request->url() === 'https://api.openai.com/v1/responses'
            && $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && $payload['store'] === false
            && $payload['text']['format']['type'] === 'json_schema'
            && $payload['text']['format']['strict'] === true
            && $payload['text']['format']['schema']['additionalProperties'] === false
            && ! str_contains($payload['input'], 'test-openai-key')
            && ! str_contains($payload['input'], '42');
    });
});

/**
 * @param  array<string, mixed>  $payload
 * @return array<string, mixed>
 */
function iotOpenAiResponse(array $payload): array
{
    return [
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => json_encode($payload, JSON_THROW_ON_ERROR),
            ]],
        ]],
    ];
}
