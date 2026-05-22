<?php

use App\Models\Game;
use App\Models\GameTranslation;
use App\Services\Translator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('translates all games immediately when queue driver is sync', function () {
    config(['queue.default' => 'sync']);

    $this->app->bind(Translator::class, fn () => new class implements Translator {
        public function translate(string $text, string $to = 'fr', ?string $from = 'en'): string
        {
            return "[FR] {$text}";
        }
    });

    $gameA = Game::create([
        'title' => 'Game A',
        'slug' => 'game-a',
        'summary' => 'First summary',
    ]);

    $gameB = Game::create([
        'title' => 'Game B',
        'slug' => 'game-b',
        'storyline' => 'Once upon a time',
    ]);

    $this->artisan('games:translate', ['--all' => true])
        ->expectsOutput('Traductions exécutées immédiatement pour 2 jeux (queue=sync).')
        ->assertExitCode(0);

    expect(GameTranslation::count())->toBe(2)
        ->and(GameTranslation::where('game_id', $gameA->id)->value('summary'))->toBe('[FR] First summary')
        ->and(GameTranslation::where('game_id', $gameB->id)->value('storyline'))->toBe('[FR] Once upon a time');
});
