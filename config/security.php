<?php

return [
    'environments' => [
        'production',
        'testing',
    ],

    'hsts' => [
        'enabled' => true,
        'max_age' => 63_072_000,
        'include_subdomains' => true,
        'preload' => true,
    ],

    'x_frame_options' => 'SAMEORIGIN',

    'referrer_policy' => 'strict-origin-when-cross-origin',

    'csp' => (function () {
        $devHostnames = [
            'localhost',
            '127.0.0.1',
            '[::1]',
        ];

        $devPorts = [5173];

        $devSchemes = ['http', 'https'];

        $devScriptSources = [];

        foreach ($devHostnames as $host) {
            foreach ($devPorts as $port) {
                foreach ($devSchemes as $scheme) {
                    $devScriptSources[] = sprintf('%s://%s:%d', $scheme, $host, $port);
                }
            }
        }

        $devConnectSources = $devScriptSources;

        foreach ($devScriptSources as $source) {
            $devConnectSources[] = str_starts_with($source, 'https://')
                ? preg_replace('/^https/', 'wss', $source, 1)
                : preg_replace('/^http/', 'ws', $source, 1);
        }

        $scriptSources = array_merge([
            "'self'",
            "'unsafe-inline'",
            'https://js.stripe.com',
            'https://*.stripe.com',
            'https://*.deepl.com',
            'https://*.deeplapi.com',
        ], $devScriptSources);

        $styleSources = array_merge([
            "'self'",
            "'unsafe-inline'",
            'https://fonts.bunny.net',
        ], $devScriptSources);


        return [
            'default-src' => [
                "'self'",
            ],
            'script-src' => $scriptSources,
            'script-src-elem' => $scriptSources,
            'style-src' => $styleSources,

            'font-src' => [
                "'self'",
                'https://fonts.bunny.net',
            ],
            'frame-src' => [
                "'self'",
                'https://js.stripe.com',
                'https://hooks.stripe.com',
                'https://checkout.stripe.com',
            ],
            'connect-src' => array_merge([
                "'self'",
            ], $devConnectSources),

        ];
    })(),
];
