<?php

declare(strict_types=1);

namespace App\Services\ChildMode;

use App\Http\Middleware\ChildModeContext;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Stages\SchoolStages;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Who may use child mode in the parent app, for which child, and what the
 * child may open there (Action Plan 4.2).
 *
 * - Child mode is for the junior stage (SchoolStages, Action Plan 4.1).
 *   Early years has no child mode on personal devices; high school learners
 *   use their own sign-in. A learner whose homeroom has no grade gets none,
 *   because the early-years rule could not be applied.
 * - Only an approving guardian (an active guardian link with the approval
 *   right, an active account and a verified email) sets the PIN or starts a
 *   child session, and only while the child holds a current guardian
 *   approval and has not left the school.
 * - A child may open "my day" and the reading library, practice from Grade
 *   3 and the tutor from Grade 4, each only while the school has that
 *   module switched on for the child's stage. Never chat, messages, other
 *   learners' marks, rankings or anything else.
 *
 * The child token (AppTokens::CHILD) belongs to the guardian's account but
 * is bound to one child and one school by its abilities. It authenticates
 * only on the child mode routes (allowedHere, called by Sanctum for every
 * token); everywhere else it is no token at all.
 */
final class ChildModeAccess
{
    public const MY_DAY = 'my_day';

    public const PRACTICE = 'practice';

    public const TUTOR = 'tutor';

    public const LIBRARY = 'library';

    /** What a child may open, in the order the app shows it, with the module behind each (null: always there). */
    public const OPENS = [self::MY_DAY => null, self::PRACTICE => 'daily_practice', self::TUTOR => 'tutor', self::LIBRARY => 'reading'];

    /** The first grade (Grade R is 0) for which each may be opened. */
    public const FROM_GRADE = [self::MY_DAY => 0, self::PRACTICE => 3, self::TUTOR => 4, self::LIBRARY => 0];

    /** Why child mode is closed, with the message the guardian sees. */
    public const REASONS = [
        'not_approving' => 'child_mode_not_approving',
        'guardian_unverified' => 'child_mode_guardian_unverified',
        'approval_needed' => 'child_mode_approval_needed',
        'early_years' => 'child_mode_early_years',
        'high_school' => 'child_mode_high_school',
        'grade_unknown' => 'child_mode_grade_unknown',
    ];

    private const SCHOOL = 'child-mode:school:';

    private const LEARNER = 'child-mode:learner:';

    /** The abilities that bind a child token to one school and one child. */
    public static function abilities(int $schoolId, int $learnerId): array
    {
        return [self::SCHOOL.$schoolId, self::LEARNER.$learnerId];
    }

    /**
     * Sanctum's check for every token: a child token authenticates only on a
     * route behind ChildModeContext. Any other token is unaffected.
     */
    public static function allowedHere(PersonalAccessToken $token): bool
    {
        if ($token->name !== AppTokens::CHILD) {
            return true;
        }
        $route = app()->bound('request') ? request()->route() : null;

        return $route instanceof Route && in_array(ChildModeContext::class, $route->gatherMiddleware(), true);
    }

    /** @return array{school: int, learner: int}|null the school and child a child token is bound to */
    public function binding(Request $request): ?array
    {
        $token = $request->user()?->currentAccessToken();
        if (! $token instanceof PersonalAccessToken || $token->name !== AppTokens::CHILD) {
            return null;
        }
        $found = [];
        foreach ((array) $token->abilities as $ability) {
            foreach (['school' => self::SCHOOL, 'learner' => self::LEARNER] as $key => $prefix) {
                if (is_string($ability) && preg_match('/^'.preg_quote($prefix, '/').'([1-9][0-9]{0,17})$/D', $ability, $match) === 1) {
                    $found[$key] = (int) $match[1];
                }
            }
        }

        return isset($found['school'], $found['learner']) ? $found : null;
    }

    /**
     * The guardian's child at the current school: 404 for a learner who is not
     * theirs (another family's child, another school's, or one whose access
     * has ended), so nothing about them is revealed.
     */
    public function child(User $guardian, int $learnerId): Students
    {
        $learner = Students::query()->find($learnerId);
        abort_if($learner === null || app(LeaverState::class)->accessEnded($learner), 404);
        abort_unless(GuardianLink::query()->where('learner_id', $learner->id)->where('guardian_user_id', $guardian->id)->where('status', 'active')->exists(), 404);

        return $learner;
    }

    /** Why this guardian cannot use child mode for this child now, or null when they can. */
    public function refusal(User $guardian, Students $learner): ?string
    {
        $fresh = User::query()->whereKey($guardian->id)->first();
        if ($fresh === null || (int) $fresh->status !== 1 || $fresh->email_verified_at === null) {
            return 'guardian_unverified';
        }
        $approving = GuardianLink::query()->where('learner_id', $learner->id)->where('guardian_user_id', $guardian->id)
            ->where('status', 'active')->where('can_approve', true)->exists();
        if (! $approving) {
            return 'not_approving';
        }
        if (app(LeaverState::class)->accessEnded($learner) || ! app(ApprovalService::class)->approved($learner)) {
            return 'approval_needed';
        }

        return match (app(SchoolStages::class)->forLearner($learner)) {
            SchoolStages::JUNIOR => null,
            SchoolStages::EARLY_YEARS => 'early_years',
            SchoolStages::HIGH_SCHOOL => 'high_school',
            default => 'grade_unknown',
        };
    }

    /** Refuses with the reason's message unless child mode is open to this guardian for this child. */
    public function assertOpen(User $guardian, Students $learner): void
    {
        $reason = $this->refusal($guardian, $learner);
        abort_if($reason !== null, 403, __(self::REASONS[(string) $reason] ?? 'child_mode_closed'));
    }

    /**
     * What this child may open now, read fresh: their grade, the school's
     * modules and each module's stages.
     *
     * @return list<string>
     */
    public function opens(Students $learner): array
    {
        $stages = app(SchoolStages::class);
        $stage = $stages->forLearner($learner);
        $grade = $stages->gradeNumber($learner);
        if ($stage !== SchoolStages::JUNIOR || $grade === null) {
            return [];
        }
        $gate = app(ModuleGate::class);
        $gate->forget();
        $opens = [];
        foreach (self::OPENS as $key => $module) {
            if ($grade < self::FROM_GRADE[$key]) {
                continue;
            }
            if ($module !== null && (! $gate->state($module)['enabled'] || ! SchoolStages::moduleAllows($module, $stage))) {
                continue;
            }
            $opens[] = $key;
        }

        return $opens;
    }

    /** The child token's lifetime, in minutes. */
    public static function minutes(): int
    {
        return max(5, min(120, (int) config('identity.child_mode_minutes', 30)));
    }
}
