<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeePayments;
use App\Services\Money\FeeRefundPolicies;
use App\Services\Money\FeeRefunds;

class FeeRefundPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeRefundPolicies::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-refunds.index';
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('fee-refunds.payout') && is_string($this->input('payout_reference'))) {
            $this->merge(['payout_reference' => FeePayments::reference($this->input('payout_reference'))]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['payment_id' => ['sometimes', 'integer', 'min:1']];
        }

        return $this->bindingRules() + match (true) {
            $this->routeIs('fee-refunds.policy') => FeeRefundPolicies::rules(),$this->routeIs('fee-refunds.prepare') => FeeRefunds::prepareRules(),$this->routeIs('fee-refunds.payout') => FeeRefunds::payoutRules(),$this->routeIs('fee-refunds.cancel') => FeeRefunds::cancelRules(),default => FeeRefunds::actionRules()
        };
    }
}
