<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_article(): void
    {
        $editor = User::factory()->create(['is_editor' => true]);
        $game = Game::factory()->create();

        $response = $this->actingAs($editor)->post(route('articles.store', $game), [
            'title' => 'Un test complet',
            'content' => 'Un contenu assez long pour representer le corps de l article.',
            'keywords' => 'test, rpg',
            'is_premium' => false,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('articles', [
            'game_id' => $game->id,
            'user_id' => $editor->id,
            'title' => 'Un test complet',
            'is_premium' => false,
        ]);
    }

    public function test_regular_user_cannot_access_article_creation(): void
    {
        $user = User::factory()->create(['is_editor' => false]);
        $game = Game::factory()->create();

        $this->actingAs($user)
            ->get(route('articles.create', $game))
            ->assertForbidden();
    }

    public function test_user_can_react_to_article(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $this->actingAs($user)
            ->post(route('articles.react', $article), ['reaction' => 'like'])
            ->assertRedirect();

        $this->assertDatabaseHas('article_reactions', [
            'article_id' => $article->id,
            'user_id' => $user->id,
            'reaction' => 1,
        ]);
    }
}
