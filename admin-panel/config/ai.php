<?php

use App\Services\Ai\Providers\OpenAiProvider;
use App\Services\Assistance\AssistanceModeration;
use App\Services\Assistance\AssistanceQuarantine;
use App\Services\Assistance\AssistantFeaturePolicy;
use App\Services\Assistance\TranslationFeaturePolicy;
use App\Services\Learning\ExamAnswerFeaturePolicy;
use App\Services\Learning\ExamAnswerModeration;
use App\Services\Learning\ExamAnswerQuarantine;
use App\Services\Learning\ExamAnswerQuarantineMedia;
use App\Services\Learning\ExamAnswerVisionResolver;
use App\Services\Learning\TeachingDraftFeaturePolicy;
use App\Services\Learning\TeachingDraftModerator;
use App\Services\Learning\TeachingDraftQuarantineMedia;
use App\Services\Learning\TeachingDraftQuarantineParticipant;
use App\Services\Learning\TeachingDraftVisionResolver;
use App\Services\Learning\TutorFeaturePolicy;
use App\Services\Learning\TutorModeration;
use App\Services\Learning\TutorQuarantineMedia;
use App\Services\Learning\TutorQuarantineParticipant;
use App\Services\Learning\TutorVisionResolver;

// Offline transcription is unchanged; inference has no active production feature/provider.
return [
    // New priced inference receipts use this server-owned quote currency.
    'billing_currency' => 'USD',
    'quarantine' => ['retention_days' => 30, 'participants' => ['school_assistant' => AssistanceQuarantine::class, 'message_translation' => AssistanceQuarantine::class, 'teaching_assistant' => TeachingDraftQuarantineParticipant::class, 'exam_coach' => ExamAnswerQuarantine::class, 'tutor' => TutorQuarantineParticipant::class],
        'media_resolvers' => ['teaching_assistant' => TeachingDraftQuarantineMedia::class, 'exam_coach' => ExamAnswerQuarantineMedia::class, 'tutor' => TutorQuarantineMedia::class]],
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
            'school_assistant' => ['enabled' => false, 'roles' => ['Parent'], 'capabilities' => ['complete'],
                'provider' => 'openai', 'provider_class' => OpenAiProvider::class, 'moderator' => AssistanceModeration::class,
                'model' => env('AI_ASSISTANCE_MODEL', ''), 'prompt' => 'school-assistant',
                'maximum_output_tokens' => 1536, 'cents_per_million_tokens' => null, 'school_daily_tokens' => 1000000, 'actor_daily_tokens' => 50000, 'learner_daily_tokens' => 50000,
                'school_daily_cents' => 1000, 'actor_daily_cents' => 50, 'learner_daily_cents' => 50],
            'message_translation' => ['enabled' => false, 'roles' => ['Admin', 'Parent', 'Teacher', 'Super Admin', 'Counsellor', 'Safeguarding', 'Facilitator'], 'capabilities' => ['complete'],
                'provider' => 'openai', 'provider_class' => OpenAiProvider::class, 'moderator' => AssistanceModeration::class,
                'model' => env('AI_ASSISTANCE_MODEL', ''), 'prompt' => 'message-translation',
                'maximum_output_tokens' => 1536, 'cents_per_million_tokens' => null, 'school_daily_tokens' => 1000000, 'actor_daily_tokens' => 50000, 'learner_daily_tokens' => 50000,
                'school_daily_cents' => 1000, 'actor_daily_cents' => 50, 'learner_daily_cents' => 50],
            'exam_coach' => ['enabled' => false, 'roles' => ['Student'], 'capabilities' => ['vision'],
                'provider' => 'openai', 'provider_class' => OpenAiProvider::class,
                'moderator' => ExamAnswerModeration::class, 'model' => env('AI_EXAM_COACH_MODEL', ''), 'prompt' => 'exam_coach',
                'maximum_output_tokens' => 768, 'cents_per_million_tokens' => null,
                'school_daily_tokens' => 1000000, 'actor_daily_tokens' => 50000, 'learner_daily_tokens' => 50000,
                'school_daily_cents' => 1000, 'actor_daily_cents' => 50, 'learner_daily_cents' => 50],
            'teaching_assistant' => ['enabled' => false, 'roles' => ['Teacher'], 'capabilities' => ['complete', 'vision'],
                'provider' => 'openai', 'provider_class' => OpenAiProvider::class, 'moderator' => TeachingDraftModerator::class,
                'model' => env('AI_TEACHING_ASSISTANT_MODEL', ''), 'prompt' => 'teaching-assistant', 'maximum_output_tokens' => 4096,
                'cents_per_million_tokens' => null, 'school_daily_tokens' => 1000000, 'actor_daily_tokens' => 100000, 'learner_daily_tokens' => 50000,
                'school_daily_cents' => 1000, 'actor_daily_cents' => 100, 'learner_daily_cents' => 50],
            'tutor' => ['enabled' => false, 'roles' => ['Student'], 'capabilities' => ['complete', 'vision'],
                'provider' => 'openai', 'provider_class' => OpenAiProvider::class,
                'moderator' => TutorModeration::class, 'model' => env('AI_TUTOR_MODEL', ''), 'prompt' => 'tutor',
                'maximum_output_tokens' => 768, 'cents_per_million_tokens' => null,
                'school_daily_tokens' => 1000000, 'actor_daily_tokens' => 50000, 'learner_daily_tokens' => 50000,
                'school_daily_cents' => 1000, 'actor_daily_cents' => 50, 'learner_daily_cents' => 50],
        ],
        // Server-owned feature => InferenceFeaturePolicy class allowlist; never a client setting.
        'staff_actor_policies' => ['message_translation' => TranslationFeaturePolicy::class],
        'feature_policies' => ['school_assistant' => AssistantFeaturePolicy::class, 'message_translation' => TranslationFeaturePolicy::class, 'teaching_assistant' => TeachingDraftFeaturePolicy::class, 'exam_coach' => ExamAnswerFeaturePolicy::class, 'tutor' => TutorFeaturePolicy::class],
        // Only explicit feature-owned resolvers may load approved private derivatives.
        'vision_resolvers' => ['teaching_assistant' => TeachingDraftVisionResolver::class, 'exam_coach' => ExamAnswerVisionResolver::class, 'tutor' => TutorVisionResolver::class],
    ],
    'transcribe' => [
        'provider' => 'local-whisper-base',
        'maximum_seconds' => 1800,
        'daily_school_seconds' => 3600,
    ],
];
