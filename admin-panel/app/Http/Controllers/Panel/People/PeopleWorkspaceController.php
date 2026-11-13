<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\Pools\PoolPanelRequest;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivitySession;
use App\Models\Activities\ActivityStaff;
use App\Models\People\Pools\PoolBooking;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Activities\ActivityGroups;
use App\Services\Activities\CommunityAuthority;
use App\Services\Panel\SessionYearContext;
use App\Services\People\Pools\PoolAccess;
use App\Services\People\Pools\PoolBookings;
use App\Services\People\Pools\PoolMembers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Native panel screens over the existing school-scoped pool and allocation services. */
class PeopleWorkspaceController extends Controller
{
    public function facilitators(Request $request): View
    {
        $authority = app(CommunityAuthority::class);
        $actor = $authority->actor($request->user());
        abort_if(array_diff(app(ProfilePolicy::class)->roles($actor), ['Parent', 'Student']) === [], 403);
        $policy = app(ProfilePolicy::class);
        abort_unless($policy->permission($actor, 'staff-list') || $policy->permission($actor, 'activity-groups-manage'), 403);
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1', 'max:10000']]);
        $year = app(SessionYearContext::class)->selected();
        $groups = ActivityGroup::where('session_year_id', $year?->id)->get()->keyBy('id');
        $allocations = ActivityStaff::whereIn('activity_group_id', $groups->keys())->where('role', 'facilitator')->orderByDesc('starts_on')->get();
        $memberIds = SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->active()->where('role', 'Facilitator')->select('user_id');
        $people = User::query()->where(fn ($q) => $q->whereIn('id', $memberIds)->orWhereIn('id', $allocations->pluck('user_id')))
            ->when($data['search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')))
            ->orderBy('last_name')->orderBy('id')->paginate(25)->withQueryString();

