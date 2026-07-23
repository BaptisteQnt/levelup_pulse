<?php

return [
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('ARTICLE_AI_MODEL', 'gpt-5.6-terra'),
        'reasoning_effort' => env('ARTICLE_AI_REASONING_EFFORT', 'low'),
        'trends_max_output_tokens' => (int) env('ARTICLE_AI_TRENDS_MAX_OUTPUT_TOKENS', 1_800),
        'correction_max_output_tokens' => (int) env('ARTICLE_AI_CORRECTION_MAX_OUTPUT_TOKENS', 12_000),
        'timeout_seconds' => (int) env('ARTICLE_AI_TIMEOUT_SECONDS', 90),
    ],
];
