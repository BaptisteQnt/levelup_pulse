<?php

namespace Tests\Feature\Security;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_creation_is_audited_for_internal_users(): void
    {
        $editor = User::factory()->create(['is_editor' => true]);
        $game = Game::factory()->create();

        $this->actingAs($editor)->post(route('articles.store', $game), [
            'title' => 'Analyse securite du build',
            'content' => 'Un contenu assez long pour creer un article editorial.',
            'keywords' => 'analyse, test',
            'is_premium' => false,
            'published_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $editor->id,
            'action' => 'article.created',
        ]);
    }

    public function test_role_update_is_audited(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.powers.update', $target), [
            'is_admin' => false,
            'is_editor' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'target_user_id' => $target->id,
            'action' => 'roles.updated',
        ]);
    }
}
