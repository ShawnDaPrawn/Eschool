<?php

declare(strict_types=1);

namespace App\Services\People\Pools;

use App\Models\Activities\ActivityAttendance;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivitySession;
use App\Models\Activities\ActivityStaff;
use App\Models\People\Pools\PoolBooking;
use App\Models\People\Pools\PoolMember;
use App\Models\Students;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Booking vetted pool members into activity group sessions as career talk
 * speakers or job-shadow hosts (Action Plan 5.6). A booking is always for a
 * group session: the session's group has at least
 * config('people_pools.minimum_learners') learners on the day and a school
 * staff member allocated to it. Nobody from the pool is ever booked
 * one-to-one with a learner.
 *
 * What the pool member sees of learners is the session's attendance list
 * (names and the recorded status, no ids, marks or contact details), and
 * only on the day of the session.
 */
final class PoolBookings
{
    public function __construct(private readonly PoolAccess $access, private readonly PoolOperations $operations, private readonly BookablePeople $people) {}

    public static function rules(string $action): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($action) {
            'book' => $operation + ['pool_member_id' => ['required', 'integer', 'min:1'], 'activity_session_id' => ['required', 'integer', 'min:1'], 'kind' => ['required', Rule::in(PoolBooking::KINDS)]],
            'cancel' => $operation + ['revision' => ['required', 'integer', 'min:0']],
            'bookings' => ['activity_session_id' => ['sometimes', 'integer', 'min:1']],
            default => [],
        };
    }

    private function groups(): void
    {
        abort_unless(app(ModuleGate::class)->allows('groups'), 403);
    }

    /** @return array<string, mixed> */
    public function book(User $actor, array $input): array
    {
        $authority = function () use ($actor) {
            $this->access->manager($actor, true);
            $this->groups();
        };
        $authority();
        $data = Validator::make($input, self::rules('book'))->validate();
        foreach (['pool_member_id', 'activity_session_id'] as $field) {
            $data[$field] = (int) $data[$field];
        }
        $receipt = $this->operations->once($actor, 'book', $data, $authority, function () use ($actor, $data): array {
            $session = ActivitySession::lockForUpdate()->findOrFail($data['activity_session_id']);
            $date = $session->on_date->toDateString();
            if ($session->status === 'cancelled' || $date < $this->access->today()) {
                throw ValidationException::withMessages(['activity_session_id' => __('pool_session_closed')]);
            }
            $group = ActivityGroup::where('active', true)->findOrFail($session->activity_group_id);
            $this->assertGroupSession($session);
            $member = $this->people->bookable($data['pool_member_id'], $data['kind'], $date);
            $booking = PoolBooking::where('activity_session_id', $session->id)->where('pool_member_id', $member->id)->lockForUpdate()->first();
            if ($booking?->status === PoolBooking::BOOKED) {
                return ['id' => (int) $booking->id];
            }
            $booking ??= new PoolBooking(['activity_session_id' => $session->id, 'pool_member_id' => $member->id, 'revision' => 0]);
            $booking->fill(['activity_group_id' => $group->id, 'kind' => $data['kind'], 'status' => PoolBooking::BOOKED, 'booked_by' => $actor->id, 'cancel_reason' => null, 'cancelled_by' => null, 'cancelled_at' => null, 'revision' => $booking->revision + 1])->save();
            Audit::record('people_pool.booked', $booking, [], ['pool_member_id' => $member->id, 'activity_session_id' => $session->id, 'kind' => $data['kind'], 'by' => $actor->id]);

            return ['id' => (int) $booking->id];
        });

        return $this->presentAll(PoolBooking::whereKey($receipt['id'])->get())[0];
    }

    /** A group session: enough learners on the day and a school adult allocated to the group. */
    private function assertGroupSession(ActivitySession $session): void
    {
        $date = $session->on_date->toDateString();
        if (count(app(ActivityGroups::class)->sessionLearnerIds($session)) < max(2, (int) config('people_pools.minimum_learners', 2))) {
            throw ValidationException::withMessages(['activity_session_id' => __('pool_group_only', ['count' => max(2, (int) config('people_pools.minimum_learners', 2))])]);
        }
        $staffed = ActivityStaff::where('active', true)->where('activity_group_id', $session->activity_group_id)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists();
        if (! $staffed) {
            throw ValidationException::withMessages(['activity_session_id' => __('pool_staff_needed')]);
        }
    }

    /** @return array<string, mixed> */
    public function cancel(User $actor, int $bookingId, array $input): array
    {
        $this->access->manager($actor, true);
        PoolBooking::findOrFail($bookingId);
        $data = Validator::make($input, self::rules('cancel'))->validate();
        $data['revision'] = (int) $data['revision'];
        $this->operations->once($actor, 'cancel:'.$bookingId, $data, fn () => $this->access->manager($actor, true), function () use ($actor, $bookingId, $data): array {
            $booking = PoolBooking::lockForUpdate()->findOrFail($bookingId);
            abort_unless($booking->revision === $data['revision'], 409, __('pool_changed'));
            abort_if($booking->status !== PoolBooking::BOOKED, 409, __('pool_booking_not_active'));
            $booking->fill(['status' => PoolBooking::CANCELLED, 'cancel_reason' => 'school', 'cancelled_by' => $actor->id, 'cancelled_at' => now(), 'revision' => $booking->revision + 1])->save();
            Audit::record('people_pool.booking_cancelled', $booking, ['status' => PoolBooking::BOOKED], ['status' => PoolBooking::CANCELLED, 'by' => $actor->id]);

            return ['id' => (int) $booking->id];
        });

        return $this->presentAll(PoolBooking::whereKey($bookingId)->get())[0];
    }

    /** @return array<string, mixed> */
    public function listing(User $actor, ?int $sessionId = null): array
    {
        $this->access->manager($actor);
        $rows = PoolBooking::when($sessionId !== null, fn ($q) => $q->where('activity_session_id', $sessionId))->orderByDesc('id')->limit(200)->get();

        return ['items' => $this->presentAll($rows)];
    }

    /**
     * @param  Collection<int, PoolBooking>  $rows
     * @return list<array<string, mixed>>
     */
    public function presentAll(Collection $rows): array
    {
        $sessions = ActivitySession::whereIn('id', $rows->pluck('activity_session_id'))->get()->keyBy('id');
        $groups = ActivityGroup::withTrashed()->whereIn('id', $rows->pluck('activity_group_id'))->get()->keyBy('id');
        $members = PoolMember::whereIn('id', $rows->pluck('pool_member_id'))->get()->keyBy('id');
        $names = User::acrossSchools()->whereIn('id', $members->pluck('user_id'))->get(['id', 'first_name', 'last_name'])->keyBy('id');

        return $rows->map(function (PoolBooking $booking) use ($sessions, $groups, $members, $names) {
            $session = $sessions->get($booking->activity_session_id);
            $member = $members->get($booking->pool_member_id);
            $user = $member === null ? null : $names->get($member->user_id);
            $date = $session?->on_date?->toDateString();

            return $this->sessionFields($booking, $session, $groups->get($booking->activity_group_id)) + [
                'pool_member_id' => (int) $booking->pool_member_id, 'pool_member_name' => $user === null ? '' : trim($user->first_name.' '.$user->last_name),
                'cancel_reason' => $booking->cancel_reason, 'vetted_for_session' => $member !== null && $date !== null && $member->vettedOn($date),
            ];
        })->values()->all();
    }

    /** @return array<string, mixed> */
    private function sessionFields(PoolBooking $booking, ?ActivitySession $session, ?ActivityGroup $group): array
    {
        return ['id' => (int) $booking->id, 'kind' => $booking->kind, 'status' => $booking->status, 'revision' => $booking->revision,
            'activity_session_id' => (int) $booking->activity_session_id, 'activity_group_id' => (int) $booking->activity_group_id,
            'group_name' => $group?->name ?? '', 'session_name' => $session?->name ?? '', 'venue' => $session?->venue ?? '',
            'on_date' => $session?->on_date?->toDateString(), 'starts_at' => $session?->starts_at === null ? null : substr((string) $session->starts_at, 0, 5),
            'ends_at' => $session?->ends_at === null ? null : substr((string) $session->ends_at, 0, 5)];
    }

    /**
     * The pool member's own bookings from today on, while their clearance
     * runs. No learner appears here.
     *
     * @return array<string, mixed>
     */
    public function mine(PoolMember $member): array
    {
        $today = $this->access->today();
        if (! $member->vettedOn($today)) {
            return ['items' => [], 'vetted' => false];
        }
        $rows = PoolBooking::where('pool_member_id', $member->id)->where('status', PoolBooking::BOOKED)
            ->whereIn('activity_session_id', ActivitySession::whereDate('on_date', '>=', $today)->where('status', '!=', 'cancelled')->select('id'))->orderBy('id')->limit(100)->get();
        $sessions = ActivitySession::whereIn('id', $rows->pluck('activity_session_id'))->get()->keyBy('id');
        $groups = ActivityGroup::whereIn('id', $rows->pluck('activity_group_id'))->get()->keyBy('id');

        return ['vetted' => true, 'items' => $rows->filter(fn (PoolBooking $b) => $member->vettedOn((string) $sessions->get($b->activity_session_id)?->on_date?->toDateString()))
            ->map(fn (PoolBooking $b) => $this->sessionFields($b, $sessions->get($b->activity_session_id), $groups->get($b->activity_group_id))
                + ['attendance_open' => $sessions->get($b->activity_session_id)?->on_date?->toDateString() === $today])->values()->all()];
    }

    /**
     * The session's attendance list for the pool member who is booked into
     * it, on the day of the session only: learner names and the status the
     * school recorded, nothing else.
     *
     * @return array<string, mixed>
     */
    public function attendance(PoolMember $member, int $bookingId): array
    {
        $today = $this->access->today();
        $booking = PoolBooking::where('pool_member_id', $member->id)->where('status', PoolBooking::BOOKED)->findOrFail($bookingId);
        $session = ActivitySession::findOrFail($booking->activity_session_id);
        abort_unless($member->vettedOn($today), 403, __('pool_not_vetted'));
        abort_if($session->status === 'cancelled', 404);
        abort_unless($session->on_date->toDateString() === $today, 403, __('pool_attendance_on_the_day'));
        ActivityGroup::where('active', true)->findOrFail($session->activity_group_id);
        $roster = app(ActivityGroups::class)->sessionLearnerIds($session);
        // A group that shrank to one learner is no longer a group session.
        abort_if(count($roster) < max(2, (int) config('people_pools.minimum_learners', 2)), 403, __('pool_group_only', ['count' => max(2, (int) config('people_pools.minimum_learners', 2))]));
        $captured = ActivityAttendance::where('activity_session_id', $session->id)->get()->keyBy('learner_id');
        $ids = array_values(array_unique([...$roster, ...$captured->keys()->map(fn ($id) => (int) $id)->all()]));
        $learners = Students::withTrashed()->whereIn('id', $ids)->get(['id', 'user_id'])->keyBy('id');
        $users = User::whereIn('id', $learners->pluck('user_id'))->get(['id', 'first_name', 'last_name'])->keyBy('id');
        $list = array_map(function (int $id) use ($learners, $users, $captured) {
            $user = $users->get($learners->get($id)?->user_id);

            return ['name' => $user === null ? __('pool_former_learner') : trim($user->first_name.' '.$user->last_name), 'status' => $captured->get($id)?->status];
        }, $ids);
        usort($list, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        return ['booking_id' => (int) $booking->id, 'on_date' => $today, 'learners' => $list];
    }
}
