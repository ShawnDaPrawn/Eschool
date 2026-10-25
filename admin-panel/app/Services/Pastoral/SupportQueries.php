<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\SupportAssignment;
use App\Models\Pastoral\SupportDisclosure;
use App\Models\Pastoral\SupportPlan;
use App\Models\Pastoral\SupportPlanVersion;
use App\Models\Pastoral\SupportReview;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class SupportQueries
{
    public function access(User $actor): array
    {
        $audiences = app(SupportAuthority::class)->audiences($actor);
        abort_unless($audiences !== [], 403);

        return ['audiences' => $audiences, 'can_write' => app(ModuleGate::class)->writable('support_plans'), 'today' => app(AcademicAuthority::class)->today()];
    }

    private function learnerName(int $id): string
    {
        return Students::with('user')->find($id)?->user?->full_name ?? __('Archived learner');
    }

    public function plan(User $actor, int $id): array
    {
        $school = app(CurrentSchool::class)->id();
        $a = app(SupportAuthority::class);
        $plan = $a->plan($actor, $id);
        $reviews = SupportReview::where('support_plan_id', $id)->orderByDesc('reviewed_on')->orderByDesc('id')->limit(21)->get();
        $disclosures = SupportDisclosure::where('support_plan_id', $id)->where('active', true)->orderBy('id')->limit(21)->get();
        $moreReviews = $reviews->count() > 20;
        $moreDisclosures = $disclosures->count() > 20;
        $reviews = $reviews->take(20);
        $disclosures = $disclosures->take(20);
        $result = ['id' => (int) $plan->id, 'learner_id' => (int) $plan->learner_id, 'learner_name' => $this->learnerName((int) $plan->learner_id), 'revision' => $plan->revision, 'state' => $plan->state,
            'started_on' => $plan->started_on->toDateString(), 'review_due_on' => $plan->review_due_on?->toDateString(), 'sias_stage' => $plan->contents['sias_stage'], 'needs' => $plan->contents['needs'], 'accommodations' => $plan->contents['accommodations'],
            'reviews_has_more' => $moreReviews, 'disclosures_has_more' => $moreDisclosures,
            'reviews' => $reviews->map(fn ($r) => ['id' => (int) $r->id, 'author_id' => (int) $r->author_id, 'author_name' => User::find($r->author_id)?->full_name ?? __('Archived staff'), 'reviewed_on' => $r->reviewed_on->toDateString(), 'next_review_on' => $r->next_review_on?->toDateString(), 'kind' => $r->kind, 'outcome' => $r->outcome])->all(),
            'disclosures' => $disclosures->map(fn ($d) => ['id' => (int) $d->id, 'revision' => $d->revision, 'user_id' => (int) $d->user_id, 'teacher_name' => User::find($d->user_id)?->full_name ?? __('Archived staff'), 'expires_on' => $d->expires_on->toDateString(), 'accommodations' => $d->accommodations])->all()];
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $a->plan($actor, $id);

        return $result;
    }

    public function reviews(User $actor, int $id, int $page = 1): array
    {
        app(SupportAuthority::class)->plan($actor, $id);
        $rows = SupportReview::where('support_plan_id', $id)->orderByDesc('reviewed_on')->orderByDesc('id')->paginate(50, ['*'], 'page', max(1, $page));

        return ['plan_id' => $id, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->getCollection()->map(fn ($r) => ['id' => (int) $r->id, 'author_id' => (int) $r->author_id, 'author_name' => User::find($r->author_id)?->full_name ?? __('Archived staff'), 'reviewed_on' => $r->reviewed_on->toDateString(), 'next_review_on' => $r->next_review_on?->toDateString(), 'kind' => $r->kind, 'outcome' => $r->outcome])->all()];
    }

    public function disclosures(User $actor, int $id, int $page = 1): array
    {
        app(SupportAuthority::class)->plan($actor, $id);
        $rows = SupportDisclosure::where('support_plan_id', $id)->orderByDesc('id')->paginate(50, ['*'], 'page', max(1, $page));

        return ['plan_id' => $id, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->getCollection()->map(fn ($d) => ['id' => (int) $d->id, 'revision' => $d->revision, 'user_id' => (int) $d->user_id, 'teacher_name' => User::find($d->user_id)?->full_name ?? __('Archived staff'), 'expires_on' => $d->expires_on->toDateString(), 'active' => $d->active, 'accommodations' => $d->accommodations])->all()];
    }

    public function versions(User $actor, int $id, int $page = 1): array
    {
        app(SupportAuthority::class)->plan($actor, $id);
        $rows = SupportPlanVersion::where('support_plan_id', $id)->orderByDesc('revision')->paginate(50, ['*'], 'page', max(1, $page));
        $result = ['plan_id' => $id, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->getCollection()->map(fn ($r) => ['revision' => $r->revision, 'author_id' => (int) $r->author_id, 'recorded_at' => $r->created_at->toIso8601String(), 'snapshot' => $r->snapshot])->all()];
        app(SupportAuthority::class)->plan($actor, $id);

        return $result;
    }

    public function disclosure(User $actor, int $id): array
    {
        $row = app(SupportAuthority::class)->disclosure($actor, $id);

        return ['id' => (int) $row->id, 'learner_id' => (int) $row->learner_id, 'learner_name' => $this->learnerName((int) $row->learner_id), 'revision' => $row->revision,
            'expires_on' => $row->expires_on->toDateString(), 'accommodations' => $row->accommodations];
    }

    public function teacher(User $actor, int $page = 1): array
    {
        $fresh = app(SupportAuthority::class)->teacher($actor);
        $rows = SupportDisclosure::where('user_id', $fresh->id)->where('active', true)->whereDate('expires_on', '>=', app(AcademicAuthority::class)->today())->orderBy('id')->paginate(50, ['*'], 'page', max(1, $page));
        $items = [];
        foreach ($rows as $row) {
            try {
                $items[] = $this->disclosure($fresh, (int) $row->id);
            } catch (ModelNotFoundException|HttpExceptionInterface) { /* Withdrawn/stale projections never survive in a listing. */
            }
        }
        app(SupportAuthority::class)->teacher($actor);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $items];
    }

    public function cases(User $actor, int $page = 1): array
    {
        $fresh = app(SupportAuthority::class)->professional($actor);
        $today = app(AcademicAuthority::class)->today();
        $assigned = SupportAssignment::where('user_id', $fresh->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
        $rows = SupportPlan::whereIn('learner_id', $assigned)->orderByDesc('id')->paginate(50, ['id', 'learner_id', 'revision', 'state', 'review_due_on'], 'page', max(1, $page));
        $items = $rows->getCollection()->map(fn ($r) => ['id' => (int) $r->id, 'learner_id' => (int) $r->learner_id, 'learner_name' => $this->learnerName((int) $r->learner_id), 'revision' => $r->revision, 'state' => $r->state, 'review_due_on' => $r->review_due_on?->toDateString()])->all();
        app(SupportAuthority::class)->professional($actor);

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $items];
    }

    /** Search returns named choices, never private plan contents. */
    public function configuration(User $actor, string $audience, string $search = '', int $page = 1): array
    {
        $a = app(SupportAuthority::class);
        if ($audience === 'principal') {
            $a->principal($actor);
        } else {
            $a->professional($actor);
        }
        $today = app(AcademicAuthority::class)->today();
        $query = Students::with('user')->whereIn('id', Enrolment::onDate($today)->select('learner_id'));
        if ($audience !== 'principal') {
            $query->whereIn('id', SupportAssignment::where('user_id', $actor->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id'));
        }
        if ($search !== '') {
            $query->whereHas('user', fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')));
        }
        $rows = $query->orderBy('id')->paginate(50, ['*'], 'page', max(1, $page));
        $staff = [];
        $roles = $audience === 'principal' ? ['Counsellor', 'Safeguarding'] : ['Teacher'];
        // Membership is the current tenant's authority; stale legacy profiles do
        // not confer eligibility and platform operators are excluded entirely.
        $ids = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereIn('role', $roles)->select('user_id');
        $operators = PlatformStaffIdentity::where('active', true)->select('user_id');
        $people = User::whereIn('id', $ids)->whereNotIn('id', $operators)
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('first_name', 'like', '%'.$search.'%')->orWhere('last_name', 'like', '%'.$search.'%')))
            ->orderBy('first_name')->orderBy('id')->paginate(50, ['*'], 'staff_page', max(1, $page));
        foreach ($people as $person) {
            try {
                $audience === 'principal' ? $a->professional($person) : $a->teacher($person);
                $staff[] = ['id' => (int) $person->id, 'name' => $person->full_name];
            } catch (HttpExceptionInterface) {
                // Eligibility may have changed since the selection query.
            }
        }
        $audience === 'principal' ? $a->principal($actor) : $a->professional($actor);

        return ['audience' => $audience, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(),
            'learners' => $rows->getCollection()->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->user?->full_name ?? __('Archived learner')])->all(),
            'staff' => $staff, 'staff_last_page' => $people->lastPage()];
    }

    /** Principal appointment management returns no plan, review or disclosed body. */
    public function assignments(User $actor, int $page = 1): array
    {
        app(SupportAuthority::class)->principal($actor);
        $rows = SupportAssignment::orderByDesc('id')->paginate(50, ['*'], 'page', max(1, $page));

        return ['page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'items' => $rows->getCollection()->map(fn ($r) => ['id' => (int) $r->id, 'learner_id' => (int) $r->learner_id, 'learner_name' => $this->learnerName((int) $r->learner_id), 'user_id' => (int) $r->user_id, 'professional_name' => User::find($r->user_id)?->full_name ?? __('Archived staff'),
            'starts_on' => $r->starts_on->toDateString(), 'ends_on' => $r->ends_on->toDateString(), 'active' => $r->active, 'revision' => $r->revision])->all()];
    }
}
