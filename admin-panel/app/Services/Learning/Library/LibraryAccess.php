<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\User;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Who may work on the platform content library (Action Plan 3.3, SA.16):
 * platform staff whose role holds library.manage (Content, Owner) or
 * library.review (Content reviewer, Owner), never from inside a school.
 * Checked again by every service method, after the route and the FormRequest.
 */
class LibraryAccess
{
    public const MANAGE = 'library.manage';

    public const REVIEW = 'library.review';

    public function actor(?User $user, string $ability): User
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403, 'The platform library is managed only from HQ.');
        abort_unless(app(PlatformStaffAccess::class)->can($user, $ability), 403);

        return app(PlatformStaffAccess::class)->actor($user);
    }

    public function can(?User $user, string $ability): bool
    {
        return ! app(CurrentSchool::class)->isSet() && app(PlatformStaffAccess::class)->can($user, $ability);
    }
}
