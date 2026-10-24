<?php

// Only Phase 2's offline transcription adapter is enabled. No external provider keys.
return [
    'transcribe' => [
        'provider' => 'local-whisper-base',
        'maximum_seconds' => 1800,
        'daily_school_seconds' => 3600,
    ],
];
