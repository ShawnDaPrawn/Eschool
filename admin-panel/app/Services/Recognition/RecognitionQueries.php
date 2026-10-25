<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\People\Enrolment;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardDefinition;
use App\Models\Recognition\AwardNomination;
use App\Models\Recognition\AwardWindow;
use App\Models\Recognition\House;
use App\Models\Recognition\PointCategory;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class RecognitionQueries
{
    public const MODULES = ['house_points', 'awards'];

    public const CATALOGUES = ['houses' => 'house', 'point-categories' => 'category', 'house-memberships' => 'membership', 'award-categories' => 'awardCategory', 'award-definitions' => 'definition', 'award-windows' => 'window', 'award-administrators' => 'administrator'];

    public function family(User $actor): bool
    {
        return array_diff(app(ProfilePolicy::class)->roles($actor), ['Parent', 'Student']) === [];
    }

    public function access(User $actor, string $module): array
    {
        $a = app(RecognitionAuthority::class);
        $fresh = $a->actor($actor, $module);
        $family = $this->family($fresh);
        $manager = ! $family && $a->manages($fresh, $module);
        $year = (int) schoolSetting('session_year');
        $today = $a->today();
        $heads = $family ? [] : AcademicLeadership::where('user_id', $fresh->id)->where('session_year_id', $year)->where('kind', 'grade_head')->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('grade_level_id')->map(fn ($id) => (int) $id)->all();

        return ['module' => $module, 'school_id' => app(CurrentSchool::class)->id(), 'account_id' => (int) $fresh->id, 'session_year_id' => $year, 'today' => $today, 'family' => $family, 'can_write' => ! $family && app(ModuleGate::class)->writable($module), 'manager' => $manager, 'grade_ids' => $heads];
    }

    private function categoryVisible(User $actor, AwardCategory $c): bool
    {
        $a = app(RecognitionAuthority::class);
        if ($a->categoryAdmin($actor, $c)) {
            return true;
        }
        $year = (int) schoolSetting('session_year');
        $today = $a->today();

        return AcademicLeadership::where('user_id', $actor->id)->where('session_year_id', $year)->where('kind', 'grade_head')->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->when($c->scope_kind === 'grade', fn ($q) => $q->where('grade_level_id', $c->grade_level_id))->exists();
    }

    public function configuration(User $actor, string $module): array
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, $module);
        $access = $this->access($actor, $module);
        $settings = app(RecognitionCatalog::class)->settings();
        $base = ['access' => $access, 'settings' => ['revision' => $settings->revision, 'daily_staff_cap' => $settings->daily_staff_cap, 'public_points_allowed' => $settings->public_points_allowed, 'principal_approval_required' => $settings->principal_approval_required],
            'years' => SessionYear::orderByDesc('start_date')->get()->map(fn ($y) => ['id' => (int) $y->id, 'name' => $y->name, 'starts_on' => substr((string) $y->start_date, 0, 10), 'ends_on' => substr((string) $y->end_date, 0, 10)])->all(),
            'grades' => GradeLevel::orderBy('sort_order')->get()->map(fn ($g) => ['id' => (int) $g->id, 'name' => $g->name])->all()];
        if ($module === 'house_points') {
            return $base + ['houses' => House::orderBy('name')->get()->map(fn ($h) => ['id' => (int) $h->id, 'name' => $h->name, 'emblem' => $h->emblem, 'primary_colour' => $h->primary_colour, 'secondary_colour' => $h->secondary_colour, 'active' => $h->active, 'revision' => $h->revision])->all(),
                'point_categories' => PointCategory::orderBy('name')->get()->map(fn ($c) => ['id' => (int) $c->id, 'name' => $c->name, 'default_points' => $c->default_points, 'minimum_points' => $c->minimum_points, 'maximum_points' => $c->maximum_points, 'active' => $c->active, 'revision' => $c->revision])->all()];
        }
        $categories = [];
        foreach (AwardCategory::orderBy('name')->get() as $c) {
            try {
                if (! $this->categoryVisible($actor, $c)) {
                    continue;
                }$managed = $a->categoryAdmin($actor, $c);
            } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                continue;
            }
            $categories[] = ['id' => (int) $c->id, 'name' => $c->name, 'parent_id' => $c->parent_id, 'scope_kind' => $c->scope_kind, 'grade_level_id' => $c->grade_level_id, 'subject_id' => $c->subject_id, 'activity_group_id' => $c->activity_group_id, 'active' => $c->active, 'revision' => $c->revision, 'can_manage' => $managed];
        }
        $ids = array_column($categories, 'id');

        return $base + ['award_categories' => $categories, 'award_definitions' => AwardDefinition::whereIn('award_category_id', $ids)->orderBy('name')->get()->map(fn ($d) => ['id' => (int) $d->id, 'award_category_id' => (int) $d->award_category_id, 'name' => $d->name, 'criteria' => $d->criteria, 'frequency' => $d->frequency, 'active' => $d->active, 'revision' => $d->revision])->all(),
            'award_windows' => AwardWindow::whereIn('award_category_id', $ids)->orderByDesc('session_year_id')->get()->map(fn ($w) => ['id' => (int) $w->id, 'award_category_id' => (int) $w->award_category_id, 'session_year_id' => (int) $w->session_year_id, 'opens_on' => $w->opens_on->toDateString(), 'closes_on' => $w->closes_on->toDateString(), 'status' => $w->status, 'revision' => $w->revision])->all()];
    }

    public function learners(User $actor, string $module, string $search = '', int $after = 0): array
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, $module);
        $year = (int) schoolSetting('session_year');
        $rows = [];
        $cursor = $after;
        $scanned = 0;
        $query = Enrolment::with(['homeroom.class'])->where('session_year_id', $year)->onDate($a->today())->where('id', '>', $after)
            ->when($search !== '', fn ($q) => $q->whereHas('learner.user', fn ($u) => $u->where(fn ($names) => $names->where('first_name', 'like', '%'.addcslashes($search, '%_\\').'%')->orWhere('last_name', 'like', '%'.addcslashes($search, '%_\\').'%'))))->orderBy('id')->limit(201);
        $places = $query->get();
        foreach ($places->take(200) as $place) {
            $cursor = (int) $place->id;
            $scanned++;
            try {
                $a->learner($actor, (int) $place->learner_id, $module);
            } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                continue;
            }
            if ($place->learner?->user === null) {
                continue;
            }
            $rows[] = ['id' => (int) $place->learner_id, 'name' => $place->learner->user->full_name, 'grade_level_id' => (int) $place->homeroom->class->grade_level_id, 'session_year_id' => $year];
            if (count($rows) === 20) {
                break;
            }
        }
        $more = $scanned < $places->count();

        return ['items' => $rows, 'next_after' => $more ? $cursor : null];
    }

    public function nominations(User $actor, int $year, ?int $grade = null, int $before = 0): array
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards');
        SessionYear::findOrFail($year);
        $manager = $a->manages($actor, 'awards');
        $today = $a->today();
        $grades = AcademicLeadership::where('user_id', $actor->id)->where('kind', 'grade_head')->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('grade_level_id')->all();
        $categories = [];
        if (! $manager) {
            foreach (AwardCategory::get() as $cat) {
                try {
                    if ($a->categoryAdmin($actor, $cat)) {
                        $categories[] = (int) $cat->id;
                    }
                } catch (HttpExceptionInterface|ModelNotFoundException $e) {
                }
            }
        }
        abort_unless($manager || $grades !== [] || $categories !== [], 403);
        if ($grade !== null) {
            GradeLevel::findOrFail($grade);
        }
        $query = AwardNomination::where('session_year_id', $year)->when($grade !== null, fn ($q) => $q->where('grade_level_id', $grade))->when($before > 0, fn ($q) => $q->where('id', '<', $before));
        if (! $manager) {
            $query->where(fn ($q) => $q->whereIn('grade_level_id', $grades)->orWhereIn('award_category_id', $categories));
        }
        $rows = $query->orderByDesc('id')->limit(51)->get();
        $more = $rows->count() > 50;
        $rows = $rows->take(50);
        $items = [];
        foreach ($rows as $n) {
            $learner = Students::with('user')->findOrFail($n->learner_id);
            $category = AwardCategory::findOrFail($n->award_category_id);
            $items[] = ['id' => (int) $n->id, 'revision' => $n->revision, 'learner_id' => (int) $n->learner_id, 'learner_name' => $learner->user?->full_name ?? '', 'grade_level_id' => (int) $n->grade_level_id, 'award_category_id' => (int) $n->award_category_id, 'award_definition_id' => (int) $n->award_definition_id, 'award_name' => $n->definition_name, 'criteria' => $n->definition_criteria, 'reason' => $n->reason, 'state' => $n->state, 'review_reason' => $n->review_reason, 'can_review' => $a->categoryAdmin($actor, $category), 'can_withdraw' => (int) $n->nominated_by === (int) $actor->id || $a->categoryAdmin($actor, $category)];
        }

        return ['items' => $items, 'next_before' => $more ? (int) $rows->last()->id : null];
    }
}
