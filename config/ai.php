<?php

return [
    'mock_without_credentials' => env('AI_MOCK_WITHOUT_CREDENTIALS', true),
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'mock'),
    'default_model' => env('AI_DEFAULT_MODEL', 'mock-coach-v1'),
    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 30),
];
