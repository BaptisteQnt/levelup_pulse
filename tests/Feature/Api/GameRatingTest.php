<?php

namespace Tests\Feature\Api;

use App\Models\Game;
use App\Models\GameRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class GameRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_manage_game_ratings(): void
    {
        $game = Game::factory()->create();

        $this->getJson(route('api.games.ratings.show', ['game' => $game->slug]))->assertUnauthorized();
        $this->postJson(route('api.games.ratings.store', ['game' => $game->slug]), ['rating' => 5])->assertUnauthorized();
        $this->putJson(route('api.games.ratings.update', ['game' => $game->slug]), ['rating' => 6])->assertUnauthorized();
        $this->deleteJson(route('api.games.ratings.destroy', ['game' => $game->slug]))->assertUnauthorized();

    }

    public function test_user_can_create_game_rating(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $game = Game::factory()->create();

        $response = $this->postJson(route('api.games.ratings.store', ['game' => $game->slug]), ['rating' => 7]);


        $response
            ->assertCreated()
            ->assertJson([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'rating' => 7,
            ]);

        $this->assertDatabaseHas('game_ratings', [
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rating' => 7,
        ]);
    }

    public function test_user_cannot_create_multiple_ratings_for_same_game(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $game = Game::factory()->create();

        GameRating::factory()->for($game, 'game')->for($user, 'user')->create(['rating' => 5]);

        $response = $this->postJson(route('api.games.ratings.store', ['game' => $game->slug]), ['rating' => 8]);


        $response
            ->assertStatus(Response::HTTP_CONFLICT)
            ->assertJson([
                'message' => 'Une note existe déjà pour ce jeu. Utilisez PUT pour la modifier.',
            ]);
    }

    public function test_user_can_view_own_game_rating(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $game = Game::factory()->create();

        GameRating::factory()->for($game, 'game')->for($user, 'user')->create(['rating' => 9]);

        $response = $this->getJson(route('api.games.ratings.show', ['game' => $game->slug]));


        $response
            ->assertOk()
            ->assertJson([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'rating' => 9,
            ]);
    }

    public function test_show_returns_not_found_when_rating_is_missing(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $game = Game::factory()->create();

        $this->getJson(route('api.games.ratings.show', ['game' => $game->slug]))

            ->assertNotFound()
            ->assertJson([
                'message' => 'Aucune note trouvée pour ce jeu.',
            ]);
    }

    public function test_user_can_update_game_rating(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $game = Game::factory()->create();

        GameRating::factory()->for($game, 'game')->for($user, 'user')->create(['rating' => 4]);

        $response = $this->putJson(route('api.games.ratings.update', ['game' => $game->slug]), ['rating' => 10]);


        $response
            ->assertOk()
            ->assertJson([
                'rating' => 10,
            ]);

        $this->assertDatabaseHas('game_ratings', [
            'game_id' => $game->id,
            'user_id' => $user->id,
            'rating' => 10,
        ]);
    }

    public function test_update_returns_not_found_when_rating_is_missing(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $game = Game::factory()->create();

        $this->putJson(route('api.games.ratings.update', ['game' => $game->slug]), ['rating' => 6])

            ->assertNotFound()
            ->assertJson([
                'message' => 'Aucune note trouvée pour ce jeu.',
            ]);
    }

    public function test_user_can_delete_game_rating(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $game = Game::factory()->create();

        GameRating::factory()->for($game, 'game')->for($user, 'user')->create(['rating' => 3]);

        $this->deleteJson(route('api.games.ratings.destroy', ['game' => $game->slug]))

            ->assertNoContent();

        $this->assertDatabaseMissing('game_ratings', [
            'game_id' => $game->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_delete_returns_not_found_when_rating_is_missing(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $game = Game::factory()->create();

        $this->deleteJson(route('api.games.ratings.destroy', ['game' => $game->slug]))

            ->assertNotFound()
            ->assertJson([
                'message' => 'Aucune note trouvée pour ce jeu.',
            ]);
    }
}
