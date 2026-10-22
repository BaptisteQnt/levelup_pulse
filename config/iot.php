<?php

return [
    'token_bytes' => (int) env('IOT_TOKEN_BYTES', 32),

    'telemetry' => [
        'max_payload_bytes' => (int) env('IOT_TELEMETRY_MAX_PAYLOAD_BYTES', 4096),
        'rate_limit_per_minute' => (int) env('IOT_TELEMETRY_RATE_LIMIT_PER_MINUTE', 30),
        'ip_rate_limit_per_minute' => (int) env('IOT_TELEMETRY_IP_RATE_LIMIT_PER_MINUTE', 120),
        'temperature_min' => (float) env('IOT_TEMPERATURE_MIN', -40),
        'temperature_max' => (float) env('IOT_TEMPERATURE_MAX', 80),
        'humidity_min' => (float) env('IOT_HUMIDITY_MIN', 0),
        'humidity_max' => (float) env('IOT_HUMIDITY_MAX', 100),
    ],
];
