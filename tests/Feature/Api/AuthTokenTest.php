<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_exchange_credentials_for_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'secret-password',
            'device_name' => 'cli',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'tokenable_type' => User::class,
            'name' => 'cli',
        ]);
    }

    public function test_invalid_credentials_return_validation_error(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret-password'),
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_revoke_current_token(): void
    {
        $user = User::factory()->create();

        $token = $user->createToken('test-device');
        $plainTextToken = $token->plainTextToken;
        $tokenId = $token->accessToken->id;

        $response = $this->withToken($plainTextToken)->deleteJson('/api/auth/token');

        $response->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }
}
