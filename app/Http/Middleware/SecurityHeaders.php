<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldApplySecurityHeaders()) {
            return $response;
        }

        $this->addStrictTransportSecurityHeader($request, $response);
        $this->addFrameOptionsHeader($response);
        $this->addContentTypeOptionsHeader($response);
        $this->addReferrerPolicyHeader($response);
        $this->addContentSecurityPolicyHeader($response);

        return $response;
    }

    protected function shouldApplySecurityHeaders(): bool
    {
        $environments = config('security.environments', []);

        if (empty($environments)) {
            return true;
        }

        return app()->environment($environments);
    }

    protected function addStrictTransportSecurityHeader(Request $request, Response $response): void
    {
        $config = config('security.hsts');

        if (!($config['enabled'] ?? true)) {
            return;
        }

        $parts = [sprintf('max-age=%d', $config['max_age'] ?? 0)];

        if ($config['include_subdomains'] ?? false) {
            $parts[] = 'includeSubDomains';
        }

        if ($config['preload'] ?? false) {
            $parts[] = 'preload';
        }

        $response->headers->set('Strict-Transport-Security', implode('; ', $parts));
    }

    protected function addFrameOptionsHeader(Response $response): void
    {
        $value = config('security.x_frame_options', 'SAMEORIGIN');

        $response->headers->set('X-Frame-Options', $value);
    }

    protected function addContentTypeOptionsHeader(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
    }

    protected function addReferrerPolicyHeader(Response $response): void
    {
        $response->headers->set('Referrer-Policy', config('security.referrer_policy', 'strict-origin-when-cross-origin'));
    }

    protected function addContentSecurityPolicyHeader(Response $response): void
    {
        $csp = config('security.csp', []);

        if (empty($csp)) {
            return;
        }

        $directives = [];

        foreach ($csp as $directive => $values) {
            if (empty($values)) {
                continue;
            }

            $directives[] = trim(sprintf('%s %s', $directive, implode(' ', array_unique($values))));
        }

        if (!empty($directives)) {
            $response->headers->set('Content-Security-Policy', implode('; ', $directives));
        }
    }
}