        return view('people.facilitators', ['people' => $people, 'groups' => $groups, 'allocations' => $allocations->groupBy('user_id'), 'year' => $year, 'canManage' => $policy->permission($actor, 'activity-groups-manage')]);
    }

    public function index(Request $request): View
    {
        $page = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:10000']]);

        return view('people.pool.index', ['listing' => app(PoolMembers::class)->listing($request->user(), (int) ($page['page'] ?? 1))]);
    }

    public function create(Request $request): View
    {
        app(PoolAccess::class)->manager($request->user(), true);

        $filters = $request->validate(['learner_search' => ['nullable', 'string', 'max:100']]);
        $leaverUsers = SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('role', 'Student')->whereNotNull('leaver_until')->whereIn('status', [SchoolMembership::LEAVER, SchoolMembership::ENDED])->select('user_id');
        $learners = Students::withTrashed()->whereIn('user_id', $leaverUsers)->get(['id', 'user_id', 'admission_no']);
        // Names are read only for former learner IDs already scoped to this school.
        $names = User::acrossSchools()->whereIn('id', $learners->pluck('user_id'))->whereDate('dob', '<=', now()->subYears((int) config('people_pools.minimum_age', 18))->toDateString())
            ->when($filters['learner_search'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')))
            ->orderBy('last_name')->limit(100)->get()->keyBy('id');
        $formerLearners = $learners->filter(fn ($learner) => $names->has($learner->user_id))->mapWithKeys(fn ($learner) => [$learner->id => $names[$learner->user_id]->full_name.' / '.$learner->admission_no])->all();

        return view('people.pool.invite', compact('formerLearners'));
    }

    public function detail(Request $request, int $member): View
    {
        $detail = app(PoolMembers::class)->detail($request->user(), $member);
        $policy = app(ProfilePolicy::class);

        return view('people.pool.detail', ['member' => $detail, 'canVet' => app(PoolAccess::class)->mayVet($request->user()), 'canManage' => $policy->permission($request->user(), PoolAccess::MANAGE), 'checks' => config('people_pools.checks', [])]);
    }

    public function bookings(Request $request): View
    {
        app(PoolAccess::class)->manager($request->user());
        $filters = $request->validate(['booking_page' => ['sometimes', 'integer', 'min:1', 'max:10000'], 'member_page' => ['sometimes', 'integer', 'min:1', 'max:10000'], 'session_page' => ['sometimes', 'integer', 'min:1', 'max:10000']]);
        $members = app(PoolMembers::class)->listing($request->user(), (int) ($filters['member_page'] ?? 1));
        $bookingRecords = PoolBooking::whereIn('activity_group_id', ActivityGroup::withTrashed()->where('session_year_id', app(SessionYearContext::class)->selectedId())->select('id'))->orderByDesc('id')->paginate(25, ['*'], 'booking_page')->withQueryString();
        $listing = ['items' => app(PoolBookings::class)->presentAll($bookingRecords->getCollection())];
        $groups = ActivityGroup::where('session_year_id', app(SessionYearContext::class)->selectedId())->where('active', true)->get()->keyBy('id');
        $sessions = ActivitySession::whereIn('activity_group_id', $groups->keys())->where('status', '!=', 'cancelled')->whereDate('on_date', '>=', app(PoolAccess::class)->today())->orderBy('on_date')->paginate(25, ['*'], 'session_page')->withQueryString();

        $sessionEligibility = $sessions->getCollection()->mapWithKeys(function (ActivitySession $session): array {
            $date = $session->on_date->toDateString();
            $learnerCount = count(app(ActivityGroups::class)->sessionLearnerIds($session));
            $supervised = ActivityStaff::where('active', true)->where('activity_group_id', $session->activity_group_id)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists();

            return [$session->id => ['learners' => $learnerCount, 'supervised' => $supervised, 'ready' => $learnerCount >= max(2, (int) config('people_pools.minimum_learners', 2)) && $supervised]];
        });

        return view('people.pool.bookings', compact('listing', 'members', 'groups', 'sessions', 'sessionEligibility', 'bookingRecords'));
    }

    public function invite(PoolPanelRequest $request): RedirectResponse
    {
        return $this->perform($request, fn () => app(PoolMembers::class)->invite($request->user(), $request->validated()), fn ($record) => route('people-pool-panel.detail', $record['id']), __('Pool invitation created. The person must sign in and give their own consent.'));
    }

    public function vet(PoolPanelRequest $request, int $member): RedirectResponse
    {
        return $this->perform($request, fn () => app(PoolMembers::class)->vet($request->user(), $member, $request->validated()), fn () => route('people-pool-panel.detail', $member), __('Vetting decision saved.'));
    }

    public function book(PoolPanelRequest $request): RedirectResponse
    {
        return $this->perform($request, fn () => app(PoolBookings::class)->book($request->user(), $request->validated()), fn () => route('people-pool-panel.bookings'), __('Group session booking saved.'));
    }

    public function cancel(PoolPanelRequest $request, int $booking): RedirectResponse
    {
        return $this->perform($request, fn () => app(PoolBookings::class)->cancel($request->user(), $booking, $request->validated()), fn () => route('people-pool-panel.bookings'), __('Booking cancelled. Its history is retained.'));
    }

    private function perform(PoolPanelRequest $request, Closure $save, Closure $destination, string $message): RedirectResponse
    {
        try {
            $record = $save();

            return redirect($destination($record))->with('success', $message);
        } catch (ValidationException $error) {
            return redirect($request->recoveryUrl())->withErrors($error->errors())->withInput($request->except(['initial_password', '_token']));
        } catch (HttpExceptionInterface $error) {
            if ($error->getStatusCode() === 422) {
                return redirect($request->recoveryUrl())->withErrors(['record' => $error->getMessage()])->withInput($request->except(['initial_password', '_token']));
            }
            if ($error->getStatusCode() !== 409) {
                throw $error;
            }

            return redirect($request->recoveryUrl())->withErrors(['record' => __('This record changed. Review the current values before saving again.')])->withInput($request->except(['initial_password', '_token']));
        }
    }
}
