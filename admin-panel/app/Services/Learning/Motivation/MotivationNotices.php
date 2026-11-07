<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Growth\MasteryGapClosure;
use App\Models\Learning\PracticeGoalWeek;
use App\Models\Learning\PracticeNudge;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Growth\GapClosures;
use App\Services\Learning\Digests\DigestAuthority;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Inbox and delivery scope for the Action Plan 3.13 notices. A goal
 * celebration is only ever the learner's own; a nudge only its recipient's;
 * a "now secure" celebration the learner's and their current guardians'.
 * Each shows only while the learner is still current and guardian-approved,
 * the person still has that relationship, and the module is on.
 */
class MotivationNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    private const SOURCES = [
        PracticeGoals::TYPE => [PracticeGoals::LINK, 'daily_practice'],
        PracticeNudges::TYPE => [PracticeNudges::LINK, 'daily_practice'],
        GapClosures::TYPE => [GapClosures::LINK, 'mastery'],
    ];

    public function apply(Builder $notifications, User $actor): void
    {
        $notifications->where(function (Builder $types) use ($actor) {
            $types->whereRaw('1 = 0');
            foreach (array_keys(self::SOURCES) as $type) {
                $source = $this->source($actor, $type);
                if ($source !== null) {
                    $types->orWhere(fn (Builder $branch) => $branch->where('type', $type)->whereIn('id', $source->whereNotNull('notification_id')->select('notification_id')));
                }
            }
        });
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        $id = $payload['motivation_id'] ?? null;
        if (! isset(self::SOURCES[$type]) || ($payload['link'] ?? null) !== self::SOURCES[$type][0] || (! is_int($id) && ! is_string($id)) || preg_match('/^[1-9][0-9]{0,18}$/D', (string) $id) !== 1) {
            return false;
        }
        try {
            return $this->source($actor, $type)?->whereKey((int) $id)->exists() ?? false;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** The ledger rows behind this type that the actor may still see, or null for none. */
    private function source(User $actor, string $type): ?Builder
    {
        $school = app(CurrentSchool::class);
        if (! $school->isSet() || $school->isAcrossSchools() || $school->isImpersonating()) {
            return null;
        }
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows(self::SOURCES[$type][1])) {
            return null;
        }
        [$own, $children] = $this->learners($actor);
        if ($type === PracticeGoals::TYPE) {
            $own = array_values(array_filter($own, fn (Students $l) => app(MotivationAuthority::class)->stageAllows($l)));

            return $own === [] ? null : PracticeGoalWeek::query()->where('user_id', $actor->id)->whereIn('learner_id', array_map(fn ($l) => (int) $l->id, $own));
        }
        $ids = array_map(fn (Students $l) => (int) $l->id, [...$own, ...$children]);
        if ($ids === []) {
            return null;
        }
        if ($type === PracticeNudges::TYPE) {
            return PracticeNudge::query()->where('user_id', $actor->id)->whereIn('learner_id', $ids);
        }

        return MasteryGapClosure::query()->whereIn('learner_id', $ids);
    }

    /**
     * The actor's own current learner records, and the current learners they are a guardian of.
     *
     * @return array{0: list<Students>, 1: list<Students>}
     */
    private function learners(User $actor): array
    {
        $fresh = app(ProfilePolicy::class)->actor(User::findOrFail($actor->id));
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $authority = app(DigestAuthority::class);
        $own = [];
        if (in_array('Student', $roles, true)) {
            foreach (Students::where('user_id', $fresh->id)->limit(5)->get() as $learner) {
                if ($authority->currentLearner($learner)) {
                    $own[] = $learner;
                }
            }
        }
        $children = [];
        if (in_array('Parent', $roles, true)) {
            $parents = Parents::where('user_id', $fresh->id)->pluck('id')->all();
            $candidates = Students::where(function (Builder $q) use ($fresh, $parents): void {
                $q->whereIn('id', GuardianLink::where('guardian_user_id', $fresh->id)->where('status', 'active')->select('learner_id'));
                if ($parents !== []) {
                    $q->orWhereIn('father_id', $parents)->orWhereIn('mother_id', $parents)->orWhereIn('guardian_id', $parents);
                }
            })->orderBy('id')->limit(50)->get();
            foreach ($candidates as $learner) {
                if ($authority->currentLearner($learner) && app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id)) {
                    $children[] = $learner;
                }
            }
        }

        return [$own, $children];
    }
}
