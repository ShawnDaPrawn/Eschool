<?php

// Offline transcription is unchanged; inference has no active production feature/provider.
return [
    // New priced inference receipts use this server-owned quote currency.
    'billing_currency' => 'USD',
    'quarantine' => ['retention_days' => 30, 'participants' => []],
    'providers' => [
        'openai' => ['qualified' => false, 'key' => env('AI_OPENAI_API_KEY'), 'moderation_model' => 'omni-moderation-2024-09-26'],
        'anthropic' => ['qualified' => false, 'key' => env('AI_ANTHROPIC_API_KEY')],
        'deepseek' => ['qualified' => false, 'key' => env('AI_DEEPSEEK_API_KEY')],
    ],
    'evaluation' => [
        'minimum' => ['refusal' => 10000, 'subject_lock' => 10000, 'safety' => 10000, 'leakage' => 10000],
    ],
    'inference' => [
        'enabled' => false,
        'features' => [],
        // Server-owned feature => InferenceFeaturePolicy class allowlist; never a client setting.
        'feature_policies' => [],
        // Only explicit feature-owned resolvers may load approved private derivatives.
        'vision_resolvers' => [],
    ],
    'transcribe' => [
        'provider' => 'local-whisper-base',
        'maximum_seconds' => 1800,
        'daily_school_seconds' => 3600,
    ],
];
