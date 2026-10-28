<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeLateChargePolicies;
use App\Services\Money\FeeLateCharges;

class FeeLateChargePanelRequest extends MoneyPanelRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->routeIs('fee-late-charges.save-policy')) {
            $this->merge(['enabled' => $this->boolean('enabled')]);
        }
    }

    protected function permission(): string
    {
        return $this->routeIs('fee-late-charges.policy', 'fee-late-charges.save-policy') ? FeeLateChargePolicies::PERMISSION : FeeLateCharges::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return $this->routeIs('fee-late-charges.policy', 'fee-late-charges.save-policy') ? 'fee-late-charges.policy' : 'fee-late-charges.index';
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['account_id' => ['sometimes', 'integer', 'min:1'], 'original_invoice_line_id' => ['sometimes', 'integer', 'min:1'], 'holder_user_id' => ['required_with:original_invoice_line_id', 'integer', 'min:1'], 'currency' => ['sometimes', 'nullable', 'string', 'regex:/\A[A-Z]{3}\z/D']];
        }

        return $this->bindingRules() + ($this->routeIs('fee-late-charges.save-policy') ? FeeLateChargePolicies::rules() : FeeLateCharges::rules($this->routeIs('fee-late-charges.post')));
    }
}
