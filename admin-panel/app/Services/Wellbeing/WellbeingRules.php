<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use Illuminate\Validation\Rule;

/** One validation contract for HTTP adapters and direct domain callers. */
class WellbeingRules
{
    public static function rules(string $operation): array
    {
        return match ($operation) {
            'category-save' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'name' => ['required', 'string', 'max:191'], 'emergency_guidance' => ['required', 'string', 'max:2000'], 'active' => ['required', 'boolean']],
            'assignment-save' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'user_id' => ['required', 'integer', 'min:1'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'active' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:2000']],
            'case-open' => ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1']],
            'report-submit' => ['secret' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'], 'category_id' => ['required', 'integer', 'min:1'], 'body' => ['required', 'string', 'max:20000'], 'urgency' => ['required', Rule::in(['routine', 'urgent'])], 'anonymous' => ['required', 'boolean'], 'acknowledged_guidance' => ['sometimes', 'boolean'], 'identify_consent' => ['sometimes', 'boolean'], 'learner_id' => ['nullable', 'integer', 'min:1']],
            'reporter-reply' => ['operation_id' => ['required', 'uuid'], 'body' => ['required', 'string', 'max:20000']],
            'identify' => ['revision' => ['required', 'integer', 'min:1'], 'identify_consent' => ['required', 'accepted'], 'learner_id' => ['nullable', 'integer', 'min:1']],
            'report-respond' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'body' => ['required', 'string', 'max:20000'], 'internal' => ['required', 'boolean'], 'status' => ['required', Rule::in(['open', 'triaged', 'resolved'])]],
            'case-entry' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'kind' => ['required', Rule::in(['note', 'escalation'])], 'body' => ['required', 'string', 'max:20000'], 'happened_at' => ['required', 'date', 'before_or_equal:now'], 'supersedes_id' => ['nullable', 'integer', 'min:1']],
            'case-link' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'incident_report_id' => ['required', 'integer', 'min:1']],
            'case-status' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'status' => ['required', Rule::in(['open', 'closed'])], 'reason' => ['required', 'string', 'max:2000']],
            'appointment-save' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'professional_user_id' => ['required', 'integer', 'min:1'], 'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'], 'location' => ['required', 'string', 'max:500'], 'notes' => ['present', 'nullable', 'string', 'max:20000'], 'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])]],
            default => throw new \InvalidArgumentException('Unknown wellbeing operation.'),
        };
    }
}
