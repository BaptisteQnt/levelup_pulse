<?php

use App\Models\Game;
use App\Models\User;
use App\Services\IGDBService;
use App\Services\Translator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Mockery;

uses(RefreshDatabase::class);

afterEach(function () {
    Mockery::close();
});

it('returns games from the local database when the search matches existing titles', function () {
    $user = User::factory()->create();

    Game::create([
        'title' => 'The Legend of Testing',
        'slug' => 'the-legend-of-testing',
    ]);

    $this->actingAs($user);

    $response = $this->get(route('games.index', ['search' => 'Legend']));

    $response->assertStatus(200);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('games/Index')
        ->where('searchQuery', 'Legend')
        ->where('searchMessage', null)
        ->has('games.data', 1, fn (AssertableInertia $item) => $item
            ->where('title', 'The Legend of Testing')
            ->etc()
        )
    );
});

it('imports games from IGDB when the search misses the local database', function () {
    $user = User::factory()->create();

    $mock = Mockery::mock(IGDBService::class);
    $mock->shouldReceive('fetchGames')
        ->once()
        ->with('Chrono Trigger')
        ->andReturn([
            [
                'id' => 42,
                'name' => 'Chrono Trigger',
                'summary' => 'Time travelling adventure.',
            ],
        ]);

    $this->app->instance(IGDBService::class, $mock);

    $this->app->bind(Translator::class, fn () => new class implements Translator {
        public function translate(string $text, string $to = 'fr', ?string $from = 'en'): string
        {
            return "[{$to}] {$text}";
        }
    });

    $this->actingAs($user);

    $response = $this->get(route('games.index', ['search' => 'Chrono Trigger']))
        ->assertStatus(200);

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('games/Index')
        ->where('searchQuery', 'Chrono Trigger')
        ->where('searchMessage', 'Un jeu a été importé depuis IGDB.')
        ->has('games.data', 1, fn (AssertableInertia $item) => $item
            ->where('title', 'Chrono Trigger')
            ->etc()
        )
    );

    expect(Game::where('slug', 'chrono-trigger')->exists())->toBeTrue();
});

