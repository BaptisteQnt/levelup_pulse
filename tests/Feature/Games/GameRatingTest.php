<?php

use App\Models\Game;
use App\Models\GameRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a user to rate a game and update the rating', function () {
    $user = User::factory()->create();
    $game = Game::create([
        'title' => 'Test Game',
        'slug' => 'test-game',
    ]);

    $this->actingAs($user)
        ->post(route('games.rating.store', $game), ['rating' => 7])
        ->assertRedirect();

    expect(GameRating::where('game_id', $game->id)->where('user_id', $user->id)->value('rating'))
        ->toBe(7);

    $this->post(route('games.rating.store', $game), ['rating' => 9])
        ->assertRedirect();

    expect(GameRating::count())->toBe(1)
        ->and(GameRating::first()->rating)->toBe(9);
});

it('validates the rating value', function () {
    $user = User::factory()->create();
    $game = Game::create([
        'title' => 'Another Game',
        'slug' => 'another-game',
    ]);

    $this->actingAs($user)
        ->post(route('games.rating.store', $game), ['rating' => 11])
        ->assertSessionHasErrors('rating');

    expect(GameRating::count())->toBe(0);
});
