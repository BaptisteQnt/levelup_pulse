<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

it('adds security headers to standard responses', function () {
    $response = $this->get('/');

    $response->assertOk();

    $response->assertHeaderMissing('Strict-Transport-Security');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $csp = config('security.csp');

    $expected = collect($csp)
        ->filter(fn ($values) => ! empty($values))
        ->map(fn ($values, $directive) => $directive.' '.implode(' ', array_unique($values)))
        ->implode('; ');

    $response->assertHeader('Content-Security-Policy', $expected);

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("img-src 'self' data: https://images.igdb.com");
});

it('adds HSTS to HTTPS responses in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $request = Request::create('https://example.test/');
    $response = (new SecurityHeaders)->handle($request, fn () => new Response);

    expect($response->headers->get('Strict-Transport-Security'))
        ->toBe('max-age=63072000; includeSubDomains; preload');
});

it('does not add HSTS to HTTP responses in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $request = Request::create('http://example.test/');
    $response = (new SecurityHeaders)->handle($request, fn () => new Response);

    expect($response->headers->has('Strict-Transport-Security'))->toBeFalse();
});
