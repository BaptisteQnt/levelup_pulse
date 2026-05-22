<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\Tip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_tip(): void
    {
        $user = User::factory()->create();
        $game = Game::factory()->create();

        $response = $this->actingAs($user)->post(route('tips.store'), [
            'game_id' => $game->id,
            'content' => 'Une astuce très utile',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('tips', [
            'user_id' => $user->id,
            'game_id' => $game->id,
            'content' => 'Une astuce très utile',
            'is_approved' => false,
        ]);
    }

    public function test_user_can_delete_own_tip(): void
    {
        $user = User::factory()->create();
        $tip = Tip::factory()->for($user)->create();

        $response = $this->actingAs($user)->delete(route('tips.destroy', $tip));

        $response->assertRedirect();
        $this->assertDatabaseMissing('tips', [
            'id' => $tip->id,
        ]);
    }

    public function test_user_cannot_delete_tip_from_someone_else(): void
    {
        $tip = Tip::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)->delete(route('tips.destroy', $tip));

        $response->assertForbidden();
        $this->assertDatabaseHas('tips', [
            'id' => $tip->id,
        ]);
    }

    public function test_admin_can_approve_tip(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $tip = Tip::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($admin)->patch(route('admin.tips.approve', $tip));

        $response->assertRedirect();

        $this->assertDatabaseHas('tips', [
            'id' => $tip->id,
            'is_approved' => true,
        ]);
    }

    public function test_non_admin_cannot_approve_tip(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $tip = Tip::factory()->create(['is_approved' => false]);

        $response = $this->actingAs($user)->patch(route('admin.tips.approve', $tip));

        $response->assertForbidden();

        $this->assertDatabaseHas('tips', [
            'id' => $tip->id,
            'is_approved' => false,
        ]);
    }
}
