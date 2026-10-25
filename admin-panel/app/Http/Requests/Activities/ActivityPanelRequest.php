<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Http\Controllers\Panel\Activities\ActivityWorkbenchController;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivitySession;
use App\Services\Activities\CommunityAuthority;
use App\Services\Panel\SessionYearContext;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ActivityPanelRequest extends ActivityRequest
{
    public function authorize(): bool
    {
        app(CommunityAuthority::class)->manage($this->user());
        // The route group is navigation context, never authority for an
        // unrelated session. Bind before dispatch and derive its actual year.
        $session = $this->action() === 'attendance-save'
            ? ActivitySession::where('activity_group_id', (int) $this->route('group'))->findOrFail((int) $this->route('session'))
            : null;
        $allowed = parent::authorize();
        if (! $this->isMethodSafe()) {
            $groupId = $session?->activity_group_id ?? $this->route('group');
            $year = $groupId === null ? $this->input('session_year_id') : ActivityGroup::findOrFail((int) $groupId)->session_year_id;
            $current = app(SessionYearContext::class)->currentId();
            abort_if($current !== null && is_scalar($year) && ctype_digit((string) $year) && (int) $year > 0 && (int) $year !== $current, 423, __('This school year is read-only. Select the current school year to make changes.'));
        }

        return $allowed;
    }

    protected function prepareForValidation(): void
    {
        if ($this->action() === 'attendance-save' && is_array($this->input('entries'))) {
            $this->merge(['entries' => array_values(array_filter($this->input('entries'), fn ($row) => is_array($row) && ($row['status'] ?? '') !== ''))]);
        }
    }

    public function rules(): array
    {
        return parent::rules() + ['group_id' => ['sometimes', 'integer', 'min:1'], 'edit' => ['sometimes', 'in:membership,staff,session,material'], 'record_id' => ['sometimes', 'integer', 'min:1'], 'session_id' => ['sometimes', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(ActivityWorkbenchController::class)->invalid($this, $validator->errors()->toArray(), 422));
    }
}
