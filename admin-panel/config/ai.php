<?php

use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Learning\TutorFeaturePolicy;
use App\Services\Learning\TutorModeration;
use App\Services\Learning\TutorQuarantineMedia;
use App\Services\Learning\TutorQuarantineParticipant;
use App\Services\Learning\TutorVisionResolver;

// Offline transcription is unchanged; inference has no active production feature/provider.
return [
    'quarantine' => ['retention_days' => 30, 'participants' => ['tutor' => TutorQuarantineParticipant::class],
        'media_resolvers' => ['tutor' => TutorQuarantineMedia::class]],
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
        'features' => [
            'tutor' => ['enabled' => false, 'roles' => ['Student'], 'capabilities' => ['complete', 'vision'],
                'provider' => 'openai', 'provider_class' => OpenAiProvider::class,
                'moderator' => TutorModeration::class, 'model' => env('AI_TUTOR_MODEL', ''), 'prompt' => 'tutor',
                'maximum_output_tokens' => 768, 'cents_per_million_tokens' => null,
                'school_daily_tokens' => 1000000, 'actor_daily_tokens' => 50000, 'learner_daily_tokens' => 50000,
                'school_daily_cents' => 1000, 'actor_daily_cents' => 50, 'learner_daily_cents' => 50],
        ],
        // Server-owned feature => InferenceFeaturePolicy class allowlist; never a client setting.
        'feature_policies' => ['tutor' => TutorFeaturePolicy::class],
        // Only explicit feature-owned resolvers may load approved private derivatives.
        'vision_resolvers' => ['tutor' => TutorVisionResolver::class],
    ],
    'transcribe' => [
        'provider' => 'local-whisper-base',
        'maximum_seconds' => 1800,
        'daily_school_seconds' => 3600,
    ],
];
