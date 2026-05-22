<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OauthAccount;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $scopes = match ($provider) {
            'google'  => ['openid','email','profile'],
            'discord' => ['identify','email'], // email requis car users.email est NOT NULL + unique
            default   => []
        };

        return Socialite::driver($provider)->scopes($scopes)->redirect();
    }

    public function callback(string $provider)
    {
        $socialUser = Socialite::driver($provider)->stateless()->user();

        $providerUserId = $socialUser->getId();
        $email          = $socialUser->getEmail(); // Discord: nécessite le scope "email"
        $name           = $socialUser->getName() ?? $socialUser->getNickname() ?? 'Utilisateur';

        if (! $email) {
            return redirect()->route('login')->with('error', "Autorise l'accès à ton email $provider pour continuer.");
        }

        $accessToken  = $socialUser->token ?? null;
        $refreshToken = $socialUser->refreshToken ?? null;
        $expiresIn    = $socialUser->expiresIn ?? null;

        $user = DB::transaction(function () use ($provider,$providerUserId,$email,$name,$accessToken,$refreshToken,$expiresIn) {
            // 1) déjà lié ?
            $oauth = OauthAccount::where('provider',$provider)
                ->where('provider_user_id',$providerUserId)
                ->first();

            if ($oauth) {
                $oauth->update([
                    'access_token'  => $accessToken,
                    'refresh_token' => $refreshToken,
                    'expires_at'    => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
                ]);
                $user = $oauth->user;
            } else {
                // 2) sinon par email
                $user = User::where('email',$email)->first();

                if (! $user) {
                    // 3) création minimale (age=0 car NOT NULL dans ta table)
                    $user = User::create([
                        'name'     => $name,
                        'username' => $this->makeUsernameFrom($name),
                        'email'    => $email,
                        'password' => bcrypt(Str::random(32)), // placeholder
                        'age'      => 0,
                    ]);
                }

                // 4) lien OAuth
                $user->oauthAccounts()->create([
                    'provider'         => $provider,
                    'provider_user_id' => $providerUserId,
                    'access_token'     => $accessToken,
                    'refresh_token'    => $refreshToken,
                    'expires_at'       => $expiresIn ? Carbon::now()->addSeconds($expiresIn) : null,
                ]);
            }

            return $user;
        });

        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    private function makeUsernameFrom(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'user';
        $candidate = $base;
        $i = 1;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $base . '_' . $i++;
        }
        return $candidate;
    }
}
