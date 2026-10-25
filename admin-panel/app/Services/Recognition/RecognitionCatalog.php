<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Academics\GradeLevel;
use App\Models\Activities\ActivityGroup;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardCategoryAdmin;
use App\Models\Recognition\AwardDefinition;
use App\Models\Recognition\AwardWindow;
use App\Models\Recognition\House;
use App\Models\Recognition\HouseMembership;
use App\Models\Recognition\PointCategory;
use App\Models\Recognition\RecognitionSettings;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class RecognitionCatalog
{
    public function settings(): RecognitionSettings
    {
        return RecognitionSettings::firstOrCreate([], ['daily_staff_cap' => 50, 'public_points_allowed' => false, 'principal_approval_required' => false, 'revision' => 1]);
    }

    public function revision(Model $row, array $data): void
    {
        abort_unless((int) $row->revision === (int) ($data['revision'] ?? 0), 409, 'The recognition record changed. Compare its current version.');
    }

    private function save(User $actor, string $kind, string $module, array $data, callable $policy, callable $write, string $class, ?int $id): Model
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, $module, true);
        $authority = function () use ($actor, $module, $a, $policy): void {
            $a->staff($actor, $module, true);
            $policy();
        };

        return app(RecognitionOperations::class)->once($actor, $kind, $data + ['target_id' => $id], $authority, $write, fn ($record) => $class::findOrFail($record));
    }

    public function configure(User $actor, string $module, array $input): RecognitionSettings
    {
        $a = app(RecognitionAuthority::class);
        $a->manage($actor, $module);
        $d = Validator::make($input, RecognitionRules::for('configure', false))->validate();
        foreach (['revision', 'daily_staff_cap'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        foreach (['public_points_allowed', 'principal_approval_required'] as $key) {
            $d[$key] = (bool) $d[$key];
        }

        return $this->save($actor, 'settings', $module, $d, fn () => $a->manage($actor, $module), function () use ($d) {
            $row = $this->settings();
            $this->revision($row, $d);
            $row->update(array_diff_key($d, ['operation_id' => true, 'revision' => true]) + ['revision' => $row->revision + 1]);
            Audit::record('recognition.settings_changed', $row, [], ['revision' => $row->revision]);

            return $row;
        }, RecognitionSettings::class, null);
    }

    public function house(User $actor, array $input, ?int $id = null): House
    {
        $a = app(RecognitionAuthority::class);
        $a->manage($actor, 'house_points');
        if ($id !== null) {
            House::findOrFail($id);
        }
        $d = Validator::make($input, RecognitionRules::for('house', $id !== null))->validate();
        $d['active'] = (bool) $d['active'];

        return $this->save($actor, 'house.save', 'house_points', $d, fn () => $a->manage($actor, 'house_points'), function () use ($actor, $id, $d) {
            $row = $id === null ? new House : House::lockForUpdate()->findOrFail($id);
            if ($id !== null) {
                $this->revision($row, $d);
            }
            $values = array_diff_key($d, ['operation_id' => true, 'revision' => true]);
            $row->fill($values + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('recognition.house_saved', $row, [], ['revision' => $row->revision, 'actor_id' => (int) $actor->id]);

            return $row;
        }, House::class, $id);
    }

    public function category(User $actor, array $input, ?int $id = null): PointCategory
    {
        $a = app(RecognitionAuthority::class);
        $a->manage($actor, 'house_points');
        if ($id !== null) {
            PointCategory::findOrFail($id);
        }
        $d = Validator::make($input, RecognitionRules::for('category', $id !== null))->validate();
        foreach (['default_points', 'minimum_points', 'maximum_points'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $d['active'] = (bool) $d['active'];
        abort_unless($d['minimum_points'] <= $d['default_points'] && $d['default_points'] <= $d['maximum_points'], 422, 'The category default must be within its point range.');

        return $this->save($actor, 'point-category.save', 'house_points', $d, fn () => $a->manage($actor, 'house_points'), function () use ($id, $d) {
            $row = $id === null ? new PointCategory : PointCategory::lockForUpdate()->findOrFail($id);
            if ($id !== null) {
                $this->revision($row, $d);
            }
            $row->fill(array_diff_key($d, ['operation_id' => true, 'revision' => true]) + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('recognition.point_category_saved', $row, [], ['revision' => $row->revision]);

            return $row;
        }, PointCategory::class, $id);
    }

    public function membership(User $actor, array $input, ?int $id = null): HouseMembership
    {
        $a = app(RecognitionAuthority::class);
        $a->manage($actor, 'house_points');
        if ($id !== null) {
            HouseMembership::findOrFail($id);
        }
        $d = Validator::make($input, RecognitionRules::for('membership', $id !== null))->validate();
        foreach (['learner_id', 'house_id', 'session_year_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $d['active'] = (bool) $d['active'];

        return $this->save($actor, 'membership.save', 'house_points', $d, fn () => $a->manage($actor, 'house_points'), function () use ($a, $id, $d) {
            Students::findOrFail($d['learner_id']);
            House::when($d['active'], fn ($q) => $q->where('active', true))->findOrFail($d['house_id']);
            $year = SessionYear::findOrFail($d['session_year_id']);
            $end = $d['ends_on'] ?? substr((string) $year->end_date, 0, 10);
            abort_unless($year->start_date !== null && $year->end_date !== null && $d['starts_on'] >= substr((string) $year->start_date, 0, 10) && $end <= substr((string) $year->end_date, 0, 10), 422, 'House dates must fit the named school year.');
            if ($d['active']) {
                $a->placement($d['learner_id'], $d['session_year_id'], $d['starts_on']);
                abort_if(HouseMembership::where('learner_id', $d['learner_id'])->where('session_year_id', $d['session_year_id'])->where('active', true)->when($id !== null, fn ($q) => $q->where('id', '!=', $id))
                    ->whereDate('starts_on', '<=', $end)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $d['starts_on']))->exists(), 409, 'This learner already belongs to a house during those dates.');
            }
            $row = $id === null ? new HouseMembership : HouseMembership::lockForUpdate()->findOrFail($id);
            if ($id !== null) {
                $this->revision($row, $d);
                abort_unless((int) $row->learner_id === $d['learner_id'] && (int) $row->session_year_id === $d['session_year_id'] && (int) $row->house_id === $d['house_id'], 422, 'House membership identity cannot be reassigned. End the old dated membership and create the new one.');
            }
            $row->fill(array_diff_key($d, ['operation_id' => true, 'revision' => true]) + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('recognition.house_membership_saved', $row, [], ['learner_id' => $d['learner_id'], 'house_id' => $d['house_id'], 'revision' => $row->revision]);

            return $row;
        }, HouseMembership::class, $id);
    }

    public function awardCategory(User $actor, array $input, ?int $id = null): AwardCategory
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', true);
        $current = $id === null ? null : AwardCategory::findOrFail($id);
        $policy = function () use ($current, $a, $actor, $input): void {
            $parent = empty($input['parent_id']) ? null : AwardCategory::findOrFail((int) $input['parent_id']);
            abort_unless($current !== null ? $a->categoryAdmin($actor, $current->fresh(), true) : ($a->manages($actor, 'awards', true) || ($parent !== null && $a->categoryAdmin($actor, $parent, true))), 403);
        };
        $policy();
        $d = Validator::make($input, RecognitionRules::for('awardCategory', $id !== null))->validate();
        foreach (['parent_id', 'grade_level_id', 'subject_id', 'activity_group_id'] as $key) {
            $d[$key] = $d[$key] === null ? null : (int) $d[$key];
        }
        $d['active'] = (bool) $d['active'];

        return $this->save($actor, 'award-category.save', 'awards', $d, $policy, function () use ($actor, $a, $d, $id) {
            if ($d['scope_kind'] === 'grade') {
                GradeLevel::findOrFail($d['grade_level_id']);
                abort_unless($d['activity_group_id'] === null, 422);
                if ($d['subject_id'] !== null) {
                    Subject::findOrFail($d['subject_id']);
                }
            } elseif ($d['scope_kind'] === 'group') {
                ActivityGroup::findOrFail($d['activity_group_id']);
                abort_unless($d['grade_level_id'] === null && $d['subject_id'] === null, 422);
            } else {
                abort_unless($d['grade_level_id'] === null && $d['subject_id'] === null && $d['activity_group_id'] === null, 422);
            }
            if ($d['parent_id'] !== null) {
                $parent = AwardCategory::findOrFail($d['parent_id']);
                $ancestors = $a->lineage($parent);
                abort_if(count($ancestors) >= 8 || ($id !== null && in_array($id, $ancestors, true)), 422, 'Award category ancestry would contain a cycle or too many levels.');
                abort_unless($a->categoryAdmin($actor, $parent, true), 403);
                if ($parent->scope_kind !== 'school') {
                    abort_unless($parent->scope_kind === $d['scope_kind'] && $parent->grade_level_id === $d['grade_level_id'] && $parent->activity_group_id === $d['activity_group_id']
                        && ($parent->subject_id === null || (int) $parent->subject_id === $d['subject_id']), 422, 'A subcategory cannot broaden its parent scope.');
                }
            }
            $row = $id === null ? new AwardCategory : AwardCategory::lockForUpdate()->findOrFail($id);
            if ($id !== null) {
                $this->revision($row, $d);
                // Scope changes can silently broaden child definitions and grants.
                foreach (['parent_id', 'scope_kind', 'grade_level_id', 'subject_id', 'activity_group_id'] as $key) {
                    abort_unless($row->getAttribute($key) === $d[$key], 422, 'Create a new category when its scope changes.');
                }
            }
            $row->fill(array_diff_key($d, ['operation_id' => true, 'revision' => true]) + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('recognition.award_category_saved', $row, [], ['revision' => $row->revision]);

            return $row;
        }, AwardCategory::class, $id);
    }

    public function definition(User $actor, array $input, ?int $id = null): AwardDefinition
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', true);
        $category = AwardCategory::findOrFail($input['award_category_id'] ?? null);
        $policy = fn () => abort_unless($a->categoryAdmin($actor, $category->fresh(), true), 403);
        $policy();
        $d = Validator::make($input, RecognitionRules::for('definition', $id !== null))->validate();
        $d['award_category_id'] = (int) $d['award_category_id'];
        $d['active'] = (bool) $d['active'];

        return $this->save($actor, 'award-definition.save', 'awards', $d, $policy, function () use ($d, $id) {
            $row = $id === null ? new AwardDefinition : AwardDefinition::lockForUpdate()->findOrFail($id);
            if ($id !== null) {
                abort_unless((int) $row->award_category_id === $d['award_category_id'], 404);
                $this->revision($row, $d);
            }
            $row->fill(array_diff_key($d, ['operation_id' => true, 'revision' => true]) + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('recognition.award_definition_saved', $row, [], ['revision' => $row->revision]);

            return $row;
        }, AwardDefinition::class, $id);
    }

    public function window(User $actor, array $input): AwardWindow
    {
        $a = app(RecognitionAuthority::class);
        $a->staff($actor, 'awards', true);
        $category = AwardCategory::findOrFail($input['award_category_id'] ?? null);
        $policy = fn () => abort_unless($a->categoryAdmin($actor, $category->fresh(), true), 403);
        $policy();
        $d = Validator::make($input, RecognitionRules::for('window', false))->validate();
        foreach (['revision', 'award_category_id', 'session_year_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }

        return $this->save($actor, 'award-window.save', 'awards', $d, $policy, function () use ($d) {
            $year = SessionYear::findOrFail($d['session_year_id']);
            abort_unless($d['opens_on'] >= substr((string) $year->start_date, 0, 10) && $d['closes_on'] <= substr((string) $year->end_date, 0, 10), 422, 'Award windows must fit their school year.');
            $row = AwardWindow::where('award_category_id', $d['award_category_id'])->where('session_year_id', $d['session_year_id'])->lockForUpdate()->first();
            abort_unless((int) ($row?->revision ?? 0) === $d['revision'], 409);
            $row ??= new AwardWindow;
            $row->fill(array_diff_key($d, ['operation_id' => true, 'revision' => true]) + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('recognition.award_window_saved', $row, [], ['revision' => $row->revision]);

            return $row;
        }, AwardWindow::class, null);
    }

    public function administrator(User $actor, array $input, ?int $id = null): AwardCategoryAdmin
    {
        $a = app(RecognitionAuthority::class);
        $a->manage($actor, 'awards');
        $d = Validator::make($input, RecognitionRules::for('administrator', $id !== null))->validate();
        foreach (['award_category_id', 'user_id'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        $d['active'] = (bool) $d['active'];

        return $this->save($actor, 'award-admin.save', 'awards', $d, fn () => $a->manage($actor, 'awards'), function () use ($d, $id) {
            AwardCategory::findOrFail($d['award_category_id']);
            $person = User::findOrFail($d['user_id']);
            $fresh = app(RecognitionAuthority::class)->staff($person, 'awards');
            abort_unless(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== [], 422, 'Choose an active school staff member.');
            $row = $id === null ? new AwardCategoryAdmin : AwardCategoryAdmin::lockForUpdate()->findOrFail($id);
            if ($id !== null) {
                $this->revision($row, $d);
                abort_unless((int) $row->award_category_id === $d['award_category_id'] && (int) $row->user_id === $d['user_id'], 422);
            }
            $row->fill(array_diff_key($d, ['operation_id' => true, 'revision' => true]) + ['revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('recognition.award_admin_saved', $row, [], ['revision' => $row->revision, 'user_id' => $d['user_id']]);

            return $row;
        }, AwardCategoryAdmin::class, $id);
    }
}
