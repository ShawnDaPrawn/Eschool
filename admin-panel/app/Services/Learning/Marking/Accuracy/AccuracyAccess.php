<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

use App\Models\User;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Who reaches the marking accuracy gate (Action Plan 3.18), checked by every
 * service method after the route and the FormRequest. Only platform staff,
 * only from HQ (never inside a school):
 *
 * - marking.evaluate (Content, Owner): import evaluation papers and scripts,
 *   run evaluations, read runs, switch a subject off.
 * - marking.approve (Owner only by default): switch a subject on.
 */
class AccuracyAccess
{
    public const EVALUATE = 'marking.evaluate';

    public const APPROVE = 'marking.approve';

    /** The signed-in staff member holding one of the abilities; 403 for anyone else or inside a school. */
    public function hq(?User $user, string ...$abilities): User
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403, 'The marking accuracy gate is managed only from HQ.');
        abort_unless($this->can($user, ...$abilities), 403);

        return app(PlatformStaffAccess::class)->actor($user);
    }

    public function can(?User $user, string ...$abilities): bool
    {
        if (app(CurrentSchool::class)->isSet()) {
            return false;
        }
        $access = app(PlatformStaffAccess::class);
        foreach ($abilities as $ability) {
            if ($access->can($user, $ability)) {
                return true;
            }
        }

        return false;
    }

    /** A subject the gate covers (config marking.gate.subjects); 404 for anything else. */
    public static function subject(string $subject): string
    {
        abort_unless(in_array($subject, self::subjects(), true), 404);

        return $subject;
    }

    /** @return list<string> */
    public static function subjects(): array
    {
        return array_values(array_filter((array) config('marking.gate.subjects', []), fn ($s) => is_string($s) && $s !== 'generic'));
    }
}
