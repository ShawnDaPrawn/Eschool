<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Events\Recognition\HousePointsChanged;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Recognition\House;
use App\Models\Recognition\HouseMembership;
use App\Models\Recognition\HousePoint;
use App\Models\Recognition\HousePointLimit;
use App\Models\Recognition\PointCategory;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;

class HousePoints
{
    private function pointAuthority(User $actor, HousePoint $point, bool $write): void
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'house_points', $write);
        if ($a->manages($actor, 'house_points', $write)) {
            return;
        }
        $scope = $a->learner($actor, (int) $point->learner_id, 'house_points', $write, (int) $point->session_year_id);
        abort_unless((int) $scope['placement']->homeroom->class->grade_level_id === (int) $point->grade_level_id, 404);
        if ($scope['scope_kind'] === 'grade' && (int) $scope['scope_id'] === (int) $point->grade_level_id) {
            return;
        }
        if ($point->scope_kind === 'activity_group') {
            abort_unless(in_array((int) $point->scope_id, $a->activityScopes($actor, (int) $point->learner_id, (int) $point->session_year_id), true), 404);

            return;
        }
        if ($point->scope_kind === 'academic_group') {
            $group = app(AcademicAuthority::class)->group($actor, (int) $point->scope_id, true);
            abort_unless((int) $group->session_year_id === (int) $point->session_year_id && (int) $group->grade_level_id === (int) $point->grade_level_id, 404);
            abort_unless(TeachingGroupMembership::where('teaching_group_id', $group->id)->where('learner_id', $point->learner_id)->whereDate('starts_on', '<=', $a->today())->whereDate('ends_on', '>=', $a->today())->exists(), 404);

            return;
        }
        abort_unless($scope['scope_kind'] === $point->scope_kind && $scope['scope_id'] === ($point->scope_id === null ? null : (int) $point->scope_id), 404);
    }

    public function issue(User $actor, array $input): HousePoint
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'house_points', true);
        $d = Validator::make($input, RecognitionRules::for('issue', false))->validate();
        foreach (['learner_id', 'session_year_id', 'point_category_id', 'points'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $d['public'] = (bool) $d['public'];
        $authority = function () use ($a, $actor, $d): void {
            $a->learner($actor, $d['learner_id'], 'house_points', true, $d['session_year_id']);
        };

        return app(RecognitionOperations::class)->once($actor, 'point.issue', $d, $authority, function () use ($a, $actor, $d) {
            $year = $a->currentYear($d['session_year_id']);
            $scope = $a->learner($actor, $d['learner_id'], 'house_points', true, (int) $year->id);
            $category = PointCategory::where('active', true)->findOrFail($d['point_category_id']);
            abort_unless($d['points'] >= $category->minimum_points && $d['points'] <= $category->maximum_points, 422, 'Points must fit the category range.');
            $settings = app(RecognitionCatalog::class)->settings();
            abort_if($d['public'] && ! $settings->public_points_allowed, 422, 'Public point celebrations are disabled by this school.');
            $today = $a->today();
            $members = HouseMembership::where('learner_id', $d['learner_id'])->where('session_year_id', $year->id)->where('active', true)->whereDate('starts_on', '<=', $today)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))->get();
            abort_unless($members->count() === 1, 409, 'Assign one current house before awarding points.');
            $house = House::where('active', true)->findOrFail($members->sole()->house_id);
            $budget = HousePointLimit::firstOrCreate(['staff_id' => $actor->id, 'on_date' => $today], ['spent' => 0]);
            abort_if((int) $budget->spent + abs($d['points']) > $settings->daily_staff_cap, 422, 'Your daily point limit has been reached. Reversals do not restore the allowance.');
            $budget->update(['spent' => (int) $budget->spent + abs($d['points'])]);
            $point = HousePoint::create(array_diff_key($d, ['operation_id' => true]) + ['staff_id' => $actor->id, 'house_id' => $house->id, 'house_name' => $house->name, 'category_name' => $category->name, 'grade_level_id' => $scope['placement']->homeroom->class->grade_level_id, 'on_date' => $today, 'scope_kind' => $scope['scope_kind'], 'scope_id' => $scope['scope_id']]);
            $this->changed($point);

            return $point;
        }, function (int $id) use ($actor) {
            $point = HousePoint::findOrFail($id);
            $this->pointAuthority($actor, $point, true);

            return $point;
        });
    }

    public function reverse(User $actor, int $id, array $input): HousePoint
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'house_points', true);
        $original = HousePoint::findOrFail($id);
        $authority = function () use ($actor, $original): void {
            $this->pointAuthority($actor, $original->fresh(), true);
            abort_unless((int) $original->staff_id === (int) $actor->id || app(RecognitionAuthority::class)->manages($actor, 'house_points', true), 403);
        };
        $authority();
        $d = Validator::make($input, RecognitionRules::for('reverse', false))->validate() + ['point_id' => $id];

        return app(RecognitionOperations::class)->once($actor, 'point.reverse', $d, $authority, function () use ($actor, $id, $d, $a) {
            $p = HousePoint::lockForUpdate()->findOrFail($id);
            abort_unless($p->reverses_id === null && ! HousePoint::where('reverses_id', $id)->exists(), 409, 'This entry has already been corrected or is itself a correction.');
            $values = $p->only(['learner_id', 'house_id', 'house_name', 'point_category_id', 'category_name', 'session_year_id', 'grade_level_id', 'scope_kind', 'scope_id']);
            $point = HousePoint::create($values + ['staff_id' => $actor->id, 'points' => -(int) $p->points, 'reason' => $d['reason'], 'on_date' => $a->today(), 'public' => false, 'reverses_id' => $id]);
            $this->changed($point);

            return $point;
        }, function (int $id) use ($actor) {
            $point = HousePoint::findOrFail($id);
            $this->pointAuthority($actor, $point, true);

            return $point;
        });
    }

    private function changed(HousePoint $point): void
    {
        Outbox::record(new HousePointsChanged(app(CurrentSchool::class)->id(), (int) $point->session_year_id, (int) $point->house_id, (int) $point->id));
        Audit::record('recognition.house_points_changed', $point, [], ['house_id' => (int) $point->house_id, 'session_year_id' => (int) $point->session_year_id, 'revision' => (int) $point->id]);
    }

    public function counters(User $actor, int $year): array
    {
        $a = app(RecognitionAuthority::class);
        $a->actor($actor, 'house_points');
        SessionYear::findOrFail($year);
        $totals = HousePoint::where('session_year_id', $year)->selectRaw('house_id, SUM(points) as total, MAX(id) as revision')->groupBy('house_id')->get()->keyBy('house_id');

        return ['session_year_id' => $year, 'revision' => (int) HousePoint::where('session_year_id', $year)->max('id'), 'items' => House::orderBy('name')->get()->map(fn ($h) => ['id' => (int) $h->id, 'name' => $h->name, 'emblem' => $h->emblem, 'primary_colour' => $h->primary_colour, 'secondary_colour' => $h->secondary_colour, 'active' => $h->active, 'points' => (int) ($totals->get($h->id)?->total ?? 0), 'revision' => (int) ($totals->get($h->id)?->revision ?? 0)])->all()];
    }

    public function history(User $actor, int $learner, int $year, bool $family = false, int $before = 0): array
    {
        $a = app(RecognitionAuthority::class);
        if ($family) {
            $a->family($actor, $learner, 'house_points');
        } else {
            $a->retainedLearner($actor, $learner, 'house_points');
        }
        SessionYear::findOrFail($year);
        $groupScopes = null;
        if (! $family && ! $a->manages($actor, 'house_points')) {
            $scope = $a->learner($actor, $learner, 'house_points');
            if ($scope['scope_kind'] === 'activity_group') {
                $groupScopes = $a->activityScopes($actor, $learner, (int) $scope['placement']->session_year_id);
            }
        }
        $q = HousePoint::where('learner_id', $learner)->where('session_year_id', $year)->when($groupScopes !== null, fn ($q) => $q->where('scope_kind', 'activity_group')->whereIn('scope_id', $groupScopes))->when($before > 0, fn ($q) => $q->where('id', '<', $before))->orderByDesc('id')->limit(51);
        $rows = $q->get();
        $more = $rows->count() > 50;
        $rows = $rows->take(50);

        return ['items' => $rows->map(fn ($p) => ['id' => (int) $p->id, 'house_id' => (int) $p->house_id, 'house_name' => $p->house_name, 'category_name' => $p->category_name, 'points' => (int) $p->points, 'reason' => $p->reason, 'on_date' => $p->on_date->toDateString(), 'public' => $p->public, 'reverses_id' => $p->reverses_id === null ? null : (int) $p->reverses_id])->all(), 'next_before' => $more ? (int) $rows->last()->id : null];
    }
}
