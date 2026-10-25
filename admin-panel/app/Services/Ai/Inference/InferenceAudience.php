<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

enum InferenceAudience: string
{
    case Staff = 'staff';
    case Family = 'family';
}
