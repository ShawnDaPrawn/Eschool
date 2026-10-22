<?php

declare(strict_types=1);

namespace App\Services\Platform\Context;

use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\SchoolProfile;
use Illuminate\Http\Request;

/**
 * What every /me/context contributor works from: the signed-in user, the
 * school the request works in (school.context chose it) and that school's
 * profile, built once per request.
 */
final readonly class ContextScope
{
    public function __construct(
        public User $user,
        public School $school,
        public SchoolProfile $profile,
        public Request $request,
    ) {}
}
