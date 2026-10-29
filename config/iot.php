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
        'quarantine_temperature_delta' => (float) env('IOT_QUARANTINE_TEMPERATURE_DELTA', 10),
        'quarantine_humidity_delta' => (float) env('IOT_QUARANTINE_HUMIDITY_DELTA', 40),
        'quarantine_max_gap_seconds' => (int) env('IOT_QUARANTINE_MAX_GAP_SECONDS', 120),
    ],

    'display' => [
        'safe_temperature_min' => (float) env('IOT_SAFE_TEMPERATURE_MIN', 5),
        'safe_temperature_max' => (float) env('IOT_SAFE_TEMPERATURE_MAX', 35),
    ],

    'ai' => [
        'enabled' => (bool) env('IOT_AI_ENABLED', false),
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('IOT_AI_MODEL', env('OPENAI_MODEL', 'gpt-5.4-mini')),
        'reasoning_effort' => env('IOT_AI_REASONING_EFFORT', 'low'),
        'max_output_tokens' => (int) env('IOT_AI_MAX_OUTPUT_TOKENS', 1_000),
        'timeout_seconds' => (int) env('IOT_AI_TIMEOUT_SECONDS', 60),
        'history_size' => (int) env('IOT_AI_HISTORY_SIZE', 10),
        'minimum_history_size' => (int) env('IOT_AI_MIN_HISTORY_SIZE', 3),
        'periodic_interval_minutes' => (int) env('IOT_AI_PERIODIC_INTERVAL_MINUTES', 15),
        'min_seconds_between_analyses' => (int) env('IOT_AI_MIN_SECONDS_BETWEEN_ANALYSES', 60),
        'trigger_on_threshold_crossing' => (bool) env('IOT_AI_TRIGGER_ON_THRESHOLD', true),
        'temperature_spike_delta' => (float) env('IOT_AI_TEMPERATURE_SPIKE_DELTA', 5),
        'humidity_spike_delta' => (float) env('IOT_AI_HUMIDITY_SPIKE_DELTA', 25),
        'anomaly_deviation' => (float) env('IOT_AI_ANOMALY_DEVIATION', 8),
    ],
];
