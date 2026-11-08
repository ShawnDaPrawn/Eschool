<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Profiles;

use App\Services\Learning\Marking\SubjectProfile;

/** Any other subject: only the memo's explicit rules, read literally; anything else goes to a teacher. */
final class GenericProfile extends SubjectProfile
{
    public function key(): string
    {
        return 'generic';
    }

    public function label(): string
    {
        return __('Other subjects');
    }

    public function conventions(): array
    {
        return [
            __('Only the memo\'s rules are used. An expression must be written as in the memo (spaces aside).'),
            __('Consistent accuracy marks after an earlier mistake go to a teacher.'),
        ];
    }
}
