<?php

use App\Services\DeeplTranslator;
use App\Services\IGDBService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

it('builds the DeepL translator from Laravel configuration', function () {
    config([
        'services.deepl.key' => 'deepl-test-key',
        'services.deepl.url' => 'https://deepl.test/v2/translate',
    ]);

    Http::fake([
        'https://deepl.test/v2/translate' => Http::response([
            'translations' => [
                ['text' => 'Bonjour'],
            ],
        ]),
    ]);

    $translated = DeeplTranslator::fromConfig()->translate('Hello');

    expect($translated)->toBe('Bonjour');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://deepl.test/v2/translate'
        && $request->hasHeader('Authorization', 'DeepL-Auth-Key deepl-test-key')
        && $request['source_lang'] === 'EN'
        && $request['target_lang'] === 'FR');
});

it('rejects a missing DeepL key before sending a request', function () {
    config(['services.deepl.key' => null]);

    expect(fn () => DeeplTranslator::fromConfig())
        ->toThrow(RuntimeException::class, 'DEEPL_API_KEY is not configured.');
});

it('uses Laravel configuration for IGDB credentials and endpoints', function () {
    config([
        'services.igdb.client_id' => 'igdb-test-client',
        'services.igdb.client_secret' => 'igdb-test-secret',
        'services.igdb.token_url' => 'https://id.igdb.test/token',
        'services.igdb.games_url' => 'https://api.igdb.test/games',
    ]);

    Cache::forget('igdb_token');

    Http::fake([
        'https://id.igdb.test/token' => Http::response([
            'access_token' => 'igdb-test-token',
        ]),
        'https://api.igdb.test/games' => Http::response([
            ['id' => 42, 'name' => 'Test Game'],
        ]),
    ]);

    $games = (new IGDBService)->fetchGames('Test Game');

    expect($games)->toBe([
        ['id' => 42, 'name' => 'Test Game'],
    ]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://id.igdb.test/token'
        && $request['client_id'] === 'igdb-test-client'
        && $request['client_secret'] === 'igdb-test-secret');

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.igdb.test/games'
        && $request->hasHeader('Client-ID', 'igdb-test-client')
        && $request->hasHeader('Authorization', 'Bearer igdb-test-token'));
});
