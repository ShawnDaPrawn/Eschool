<?php

declare(strict_types=1);

namespace App\Http\Requests\Wellbeing;

use App\Http\Controllers\Panel\Wellbeing\WellbeingPanelController;
use App\Services\Wellbeing\WellbeingPanelScope;
use App\Services\Wellbeing\WellbeingRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class WellbeingPanelRequest extends FormRequest
{
    public function operation(): string
    {
        return app(WellbeingPanelScope::class)->operation($this);
    }

    public function authorize(): bool
    {
        return app(WellbeingPanelScope::class)->authorize($this);
    }

    public function validationData(): array
    {
        return $this->isMethodSafe() ? $this->query->all() : $this->getInputSource()->all();
    }

    public function rules(): array
    {
        $common = ['mode' => ['sometimes', 'in:configuration,clinical'], 'page' => ['sometimes', 'integer', 'between:1,10000'],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'case' => ['sometimes', 'integer', 'min:1'], 'report' => ['sometimes', 'integer', 'min:1'],
            'category' => ['sometimes', 'integer', 'min:1'], 'managed_case' => ['sometimes', 'integer', 'min:1'], 'before' => ['sometimes', 'integer', 'min:1'],
            'professional_page' => ['sometimes', 'integer', 'between:1,10000'], 'professional_search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'calendar_from' => ['sometimes', 'date_format:Y-m-d'], 'calendar_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:calendar_from']];
        if ($this->isMethodSafe()) {
            return $common;
        }
        $identity = ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1'],
            'review_required' => ['sometimes', 'in:1'], 'confirm_current' => ['exclude_unless:review_required,1', 'required', 'accepted']];
        if ($this->operation() === 'case-choices') {
            return $common + $identity + ['starts_at' => ['sometimes', 'nullable', 'string', 'max:30'], 'ends_at' => ['sometimes', 'nullable', 'string', 'max:30'],
                'location' => ['sometimes', 'nullable', 'string', 'max:500'], 'notes' => ['sometimes', 'nullable', 'string', 'max:20000'],
                'professional_user_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'choice_direction' => ['sometimes', 'in:next,previous,search']];
        }
        $operation = match ($this->operation()) {
            'category-create', 'category-save' => 'category-save',
            'team-create', 'team-save', 'case-assignment-create', 'case-assignment-save' => 'assignment-save',
            'case-create' => 'case-open', 'appointment-create', 'appointment-save' => 'appointment-save',
            default => $this->operation(),
        };
        $rules = WellbeingRules::rules($operation);
        if ($operation === 'case-entry') {
            $rules['happened_at'] = ['required', 'date_format:Y-m-d\\TH:i'];
        }
        if ($operation === 'appointment-save') {
            $rules['starts_at'] = ['required', 'date_format:Y-m-d\\TH:i'];
            $rules['ends_at'] = ['required', 'date_format:Y-m-d\\TH:i', 'after:starts_at'];
        }

        return $common + $identity + $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(WellbeingPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
