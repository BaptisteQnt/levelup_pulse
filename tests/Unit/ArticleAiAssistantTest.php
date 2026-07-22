<?php

use App\Models\Game;
use App\Services\ArticleAiAssistant;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function articleAiResponse(array $payload, array $sources = []): array
{
    $output = [];

    if ($sources !== []) {
        $output[] = [
            'type' => 'web_search_call',
            'action' => ['sources' => $sources],
        ];
    }

    $output[] = [
        'type' => 'message',
        'content' => [[
            'type' => 'output_text',
            'text' => json_encode($payload, JSON_THROW_ON_ERROR),
            'annotations' => [],
        ]],
    ];

    return ['output' => $output];
}

beforeEach(function () {
    config([
        'article_ai.openai.api_key' => 'test-key',
        'article_ai.openai.model' => 'gpt-5.6-terra',
        'article_ai.openai.reasoning_effort' => 'low',
        'article_ai.openai.trends_max_output_tokens' => 1_800,
        'article_ai.openai.correction_max_output_tokens' => 12_000,
        'article_ai.openai.timeout_seconds' => 30,
    ]);
});

it('uses a grounded structured OpenAI request for five trending games', function () {
    $games = collect(range(1, 5))->map(fn (int $index) => [
        'title' => "Trending Game {$index}",
        'why_trending' => "Actualité documentée {$index}",
        'article_angle' => "Angle éditorial {$index}",
    ])->all();

    Http::fake([
        '*' => Http::response(articleAiResponse(
            ['games' => $games],
            [['title' => 'Source fiable', 'url' => 'https://example.com/gaming-news']],
        )),
    ]);

    $result = (new ArticleAiAssistant)->suggestTrendingGames(42);

    expect($result['games'])->toHaveCount(5)
        ->and($result['sources'])->toBe([
            ['title' => 'Source fiable', 'url' => 'https://example.com/gaming-news'],
        ]);

    Http::assertSent(function (Request $request) {
        $data = $request->data();

        return $request->url() === 'https://api.openai.com/v1/responses'
            && $data['model'] === 'gpt-5.6-terra'
            && $data['store'] === false
            && $data['reasoning']['effort'] === 'low'
            && $data['max_output_tokens'] === 1_800
            && ($data['tools'][0]['type'] ?? null) === 'web_search'
            && ($data['include'][0] ?? null) === 'web_search_call.action.sources'
            && $data['text']['format']['type'] === 'json_schema'
            && $data['text']['format']['name'] === 'trending_games'
            && ($data['text']['format']['schema']['properties']['games']['minItems'] ?? null) === 5;
    });
});

it('corrects an article with a separate request that cannot use web search', function () {
    Http::fake([
        '*' => Http::response(articleAiResponse([
            'corrected_text' => 'Ce texte est corrigé tout en conservant les faits.',
            'changes' => ['Accords et ponctuation corrigés.'],
            'editorial_notes' => ['Vérifier la date de sortie citée.'],
        ])),
    ]);

    $result = (new ArticleAiAssistant)->correctArticle(
        new Game(['title' => 'Test Game']),
        'Notre analyse',
        'Ce texte et corriger mais il conserve les faits.',
        7,
    );

    expect($result['corrected_text'])->toContain('conservant les faits')
        ->and($result['changes'])->toHaveCount(1)
        ->and($result['editorial_notes'])->toHaveCount(1);

    Http::assertSent(function (Request $request) {
        $data = $request->data();
        $input = json_decode($data['input'], true, 512, JSON_THROW_ON_ERROR);

        return $data['text']['format']['name'] === 'article_text_correction'
            && $data['max_output_tokens'] === 12_000
            && ! array_key_exists('tools', $data)
            && $input['game'] === 'Test Game'
            && $input['article_title'] === 'Notre analyse'
            && str_contains($input['draft'], 'conserve les faits');
    });
});
