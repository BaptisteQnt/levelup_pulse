<?php

namespace App\Providers;

use App\Contracts\CompatibilityAiProvider;
use App\Http\Middleware\AuthenticateIotDevice;
use App\Models\CompatibilityScan;
use App\Models\IotDevice;
use App\Services\DeeplTranslator;
use App\Services\OpenAiCompatibilityProvider;
use App\Services\Translator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Discord\DiscordExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Translator::class, fn () => DeeplTranslator::fromConfig());
        $this->app->bind(CompatibilityAiProvider::class, OpenAiCompatibilityProvider::class);
    }

    public function boot(): void
    {
        RateLimiter::for('compatibility-scan-create', function (Request $request) {
            return Limit::perHour(3)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('compatibility-scan-upload', function (Request $request) {
            $routeScan = $request->route('compatibilityScan');
            $scan = $routeScan instanceof CompatibilityScan
                ? (string) $routeScan->getRouteKey()
                : (string) $routeScan;

            return Limit::perMinutes(15, 5)->by($scan.'|'.$request->ip());
        });

        RateLimiter::for('article-ai-trends', function (Request $request) {
            return Limit::perHour(10)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('article-ai-corrections', function (Request $request) {
            return Limit::perHour(20)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('iot-telemetry-ip', function (Request $request) {
            return Limit::perMinute(max(1, (int) config('iot.telemetry.ip_rate_limit_per_minute', 120)))
                ->by('iot-ip:'.$request->ip());
        });

        RateLimiter::for('iot-telemetry-device', function (Request $request) {
            $device = $request->attributes->get(AuthenticateIotDevice::REQUEST_ATTRIBUTE);
            $deviceKey = $device instanceof IotDevice ? (string) $device->id : $request->ip();

            return Limit::perMinute(max(1, (int) config('iot.telemetry.rate_limit_per_minute', 30)))
                ->by('iot-device:'.$deviceKey);
        });

        // Enregistre le driver "discord" pour Socialite
        Event::listen(SocialiteWasCalled::class, [DiscordExtendSocialite::class, 'handle']);
    }
}
