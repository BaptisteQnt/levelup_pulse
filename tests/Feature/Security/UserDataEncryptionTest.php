<?php

namespace Tests\Feature\Security;

use App\Models\DataErasureRequest;
use App\Models\OauthAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserDataEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_sensitive_user_profile_data_is_encrypted_at_rest(): void
    {
        $user = User::factory()->create([
            'name' => 'Jane Doe',
            'phone' => '0601020304',
            'address' => '12 rue de la Paix',
            'city' => 'Paris',
            'cp' => '75001',
            'country' => 'France',
            'age' => 28,
            'display_alias' => 'Reporter',
        ]);

        $raw = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame('Jane Doe', $raw->name);
        $this->assertNotSame('0601020304', $raw->phone);
        $this->assertNotSame('12 rue de la Paix', $raw->address);
        $this->assertNotSame('Paris', $raw->city);
        $this->assertNotSame('75001', $raw->cp);
        $this->assertNotSame('France', $raw->country);
        $this->assertNotSame('28', (string) $raw->age);
        $this->assertNotSame('Reporter', $raw->display_alias);

        $user->refresh();

        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame('0601020304', $user->phone);
        $this->assertSame('12 rue de la Paix', $user->address);
        $this->assertSame('Paris', $user->city);
        $this->assertSame('75001', $user->cp);
        $this->assertSame('France', $user->country);
        $this->assertSame(28, $user->age);
        $this->assertSame('Reporter', $user->display_alias);
    }

    public function test_rgpd_notes_and_oauth_tokens_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $request = DataErasureRequest::create([
            'user_id' => $user->id,
            'request_type' => 'data_deletion',
            'details' => 'Merci de supprimer mes donnees.',
            'status' => 'pending',
            'admin_notes' => 'Demande recue.',
        ]);

        $oauthAccount = OauthAccount::create([
            'user_id' => $user->id,
            'provider' => 'discord',
            'provider_user_id' => '12345',
            'access_token' => 'access-secret',
            'refresh_token' => 'refresh-secret',
        ]);

        $rawRequest = DB::table('data_erasure_requests')->where('id', $request->id)->first();
        $rawOauth = DB::table('oauth_accounts')->where('id', $oauthAccount->id)->first();

        $this->assertNotSame('Merci de supprimer mes donnees.', $rawRequest->details);
        $this->assertNotSame('Demande recue.', $rawRequest->admin_notes);
        $this->assertNotSame('access-secret', $rawOauth->access_token);
        $this->assertNotSame('refresh-secret', $rawOauth->refresh_token);

        $this->assertSame('Merci de supprimer mes donnees.', $request->refresh()->details);
        $this->assertSame('Demande recue.', $request->admin_notes);
        $this->assertSame('access-secret', $oauthAccount->refresh()->access_token);
        $this->assertSame('refresh-secret', $oauthAccount->refresh_token);
    }
}
