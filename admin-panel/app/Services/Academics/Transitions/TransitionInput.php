<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use Illuminate\Support\Facades\Validator;

class TransitionInput
{
    public static function rules(): array
    {
        return ['kind' => ['required', 'in:join,leave,subject_change'], 'learner_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'],
            'effective_on' => ['required', 'date_format:Y-m-d'], 'reason' => ['required', 'string', 'min:3', 'max:2000'],
            'class_section_id' => ['required_if:kind,join', 'integer', 'min:1'], 'group_ids' => ['present_if:kind,join', 'array', 'max:30'], 'group_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'fee_prorata_requested' => ['required_if:kind,join', 'boolean'], 'source_membership_id' => ['required_if:kind,subject_change', 'integer', 'min:1'],
            'target_group_id' => ['required_if:kind,subject_change', 'integer', 'min:1'], 'mark_rule_override' => ['sometimes', 'nullable', 'in:catch_up,excused,carried']];
    }

    public function normalise(array $input): array
    {
        if (is_string($input['reason'] ?? null)) {
            $input['reason'] = trim($input['reason']);
        }
        $data = Validator::make($input, self::rules())->validate();
        $normal = ['kind' => $data['kind'], 'learner_id' => (int) $data['learner_id'], 'session_year_id' => (int) $data['session_year_id'], 'effective_on' => $data['effective_on'], 'reason' => trim($data['reason'])];
        if ($normal['kind'] === 'join') {
            $ids = array_map('intval', $data['group_ids']);
            sort($ids);
            $normal += ['class_section_id' => (int) $data['class_section_id'], 'group_ids' => $ids, 'fee_prorata_requested' => (bool) $data['fee_prorata_requested']];
        }
        if ($normal['kind'] === 'subject_change') {
            $normal += ['source_membership_id' => (int) $data['source_membership_id'], 'target_group_id' => (int) $data['target_group_id']];
        }
        if ($normal['kind'] !== 'leave') {
            $normal['mark_rule_override'] = $data['mark_rule_override'] ?? null;
        }

        return $normal;
    }
}
