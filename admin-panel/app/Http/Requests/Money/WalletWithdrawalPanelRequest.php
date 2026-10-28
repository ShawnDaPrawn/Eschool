<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Policies\Money\WalletPolicy;
use App\Services\Money\FeePayments;
use App\Services\Money\WalletWithdrawals;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class WalletWithdrawalPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return WalletPolicy::FINANCE;
    }

    protected function returnRoute(): string
    {
        return 'wallet-withdrawals.index';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('payout_reference'))) {
            $this->merge(['payout_reference' => FeePayments::reference($this->input('payout_reference'))]);
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
        if ($this->isMethodSafe()) {
            return $this->readRules();
        }
        $rules = match (true) {
            $this->routeIs('wallet-withdrawals.policy') => WalletWithdrawals::policyRules(),
            $this->routeIs('wallet-withdrawals.slots.publish') => WalletWithdrawals::slotRules(),
            $this->routeIs('wallet-withdrawals.offboarding') => WalletWithdrawals::requestRules() + WalletWithdrawals::approvalRules() + ['holder_user_id' => ['required', 'integer', 'min:1']],
            $this->routeIs('wallet-withdrawals.approve') => WalletWithdrawals::approvalRules(),
            $this->routeIs('wallet-withdrawals.paid') => WalletWithdrawals::payoutRules(),
            default => WalletWithdrawals::reasonRules(),
        };

        return $this->bindingRules() + $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Adult wallet withdrawals');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
