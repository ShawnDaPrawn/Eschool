<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\CashupPolicy;
use App\Services\Money\Cashups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CashupPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return CashupPolicy::MANAGE;
    }

    protected function returnRoute(): string
    {
        return 'cashups.index';
    }

    public function authorize(): bool
    {
        $actor = app(CashupPolicy::class)->finance($this->user());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? ['operator_id' => ['sometimes', 'integer', 'min:1'], 'currency' => ['sometimes', 'string', 'regex:/\\A[A-Z]{3}\\z/D'], 'counted_on' => ['sometimes', 'date_format:Y-m-d']] : $this->bindingRules() + Cashups::rules();
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Daily cash-up');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route('cashups.index');
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422));
    }
}
