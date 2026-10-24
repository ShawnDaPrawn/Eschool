<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\AcademicTransitionPolicy;
use App\Models\Academics\GradeLevel;
use App\Models\Platform\RetentionRule;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransitionPolicies
{
    public function grade(int $id): array
    {
        GradeLevel::findOrFail($id);
        $row = AcademicTransitionPolicy::where('grade_level_id', $id)->first();

        return ['grade_level_id' => $id, 'join_mark_rule' => $row?->join_mark_rule ?? 'catch_up', 'change_mark_rule' => $row?->change_mark_rule ?? 'catch_up', 'revision' => $row?->revision ?? 0];
    }

    public static function rules(): array
    {
        return ['join_mark_rule' => ['required', 'in:catch_up,excused'], 'change_mark_rule' => ['required', 'in:catch_up,excused,carried'], 'revision' => ['required', 'integer', 'min:0']];
    }

    public function save(User $actor, int $grade, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        GradeLevel::findOrFail($grade);
        $data = Validator::make($input, self::rules())->validate();

        return DB::transaction(function () use ($actor, $grade, $data) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(AcademicAuthority::class)->manage($actor);
            $row = AcademicTransitionPolicy::where('grade_level_id', $grade)->first() ?? new AcademicTransitionPolicy(['grade_level_id' => $grade, 'revision' => 0]);
            abort_unless($row->revision === (int) $data['revision'], 409, __('This grade policy changed. Review the latest defaults.'));
            $before = $row->getAttributes();
            $row->fill(['join_mark_rule' => $data['join_mark_rule'], 'change_mark_rule' => $data['change_mark_rule'], 'revision' => $row->revision + 1])->save();
            Audit::record('academics.transition_policy_saved', $row, $before, $row->getAttributes());

            return $this->grade($grade);
        });
    }

    public function retention(): array
    {
        $row = RetentionRule::where('record_type', 'academic_exit_packages')->first();

        return ['days' => $row?->days ?? (int) config('academic_transitions.exit_export_days'), 'maximum_days' => (int) config('academic_transitions.maximum_exit_export_days'), 'revision' => $row?->revision ?? 0, 'educational_records' => 'retain_until_approved_schedule'];
    }

    public function retainHistory(): void
    {
        $rule = RetentionRule::firstOrCreate(['record_type' => 'academic_records'], ['action' => 'retain', 'days' => null, 'revision' => 1]);
        if ($rule->wasRecentlyCreated) {
            Audit::record('privacy.retention_rule_created', $rule, [], $rule->getAttributes());
        }
    }

    public function saveRetention(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, ['days' => ['required', 'integer', 'min:1', 'max:'.config('academic_transitions.maximum_exit_export_days')], 'revision' => ['required', 'integer', 'min:0']])->validate();

        return DB::transaction(function () use ($actor, $data) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(AcademicAuthority::class)->manage($actor);
            $this->retainHistory();
            $row = RetentionRule::where('record_type', 'academic_exit_packages')->first() ?? new RetentionRule(['record_type' => 'academic_exit_packages', 'revision' => 0]);
            abort_unless($row->revision === (int) $data['revision'], 409, __('The export retention rule changed. Review it before saving.'));
            $before = $row->getAttributes();
            $row->fill(['action' => 'expire_artifact', 'days' => (int) $data['days'], 'revision' => $row->revision + 1])->save();
            Audit::record('academics.export_retention_saved', $row, $before, $row->getAttributes());

            return $this->retention();
        });
    }
}
