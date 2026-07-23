<?php

return [
    'scan' => [
        'upload_ttl_minutes' => (int) env('COMPATIBILITY_SCAN_UPLOAD_TTL_MINUTES', 15),
        'retention_hours' => (int) env('COMPATIBILITY_SCAN_RETENTION_HOURS', 24),
        'max_payload_bytes' => (int) env('COMPATIBILITY_SCAN_MAX_PAYLOAD_BYTES', 65_536),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5.4-mini'),
        'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'low'),
        'max_output_tokens' => (int) env('OPENAI_MAX_OUTPUT_TOKENS', 2_000),
        'timeout_seconds' => (int) env('OPENAI_TIMEOUT_SECONDS', 90),
    ],
];
