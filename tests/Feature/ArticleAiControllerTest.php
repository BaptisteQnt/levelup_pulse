<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use App\Services\ArticleAiAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ArticleAiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_use_editorial_ai_tools(): void
    {
        $user = User::factory()->create(['is_editor' => false]);
        $game = Game::factory()->create();

        $this->actingAs($user)
            ->postJson(route('articles.ai.trending'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('articles.ai.correct', $game), [
                'content' => 'Un brouillon suffisamment long pour être corrigé.',
            ])
            ->assertForbidden();
    }

    public function test_editor_can_request_trending_games(): void
    {
        $editor = User::factory()->create(['is_editor' => true]);
        $assistant = Mockery::mock(ArticleAiAssistant::class);
        $assistant->shouldReceive('suggestTrendingGames')
            ->once()
            ->with($editor->id)
            ->andReturn([
                'games' => collect(range(1, 5))->map(fn (int $index) => [
                    'title' => "Jeu {$index}",
                    'why_trending' => "Raison {$index}",
                    'article_angle' => "Angle {$index}",
                ])->all(),
                'sources' => [['title' => 'Source', 'url' => 'https://example.com']],
                'generated_at' => now()->toIso8601String(),
            ]);
        $this->app->instance(ArticleAiAssistant::class, $assistant);

        $this->actingAs($editor)
            ->postJson(route('articles.ai.trending'))
            ->assertOk()
            ->assertJsonCount(5, 'games')
            ->assertJsonPath('games.0.title', 'Jeu 1');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $editor->id,
            'action' => 'article.ai.trends_requested',
        ]);
    }

    public function test_editor_receives_a_correction_without_mutating_an_article(): void
    {
        $editor = User::factory()->create(['is_editor' => true]);
        $game = Game::factory()->create();
        $assistant = Mockery::mock(ArticleAiAssistant::class);
        $assistant->shouldReceive('correctArticle')
            ->once()
            ->andReturn([
                'corrected_text' => 'Le brouillon corrigé.',
                'changes' => ['Orthographe corrigée.'],
                'editorial_notes' => [],
            ]);
        $this->app->instance(ArticleAiAssistant::class, $assistant);

        $this->actingAs($editor)
            ->postJson(route('articles.ai.correct', $game), [
                'title' => 'Mon article',
                'content' => 'Un brouillon suffisamment long pour être corrigé par le service.',
            ])
            ->assertOk()
            ->assertJsonPath('corrected_text', 'Le brouillon corrigé.')
            ->assertJsonPath('changes.0', 'Orthographe corrigée.');

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $editor->id,
            'action' => 'article.ai.correction_requested',
            'auditable_type' => $game->getMorphClass(),
            'auditable_id' => $game->id,
        ]);
    }

    public function test_correction_rejects_an_empty_or_oversized_draft(): void
    {
        $editor = User::factory()->create(['is_editor' => true]);
        $game = Game::factory()->create();

        $this->actingAs($editor)
            ->postJson(route('articles.ai.correct', $game), ['content' => 'Trop court'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->actingAs($editor)
            ->postJson(route('articles.ai.correct', $game), ['content' => str_repeat('a', 30001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }
}
