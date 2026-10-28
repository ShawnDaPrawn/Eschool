<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\FeePayments;
use App\Services\Money\WalletDeposits;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletDepositPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return WalletPolicy::FINANCE;
    }

    protected function returnRoute(): string
    {
        return 'wallet-deposits.index';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('source_reference'))) {
            $this->merge(['source_reference' => FeePayments::reference($this->input('source_reference'))]);
        }
    }

    public function authorize(): bool
    {
        $actor = app(WalletPolicy::class)->finance($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $actor->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('wallet-deposits.policy')) {
            return $this->bindingRules() + WalletDeposits::policyRules();
        }

        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + ($this->routeIs('wallet-deposits.verify') ? WalletDeposits::verifyRules() : WalletDeposits::finishRules('rejected'));
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Parent wallet deposits');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
