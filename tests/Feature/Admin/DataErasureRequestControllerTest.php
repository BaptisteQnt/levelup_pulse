<?php

namespace Tests\Feature\Admin;

use App\Models\DataErasureRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataErasureRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_user_account_from_request(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $request = DataErasureRequest::factory()->accountDeletion()->create();

        $response = $this->actingAs($admin)->post(route('admin.privacy.requests.destroy-account', $request));

        $response->assertRedirect(route('admin.privacy.requests.index'));

        $this->assertDatabaseMissing('users', [
            'id' => $request->user_id,
        ]);

        $this->assertDatabaseMissing('data_erasure_requests', [
            'id' => $request->id,
        ]);
    }

    public function test_admin_can_anonymize_user_personal_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $request = DataErasureRequest::factory()->dataDeletion()->create();

        $response = $this->actingAs($admin)->post(route('admin.privacy.requests.erase-data', $request));

        $response->assertRedirect();

        $request->refresh();
        $user = $request->user;

        $this->assertNotNull($user);
        $this->assertSame('Compte anonymisé', $user->name);
        $this->assertStringStartsWith('anon-', $user->username);
        $this->assertStringContainsString('@deleted.local', $user->email);
        $this->assertNull($user->phone);
        $this->assertNull($user->address);
        $this->assertNull($user->city);
        $this->assertNull($user->cp);
        $this->assertNull($user->country);
        $this->assertSame(0, $user->age);
        $this->assertNull($user->display_name_color);
        $this->assertNull($user->display_alias);
        $this->assertNull($user->profile_border_style);
        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->remember_token);

        $this->assertDatabaseHas('data_erasure_requests', [
            'id' => $request->id,
            'status' => 'resolved',
        ]);
        $this->assertNotNull($request->resolved_at);
    }

    public function test_non_admin_users_cannot_process_rgpd_actions(): void
    {
        $nonAdmin = User::factory()->create(['is_admin' => false]);
        $request = DataErasureRequest::factory()->dataDeletion()->create();

        $this->actingAs($nonAdmin)
            ->post(route('admin.privacy.requests.erase-data', $request))
            ->assertForbidden();

        $this->actingAs($nonAdmin)
            ->post(route('admin.privacy.requests.destroy-account', $request))
            ->assertForbidden();

        $request->refresh();

        $this->assertSame('pending', $request->status);
    }
}
