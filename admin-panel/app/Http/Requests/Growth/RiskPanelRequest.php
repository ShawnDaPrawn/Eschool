<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Http\Controllers\Panel\Growth\RiskPanelController;
use App\Services\Growth\RiskPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RiskPanelRequest extends RiskRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('risk-interventions.'));
    }

    public function authorize(): bool
    {
        return app(RiskPanelScope::class)->authorize($this);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $owner = $this->input('owner_choice');
        if (is_string($owner) && preg_match('/^([1-9][0-9]*):(academic|professional|leadership)$/', $owner, $m)) {
            $this->merge(['owner_id' => (int) $m[1], 'owner_authority' => $m[2]]);
        }
        if ($this->operation() === 'configure') {
            foreach (['academic_threshold_pct' => 'academic_threshold_bp', 'minimum_coverage_pct' => 'minimum_coverage_bp', 'absence_threshold_pct' => 'absence_threshold_bp'] as $field => $target) {
                $v = $this->input($field);
                if (is_scalar($v) && preg_match('/^\d{1,3}(\.\d{1,2})?$/', (string) $v)) {
                    $parts = explode('.', (string) $v, 2);
                    $this->merge([$target => (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0')]);
                }
            }
        }
    }

    public function rules(): array
    {
        $context = ['mode' => ['sometimes', 'in:queue,signals,classroom,assignments,settings'], 'learner_id' => ['sometimes', 'integer', 'min:1'], 'group_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'session_year_id' => ['sometimes', 'integer', 'min:1'], 'intervention' => ['sometimes', 'nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'learner_page' => ['sometimes', 'integer', 'min:1'], 'owner_page' => ['sometimes', 'integer', 'min:1'], 'staff_page' => ['sometimes', 'integer', 'min:1']];
        if ($this->isMethodSafe()) {
            return $context;
        }

        $rules = parent::rules();
        if ($this->operation() === 'update') {
            unset($rules['session_year_id']);
        }

        return $rules + $context + ['professional_id' => [in_array($this->operation(), ['assign', 'revoke'], true) ? 'required' : 'sometimes', 'integer', 'min:1'],
            'expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1'],
            'review_required' => ['sometimes', 'in:1'], 'confirm_current' => ['exclude_unless:review_required,1', 'required', 'accepted']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(RiskPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
