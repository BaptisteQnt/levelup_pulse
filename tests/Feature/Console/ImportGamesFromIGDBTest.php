<?php

use App\Models\Game;
use App\Models\GameTranslation;
use App\Services\IGDBService;
use App\Services\Translator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

it('imports games and triggers translations when text is provided', function () {
    $mock = Mockery::mock(IGDBService::class);
    $mock->shouldReceive('fetchGames')
        ->once()
        ->with('zelda')
        ->andReturn([
            [
                'id' => 123,
                'name' => 'The Legend of Testing',
                'summary' => 'An epic summary waiting for translation.',
            ],
        ]);

    $this->app->instance(IGDBService::class, $mock);

    $this->app->bind(Translator::class, fn () => new class implements Translator {
        public function translate(string $text, string $to = 'fr', ?string $from = 'en'): string
        {
            return "[{$to}] {$text}";
        }
    });

    $this->artisan('games:import', ['search' => 'zelda'])
        ->expectsOutput('Games imported successfully (translations dispatched).')
        ->assertExitCode(0);

    $game = Game::where('slug', 'the-legend-of-testing')->first();
    expect($game)->not->toBeNull();

    $translation = GameTranslation::where('game_id', $game->id)->first();
    expect($translation)->not->toBeNull()
        ->and($translation->summary)->toBe('[fr] An epic summary waiting for translation.');
});
