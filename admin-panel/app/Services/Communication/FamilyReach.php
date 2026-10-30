<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\ClassSection;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/**
 * Family reach (Action Plan 2.6): how many current learners have a guardian
 * the school can actually reach, and which do not. A guardian reaches the
 * family by the parent app (a current sign-in) or by a verified email
 * address; an account that is switched off reaches nobody. Guardians are the
 * same people GuardianService::guardianUserIds() counts: active links, and
 * legacy parents who have no link row. Read-only, for the school office.
 * Specification: engineering/specs/communication/family-reach.md.
 */
class FamilyReach
{
    public const APP = 'app';

    public const EMAIL = 'email';

    public const UNREACHABLE = 'unreachable';

    public const NO_GUARDIAN = 'no_guardian';

    public function authorise(User $actor): void
    {
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_unless($fresh->hasRole('Super Admin') || (! $fresh->hasAnyRole(['Student', 'Parent', 'Teacher']) && $fresh->can('student-list')), 403);
    }

    /**
     * @return array{total: int, counts: array<string, int>, by_homeroom: list<array<string, mixed>>, unreached: list<array<string, mixed>>}
     */
    public function report(User $actor): array
    {
        $this->authorise($actor);
        $school = app(CurrentSchool::class)->id();
        $today = now(app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg')->toDateString();
        $learnerUsers = SchoolMembership::query()->where('school_id', $school)->where('role', 'Student')->where('status', SchoolMembership::ACTIVE)
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhere('starts_on', '<=', $today))->where(fn ($q) => $q->whereNull('ends_on')->orWhere('ends_on', '>=', $today))
            ->pluck('user_id');
        $learners = Students::query()->with('user')->whereIn('user_id', $learnerUsers)->whereHas('user', fn ($q) => $q->where('status', 1))->get();
        $guardians = $this->guardians($learners);
        $people = User::query()->whereIn('id', array_unique(array_merge([], ...array_values($guardians))))->get()->keyBy('id');
        // A sign-in is current only while Sanctum would still accept the token:
        // younger than the configured expiration and before its own expires_at.
        $expiration = config('sanctum.expiration');
        $withApp = DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('name', AppTokens::PARENT)
            ->whereIn('tokenable_id', $people->keys())
            ->when($expiration, fn ($q) => $q->where('created_at', '>', now()->subMinutes((int) $expiration)))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->distinct()->pluck('tokenable_id')->map(fn ($id) => (int) $id)->flip();
        $rooms = ClassSection::with('class', 'section')->whereIn('id', $learners->pluck('class_section_id')->filter()->unique())->get()
            ->mapWithKeys(fn ($room) => [(int) $room->id => trim(($room->class?->name ?? '').' '.($room->section?->name ?? ''))]);

        $counts = array_fill_keys([self::APP, self::EMAIL, self::UNREACHABLE, self::NO_GUARDIAN], 0);
        $byRoom = [];
        $unreached = [];
        foreach ($learners as $learner) {
            $ids = $guardians[(int) $learner->id] ?? [];
            $status = $ids === [] ? self::NO_GUARDIAN : self::UNREACHABLE;
            foreach ($ids as $id) {
                $person = $people->get($id);
                if ($person === null || (int) $person->status !== 1 || $person->trashed()) {
                    continue;
                }
                if (isset($withApp[$id])) {
                    $status = self::APP;
                    break;
                }
                if ($person->email_verified_at !== null && filter_var($person->email, FILTER_VALIDATE_EMAIL) !== false) {
                    $status = self::EMAIL;
                }
            }
            $counts[$status]++;
            $room = $rooms[(int) $learner->class_section_id] ?? __('No homeroom');
            $byRoom[$room] ??= ['homeroom' => $room, 'learners' => 0, 'reached' => 0];
            $byRoom[$room]['learners']++;
            if (in_array($status, [self::APP, self::EMAIL], true)) {
                $byRoom[$room]['reached']++;
            } else {
                $unreached[] = ['learner_id' => (int) $learner->id, 'name' => trim(($learner->user?->first_name ?? '').' '.($learner->user?->last_name ?? '')),
                    'homeroom' => $room, 'reason' => $status, 'guardians' => count($ids)];
            }
        }
        ksort($byRoom);
        usort($unreached, fn ($a, $b) => [$a['homeroom'], $a['name']] <=> [$b['homeroom'], $b['name']]);

        return ['total' => $learners->count(), 'counts' => $counts, 'by_homeroom' => array_values($byRoom), 'unreached' => $unreached];
    }

    /**
     * Guardian user ids per learner id, as GuardianService::guardianUserIds()
     * counts them, in two queries for the whole school.
     *
     * @return array<int, list<int>>
     */
    private function guardians($learners): array
    {
        $ids = $learners->pluck('id')->all();
        $links = GuardianLink::query()->whereIn('learner_id', $ids)->get(['learner_id', 'guardian_user_id', 'status']);
        $linked = [];
        $anyLink = [];
        foreach ($links as $link) {
            $anyLink[(int) $link->learner_id][(int) $link->guardian_user_id] = true;
            if ($link->status === 'active') {
                $linked[(int) $link->learner_id][] = (int) $link->guardian_user_id;
            }
        }
        $parentIds = $learners->flatMap(fn ($learner) => array_filter([$learner->father_id, $learner->mother_id, $learner->guardian_id]))->unique()->all();
        $parentUsers = Parents::query()->whereIn('id', $parentIds)->pluck('user_id', 'id');
        $result = [];
        foreach ($learners as $learner) {
            $own = $linked[(int) $learner->id] ?? [];
            foreach (array_filter([$learner->father_id, $learner->mother_id, $learner->guardian_id]) as $parentId) {
                $user = (int) ($parentUsers[$parentId] ?? 0);
                if ($user > 0 && ! isset($anyLink[(int) $learner->id][$user])) {
                    $own[] = $user;
                }
            }
            $result[(int) $learner->id] = array_values(array_unique($own));
        }

        return $result;
    }
}
