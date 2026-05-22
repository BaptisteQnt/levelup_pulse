<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Game;
use App\Models\GameRating;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_stats_endpoint(): void
    {
        $this->getJson('/api/stats')->assertUnauthorized();
    }

    public function test_guest_cannot_access_game_rating_endpoint(): void
    {
        $game = Game::factory()->create();

        $this->getJson(route('api.games.rating', ['name' => $game->title]))->assertUnauthorized();
    }

    public function test_stats_endpoint_returns_aggregated_metrics(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $gameA = Game::factory()->create();
        $gameB = Game::factory()->create();

        GameRating::factory()->for($gameA, 'game')->for($user, 'user')->create(['rating' => 8]);
        GameRating::factory()->for($gameA, 'game')->for($anotherUser, 'user')->create(['rating' => 6]);
        GameRating::factory()->for($gameB, 'game')->for($user, 'user')->create(['rating' => 7]);

        Comment::factory()->for($gameA, 'game')->for($user, 'user')->create();
        Comment::factory()->for($gameB, 'game')->for($anotherUser, 'user')->create();
        Comment::factory()->for($gameA, 'game')->for($user, 'user')->create(['is_approved' => false]);

        Tip::factory()->for($gameA, 'game')->for($user, 'user')->create();
        Tip::factory()->for($gameB, 'game')->for($anotherUser, 'user')->create(['is_approved' => false]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/stats');

        $response
            ->assertOk()
            ->assertJson([
                'games' => [
                    'total' => 2,
                    'rated_total' => 2,
                ],
                'ratings' => [
                    'total' => 3,
                    'average' => 7.0,
                ],
                'comments' => [
                    'approved_total' => 2,
                ],
                'tips' => [
                    'approved_total' => 1,
                ],
                'users' => [
                    'total' => 2,
                ],
            ]);
    }

    public function test_game_rating_endpoint_returns_average_and_count(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $game = Game::factory()->create();

        GameRating::factory()->for($game, 'game')->for($user, 'user')->create(['rating' => 8]);
        GameRating::factory()->for($game, 'game')->for($anotherUser, 'user')->create(['rating' => 7]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.games.rating', ['name' => $game->title]));

        $response
            ->assertOk()
            ->assertJson([
                'game_id' => $game->id,
                'average_rating' => 7.5,
                'ratings_count' => 2,
            ]);
    }

    public function test_game_rating_endpoint_requires_game_name(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson(route('api.games.rating'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_game_rating_endpoint_can_find_game_by_slug(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $game = Game::factory()->create([
            'title' => 'Level Up Quest',
            'slug' => 'level-up-quest',
        ]);

        GameRating::factory()->for($game, 'game')->for($user, 'user')->create(['rating' => 9]);
        GameRating::factory()->for($game, 'game')->for($anotherUser, 'user')->create(['rating' => 5]);

        Sanctum::actingAs($user);

        $response = $this->getJson(route('api.games.rating', ['name' => $game->slug]));

        $response
            ->assertOk()
            ->assertJson([
                'game_id' => $game->id,
                'average_rating' => 7.0,
                'ratings_count' => 2,
            ]);
    }
}
