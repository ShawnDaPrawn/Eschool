<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeDiscountRuns;
use App\Services\Money\FeeDiscounts;
use App\Services\Platform\Ledger\Money;

class FeeDiscountPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeDiscounts::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-discounts.index';
    }

    protected function prepareForValidation(): void
    {
        if (! $this->routeIs('fee-discounts.create', 'fee-discounts.save')) {
            return;
        }
        $this->merge(['amount_value' => 0, 'minimum_paid_bp' => null]);
        $value = $this->input('discount_value');
        if (is_string($value) && preg_match('/\A\d{1,10}(?:\.\d{1,2})?\z/D', $value) === 1) {
            if ($this->input('amount_kind') === 'percent' && Money::fromDecimalString($value)->cents <= 10000) {
                $this->merge(['amount_value' => Money::fromDecimalString($value)->cents]);
            } elseif ($this->input('amount_kind') === 'fixed') {
                $this->merge(['amount_value' => Money::fromDecimalString($value)->cents]);
            }
        }
        $paid = $this->input('minimum_paid_percent');
        if (is_string($paid) && preg_match('/\A\d{1,3}(?:\.\d{1,2})?\z/D', $paid) === 1) {
            $this->merge(['minimum_paid_bp' => Money::fromDecimalString($paid)->cents]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['account_id' => ['sometimes', 'integer', 'min:1'], 'rule_id' => ['sometimes', 'integer', 'min:1']];
        }
        $rules = match (true) {
            $this->routeIs('fee-discounts.create', 'fee-discounts.save') => FeeDiscounts::rules() + ['discount_value' => ['required', 'string', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'], 'minimum_paid_percent' => ['nullable', 'string', 'regex:/\A\d{1,3}(?:\.\d{1,2})?\z/D']],
            $this->routeIs('fee-discounts.transition') => FeeDiscountRuns::operationRules() + ['action' => ['required', 'in:approve,archive']],
            $this->routeIs('fee-discounts.prepare') => FeeDiscountRuns::prepareRules(),
            default => FeeDiscountRuns::operationRules(),
        };

        return $this->bindingRules() + $rules;
    }
}
