<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeAllocations;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;

class FeePaymentPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeePayments::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-payments.index';
    }

    protected function prepareForValidation(): void
    {
        if (is_string($reference = $this->input('source_reference'))) {
            $this->merge(['source_reference' => FeePayments::reference($reference)]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['account_id' => ['sometimes', 'integer', 'min:1'], 'charges_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'receipts_page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
        }

        return $this->bindingRules() + match (true) {
            $this->routeIs('fee-payments.policy') => FeePaymentSettings::rules(),
            $this->routeIs('fee-payments.allocate') => FeeAllocations::rules(),
            default => FeePayments::rules(),
        };
    }
}
