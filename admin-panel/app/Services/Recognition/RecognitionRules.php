<?php

declare(strict_types=1);

namespace App\Services\Recognition;

final class RecognitionRules
{
    public static function normalise(array $input, string $operation): array
    {
        return $input + match ($operation) {
            'membership' => ['ends_on' => null],
            'awardCategory' => ['parent_id' => null, 'grade_level_id' => null, 'subject_id' => null, 'activity_group_id' => null],
            default => [],
        };
    }

    public static function for(string $operation, bool $existing = false): array
    {
        return match ($operation) {
            'configure' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'daily_staff_cap' => ['required', 'integer', 'min:1', 'max:1000000'], 'public_points_allowed' => ['required', 'boolean'], 'principal_approval_required' => ['required', 'boolean']],
            'house' => ['operation_id' => ['required', 'uuid'], 'revision' => [$existing === false ? 'sometimes' : 'required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:120'], 'emblem' => ['required', 'in:shield,star,mountain,river,forest,sun'], 'primary_colour' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'], 'secondary_colour' => ['required', 'regex:/^#[a-fA-F0-9]{6}$/'], 'active' => ['required', 'boolean']],
            'category' => ['operation_id' => ['required', 'uuid'], 'revision' => [$existing === false ? 'sometimes' : 'required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:120'], 'default_points' => ['required', 'integer', 'between:-10000,10000', 'not_in:0'], 'minimum_points' => ['required', 'integer', 'between:-10000,10000'], 'maximum_points' => ['required', 'integer', 'between:-10000,10000'], 'active' => ['required', 'boolean']],
            'membership' => ['operation_id' => ['required', 'uuid'], 'revision' => [$existing === false ? 'sometimes' : 'required', 'integer', 'min:1'], 'learner_id' => ['required', 'integer', 'min:1'], 'house_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['present', 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'active' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:2000']],
            'awardCategory' => ['operation_id' => ['required', 'uuid'], 'revision' => [$existing === false ? 'sometimes' : 'required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'], 'parent_id' => ['present', 'nullable', 'integer', 'min:1'], 'scope_kind' => ['required', 'in:school,grade,group'], 'grade_level_id' => ['present', 'nullable', 'integer', 'min:1'], 'subject_id' => ['present', 'nullable', 'integer', 'min:1'], 'activity_group_id' => ['present', 'nullable', 'integer', 'min:1'], 'active' => ['required', 'boolean']],
            'definition' => ['operation_id' => ['required', 'uuid'], 'revision' => [$existing === false ? 'sometimes' : 'required', 'integer', 'min:1'], 'award_category_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'], 'criteria' => ['required', 'string', 'max:4000'], 'frequency' => ['required', 'in:annual,repeatable'], 'active' => ['required', 'boolean']],
            'window' => ['activity_group_id' => ['sometimes', 'integer', 'min:1'], 'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'award_category_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'opens_on' => ['required', 'date_format:Y-m-d'], 'closes_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:opens_on'], 'status' => ['required', 'in:draft,open,closed']],
            'administrator' => ['operation_id' => ['required', 'uuid'], 'revision' => [$existing === false ? 'sometimes' : 'required', 'integer', 'min:1'], 'award_category_id' => ['required', 'integer', 'min:1'], 'user_id' => ['required', 'integer', 'min:1'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'active' => ['required', 'boolean']],
            'issue' => ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'point_category_id' => ['required', 'integer', 'min:1'], 'points' => ['required', 'integer', 'between:-10000,10000', 'not_in:0'], 'reason' => ['required', 'string', 'max:2000'], 'public' => ['required', 'boolean']],
            'reverse' => ['operation_id' => ['required', 'uuid'], 'reason' => ['required', 'string', 'max:2000']],
            'nominate' => ['operation_id' => ['required', 'uuid'], 'session_year_id' => ['required', 'integer', 'min:1'], 'grade_level_id' => ['required', 'integer', 'min:1'], 'learner_id' => ['required', 'integer', 'min:1'], 'award_definition_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:4000']],
            'review' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:approved,rejected'], 'reason' => ['required', 'string', 'max:4000']],
            'withdraw' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:4000']],
            'present' => ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'award_definition_id' => ['required', 'integer', 'min:1'], 'award_nomination_id' => ['present', 'nullable', 'integer', 'min:1'], 'presented_on' => ['required', 'date_format:Y-m-d'], 'event' => ['present', 'nullable', 'string', 'max:191']],
            'outside' => ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'], 'title' => ['required', 'string', 'max:160'], 'criteria' => ['required', 'string', 'max:4000'], 'outside_organisation' => ['required', 'string', 'max:160'], 'presented_on' => ['required', 'date_format:Y-m-d'], 'event' => ['present', 'nullable', 'string', 'max:191']],
            'void' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:4000']],
            default => abort(404),
        };
    }
}
