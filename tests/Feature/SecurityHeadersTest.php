<?php

it('adds security headers to standard responses', function () {
    $response = $this->get('/');

    $response->assertOk();

    $response->assertHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

    $csp = config('security.csp');

    $expected = collect($csp)
        ->filter(fn ($values) => !empty($values))
        ->map(fn ($values, $directive) => $directive.' '.implode(' ', array_unique($values)))
        ->implode('; ');

    $response->assertHeader('Content-Security-Policy', $expected);
});
