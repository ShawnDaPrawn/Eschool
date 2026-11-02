<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Models\Wellbeing\CareCase;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\Category;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\TeamAssignment;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Identity\LearnerIdentifiers;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class WellbeingQueries
{
    public function __construct(private WellbeingAccess $access) {}

    public function access(User $actor): array
    {
        $fresh = $this->access->actor($actor);
        $policy = app(ProfilePolicy::class);
        $professional = array_intersect($policy->roles($fresh), ['Counsellor', 'Safeguarding']) !== [] && $policy->permission($fresh, SensitiveRecordsAccess::PERMISSION);
        if ($professional) {
            $this->access->professional($fresh);
        }

        return ['can_configure' => $policy->permission($fresh, 'wellbeing-configure') || $this->access->isPrincipal($fresh),
            'professional' => $professional, 'can_triage' => $professional && $this->access->teamIds($fresh) !== [], 'can_casework' => $professional && $this->access->caseIds($fresh) !== [],
            'timezone' => app(CurrentSchool::class)->school()->timezone, 'school_slug' => app(CurrentSchool::class)->school()->slug, 'can_write' => app(ModuleGate::class)->writable('wellbeing'), 'today' => app(AcademicAuthority::class)->today(), 'emergency_guidance' => WellbeingSettings::EMERGENCY_GUIDANCE];
    }

    private function name(User $person): string
    {
        return trim($person->first_name.' '.$person->last_name);
    }

    public function learnerName(int $id): string
    {
        $person = Students::withTrashed()->with('user')->find($id)?->user;

        return $person === null ? __('Archived learner') : $this->name($person);
    }

    private function search($query, string $search): void
    {
        if ($search !== '') {
            $term = '%'.addcslashes($search, '%_\\').'%';
            $query->where(fn ($q) => $q->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term));
        }
    }

    public function configuration(User $actor, string $search = '', int $page = 1): array
    {
        $this->access->configure($actor);
        $today = app(AcademicAuthority::class)->today();
        $candidates = User::where('status', 1)->whereIn('id', SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->active()->whereIn('role', ['Counsellor', 'Safeguarding'])->select('user_id'))
            ->whereNotIn('id', PlatformStaffIdentity::where('active', true)->select('user_id'));
        $this->search($candidates, $search);
        $professionals = $candidates->orderBy('first_name')->orderBy('id')->paginate(30, ['id', 'first_name', 'last_name', 'status'], 'page', $page);
        $eligible = [];
        foreach ($professionals->items() as $candidate) {
            try {
                $this->access->professional($candidate);
                $eligible[] = ['id' => (int) $candidate->id, 'name' => $this->name($candidate)];
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // Never offer a stale/unauthorized professional as a clinical target.
            }
        }
        $learners = Students::whereIn('id', Enrolment::where('session_year_id', (int) schoolSetting('session_year'))->onDate($today)->select('learner_id'));
        if ($search !== '') {
            $learners->whereHas('user', function ($query) use ($search) {
                $this->search($query, $search);
            });
        }
        $learnerRows = $learners->with('user')->orderBy('id')->paginate(30, ['id', 'school_id', 'user_id', 'admission_no'], 'page', $page);
        $identifiers = app(LearnerIdentifiers::class)->forStaffList($actor, $learnerRows->items());
        $categories = Category::orderBy('name')->orderBy('id')->paginate(30, ['id', 'name', 'emergency_guidance', 'active', 'revision'], 'page', $page);

        return ['professional_choices' => $eligible, 'professionals_last_page' => $professionals->lastPage(),
            'learner_choices' => collect($learnerRows->items())->map(fn ($learner) => ['id' => (int) $learner->id, 'name' => $learner->user === null ? __('Archived learner') : $this->name($learner->user), 'admission_no' => $learner->admission_no, 'primary_identifier' => $identifiers[(int) $learner->id] ?? null])->all(),
            'learners_last_page' => $learnerRows->lastPage(), 'categories' => $categories->items(), 'categories_last_page' => $categories->lastPage(), 'page' => $page];
    }

    public function managedCases(User $actor, int $page = 1): array
    {
        $this->access->configure($actor);
        $rows = CareCase::orderByDesc('id')->paginate(30, ['id', 'learner_id', 'status', 'opened_at', 'revision'], 'page', $page);

        return ['items' => collect($rows->items())->map(fn ($case) => $case->only(['id', 'learner_id', 'status', 'opened_at', 'revision']) + ['learner_name' => $this->learnerName((int) $case->learner_id)])->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function assignments(User $actor, string $kind, int $owner, int $page = 1): array
    {
        $this->access->configure($actor);
        if ($kind === 'case') {
            CareCase::select('id')->findOrFail($owner);
            $rows = CaseAssignment::where('support_case_id', $owner);
        } else {
            Category::findOrFail($owner);
            $rows = TeamAssignment::where('category_id', $owner);
        }
        $paginator = $rows->orderByDesc('id')->paginate(30, ['*'], 'page', $page);

        return ['owner_id' => $owner, 'items' => collect($paginator->items())->map(function ($row) {
            $person = User::find($row->user_id);

            return ['id' => (int) $row->id, 'user_id' => (int) $row->user_id, 'name' => $person === null ? __('Archived staff') : $this->name($person),
                'starts_on' => $row->starts_on->toDateString(), 'ends_on' => $row->ends_on->toDateString(), 'active' => (bool) $row->active, 'revision' => $row->revision];
        })->all(), 'page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage()];
    }

    public function caseMetadata(User $actor, int $id): array
    {
        $this->access->configure($actor);
        $case = CareCase::select(['id', 'learner_id', 'status', 'opened_at', 'revision'])->findOrFail($id);

        return $case->only(['id', 'learner_id', 'status', 'opened_at', 'revision']) + ['learner_name' => $this->learnerName((int) $case->learner_id)];
    }

    /** Only professionals already assigned to this case on the chosen date. */
    public function caseProfessionals(User $actor, int $id, string $onDate, string $search = '', int $page = 1, ?int $selected = null): array
    {
        $this->access->case($actor, $id);
        Validator::make(['on_date' => $onDate, 'search' => $search, 'page' => $page], ['on_date' => ['date_format:Y-m-d'], 'search' => ['string', 'max:100'], 'page' => ['integer', 'between:1,10000']])->validate();
        $assigned = CaseAssignment::where('support_case_id', $id)->where('active', true)->whereDate('starts_on', '<=', $onDate)->whereDate('ends_on', '>=', $onDate)->select('user_id');
        $roles = SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('status', 'active')->whereIn('role', ['Counsellor', 'Safeguarding'])
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $onDate))->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $onDate))->select('user_id');
        $rows = User::whereIn('id', $assigned)->whereIn('id', $roles)->where('status', 1)->whereNotIn('id', PlatformStaffIdentity::where('active', true)->select('user_id'));
        $selectedQuery = clone $rows;
        $this->search($rows, $search);
        $paginator = $rows->orderBy('first_name')->orderBy('id')->paginate(30, ['id', 'first_name', 'last_name', 'status'], 'page', $page);
        $items = [];
        foreach ($paginator->items() as $candidate) {
            try {
                $this->access->professional($candidate);
                $items[] = ['id' => (int) $candidate->id, 'name' => $this->name($candidate)];
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                // A retained case grant cannot revive a revoked clinical role.
            }
        }
        if ($selected !== null && ! in_array($selected, array_column($items, 'id'), true)) {
            $candidate = $selectedQuery->whereKey($selected)->first(['id', 'first_name', 'last_name', 'status']);
            if ($candidate !== null) {
                try {
                    $this->access->professional($candidate);
                    $items[] = ['id' => (int) $candidate->id, 'name' => $this->name($candidate)];
                } catch (ModelNotFoundException|HttpExceptionInterface) {
                    // The private draft must choose again when authority changes.
                }
            }
        }
        $this->access->case($actor, $id);

        return ['items' => $items, 'page' => $page, 'last_page' => $paginator->lastPage(), 'selected_available' => $selected === null || in_array($selected, array_column($items, 'id'), true)];
    }

    public function caseReportChoices(User $actor, int $id, int $page = 1, ?int $selected = null): array
    {
        $case = $this->access->case($actor, $id);
        $query = IncidentReport::select(['id', 'category_id', 'created_at'])->where('anonymous', false)->where('learner_id', $case->learner_id)
            ->whereIn('category_id', $this->access->teamIds($actor));
        $selectedQuery = clone $query;
        $rows = $query->orderByDesc('id')->paginate(30, ['*'], 'page', $page);
        $choice = fn ($row) => ['id' => (int) $row->id, 'name' => Category::findOrFail($row->category_id)->name.' · '.$row->created_at->toDateString()];
        $items = collect($rows->items())->map($choice)->all();
        if ($selected !== null && ! in_array($selected, array_column($items, 'id'), true)) {
            $row = $selectedQuery->whereKey($selected)->first();
            if ($row !== null) {
                $items[] = $choice($row);
            }
        }
        $this->access->case($actor, $id);

        return ['items' => $items, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function reports(User $actor, int $page = 1): array
    {
        $result = app(IncidentReporting::class)->listing($actor, $page);
        $result['items'] = collect($result['items'])->map(fn ($row) => $row->only(['id', 'category_id', 'anonymous', 'urgency', 'status', 'revision', 'created_at']) + ['category_name' => Category::findOrFail($row->category_id)->name])->all();

        return $result;
    }

    public function report(User $actor, int $id, ?int $before = null, bool $paged = false): array
    {
        $result = $paged ? app(IncidentReporting::class)->readPage($actor, $id, $before) : app(IncidentReporting::class)->read($actor, $id);
        $result['category_name'] = Category::findOrFail($result['category_id'])->name;
        $reporter = $result['reporter_user_id'] === null ? null : User::find($result['reporter_user_id']);
        $result['reporter_name'] = $reporter === null ? null : $this->name($reporter);
        $result['learner_name'] = $result['learner_id'] === null ? null : $this->learnerName((int) $result['learner_id']);
        foreach ($result['threads'] as &$thread) {
            $person = $thread['staff_user_id'] === null ? null : User::find($thread['staff_user_id']);
            $thread['author_name'] = $thread['author_type'] === 'reporter' ? __('Reporter') : ($person === null ? __('Archived staff') : $this->name($person));
        }
        unset($thread);
        $this->access->report($actor, $id);

        return $result;
    }

    public function cases(User $actor, int $page = 1): array
    {
        $result = app(Casework::class)->listing($actor, $page);
        $result['items'] = collect($result['items'])->map(fn ($case) => $case->only(['id', 'learner_id', 'status', 'opened_at', 'revision']) + ['learner_name' => $this->learnerName((int) $case->learner_id)])->all();

        return $result;
    }

    public function case(User $actor, int $id, ?int $before = null): array
    {
        $result = app(Casework::class)->read($actor, $id, $before);
        $result['learner_name'] = $this->learnerName((int) $result['learner_id']);
        foreach ($result['entries'] as &$entry) {
            $person = User::find($entry['author_id']);
            $entry['author_name'] = $person === null ? __('Archived staff') : $this->name($person);
        }
        unset($entry);
        $this->access->case($actor, $id);

        return $result;
    }
}
