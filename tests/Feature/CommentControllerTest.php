<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_comment_and_it_is_pending(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();

        $response = $this->actingAs($user)->post(route('comments.store'), [
            'game_id' => $game->id,
            'content' => 'Un commentaire à valider',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'game_id' => $game->id,
            'content' => 'Un commentaire à valider',
            'is_approved' => false,
        ]);
    }

    public function test_admin_can_approve_comment(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $comment = Comment::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($admin)->patch(route('admin.comments.approve', $comment));

        $response->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_approved' => true,
        ]);
    }

    public function test_non_admin_cannot_approve_comment(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $comment = Comment::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($user)->patch(route('admin.comments.approve', $comment));

        $response->assertForbidden();

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'is_approved' => false,
        ]);
    }
}
