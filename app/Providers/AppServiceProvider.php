<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event; // ✅ le bon facade
use App\Services\Translator;
use App\Services\DeeplTranslator;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Discord\DiscordExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Translator::class, fn () => DeeplTranslator::fromEnv());
    }

    public function boot(): void
    {
        // Enregistre le driver "discord" pour Socialite
        Event::listen(SocialiteWasCalled::class, [DiscordExtendSocialite::class, 'handle']);
    }
}
