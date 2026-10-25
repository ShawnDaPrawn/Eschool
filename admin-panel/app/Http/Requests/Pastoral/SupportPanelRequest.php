<?php

declare(strict_types=1);

namespace App\Http\Requests\Pastoral;

use App\Http\Controllers\Panel\Pastoral\SupportPanelController;
use App\Services\Pastoral\SupportPanelScope;
use App\Services\Pastoral\SupportPlans;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SupportPanelRequest extends FormRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('support-plans.'));
    }

    public function authorize(): bool
    {
        return app(SupportPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        $common = ['audience' => ['sometimes', 'in:professional,principal,teacher'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'min:1'], 'history_page' => ['sometimes', 'integer', 'min:1'], 'plan' => ['sometimes', 'integer', 'min:1'], 'history' => ['sometimes', 'in:reviews,versions,disclosures']];
        if ($this->isMethodSafe()) {
            return $common;
        }

        return $common + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1'], 'review_required' => ['sometimes', 'in:1'], 'confirm_current' => ['exclude_unless:review_required,1', 'required', 'accepted']] + SupportPlans::rules($this->operation() === 'create' ? 'save' : $this->operation());
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(SupportPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
