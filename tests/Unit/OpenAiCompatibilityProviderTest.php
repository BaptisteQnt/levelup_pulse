<?php

use App\Models\Game;
use App\Services\OpenAiCompatibilityProvider;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function openAiOutput(array $payload, array $annotations = []): array
{
    return [
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => json_encode($payload, JSON_THROW_ON_ERROR),
                'annotations' => $annotations,
            ]],
        ]],
    ];
}

it('uses web search for requirements and a separate structured comparison request', function () {
    config([
        'compatibility.openai.api_key' => 'test-key',
        'compatibility.openai.model' => 'gpt-5.4-mini',
        'compatibility.openai.reasoning_effort' => 'low',
        'compatibility.openai.max_output_tokens' => 2_000,
    ]);

    Http::fakeSequence()
        ->push(openAiOutput([
            'game_title' => 'Test Game',
            'minimum' => ['cpu' => 'CPU A', 'gpu' => 'GPU A', 'ram_gb' => 8, 'vram_gb' => 4, 'storage_gb' => 50, 'ssd_required' => false, 'os' => 'Windows 10', 'directx' => '12'],
            'recommended' => ['cpu' => 'CPU B', 'gpu' => 'GPU B', 'ram_gb' => 16, 'vram_gb' => 8, 'storage_gb' => 50, 'ssd_required' => true, 'os' => 'Windows 11', 'directx' => '12'],
            'sources' => [['title' => 'Official requirements', 'url' => 'https://example.com/game', 'publisher' => 'Publisher']],
            'notes' => [],
        ]))
        ->push(openAiOutput([
            'verdict' => 'high',
            'summary' => 'Le PC dépasse les prérequis recommandés.',
            'component_checks' => [],
            'bottlenecks' => [],
            'advice' => ['Utiliser les réglages élevés.'],
            'disclaimer' => 'Estimation IA sans garantie de FPS.',
        ]));

    $game = new Game(['title' => 'Test Game']);
    $provider = new OpenAiCompatibilityProvider;
    $requirements = $provider->researchRequirements($game, 10);
    $result = $provider->analyze($game, compatibilityHardwarePayload(), $requirements, 10);

    expect($requirements['sources'])->toHaveCount(1)
        ->and($result['verdict'])->toBe('high');

    Http::assertSentCount(2);
    Http::assertSent(function (Request $request) {
        $data = $request->data();

        return ($data['text']['format']['name'] ?? null) === 'game_requirements'
            && $data['model'] === 'gpt-5.4-mini'
            && $data['store'] === false
            && $data['reasoning']['effort'] === 'low'
            && $data['max_output_tokens'] === 2_000
            && ($data['tools'][0]['type'] ?? null) === 'web_search'
            && $data['text']['format']['type'] === 'json_schema';
    });

    Http::assertSent(function (Request $request) {
        $data = $request->data();

        return ($data['text']['format']['name'] ?? null) === 'compatibility_result'
            && ! array_key_exists('tools', $data);
    });
});

it('returns an unknown result after two malformed structured responses', function () {
    config(['compatibility.openai.api_key' => 'test-key']);
    Http::fakeSequence()->push(['output' => []])->push(['output' => []]);

    $result = (new OpenAiCompatibilityProvider)->analyze(
        new Game(['title' => 'Test Game']),
        compatibilityHardwarePayload(),
        ['minimum' => [], 'recommended' => [], 'sources' => []],
        1,
    );

    expect($result['verdict'])->toBe('unknown');
    Http::assertSentCount(2);
});

it('preserves every supported verdict from a valid structured response', function (string $verdict) {
    config(['compatibility.openai.api_key' => 'test-key']);
    Http::fake([
        '*' => Http::response(openAiOutput([
            'verdict' => $verdict,
            'summary' => 'Résultat de référence.',
            'component_checks' => [],
            'bottlenecks' => [],
            'advice' => [],
            'disclaimer' => 'Estimation IA.',
        ])),
    ]);

    $result = (new OpenAiCompatibilityProvider)->analyze(
        new Game(['title' => 'Test Game']),
        compatibilityHardwarePayload(),
        [
            'minimum' => ['cpu' => 'CPU A'],
            'recommended' => ['cpu' => 'CPU B'],
            'sources' => [['title' => 'Official', 'url' => 'https://example.com', 'publisher' => null]],
        ],
        1,
    );

    expect($result['verdict'])->toBe($verdict);
})->with(['high', 'medium', 'low', 'incompatible', 'unknown']);
