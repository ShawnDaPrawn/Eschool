<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Http\Controllers\Panel\Growth\ProgressPanelController;
use App\Services\Growth\ProgressPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProgressPanelRequest extends ProgressNotesRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('learner-notes.'));
    }

    public function authorize(): bool
    {
        return app(ProgressPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        $context = ['mode' => ['sometimes', 'in:academic,counselling,assignments'], 'learner_id' => ['sometimes', 'integer', 'min:1'],
            'group_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'subject_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'session_year_id' => ['sometimes', 'integer', 'min:1'], 'visibility' => ['sometimes', 'in:tutor,staff,counselling'],
            'after' => ['sometimes', 'integer', 'min:0'], 'note' => ['sometimes', 'nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1'], 'term_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        if ($this->isMethodSafe()) {
            return $context;
        }

        $operationRules = parent::rules();
        if ($this->operation() === 'configure') {
            $operationRules = ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0']];
            foreach (['high_pct', 'secure_pct', 'minimum_coverage_pct'] as $field) {
                $operationRules[$field] = ['required', 'numeric', 'regex:/^\d{1,3}(\.\d{1,2})?$/', 'between:0,100'];
            }
        }

        return $operationRules + $context + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1'],
            'staff_user_id' => [in_array($this->operation(), ['assign', 'revoke-assignment'], true) ? 'required' : 'sometimes', 'integer', 'min:1'],
            'review_required' => ['sometimes', 'in:1'], 'confirm_current' => ['exclude_unless:review_required,1', 'required', 'accepted']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(ProgressPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
