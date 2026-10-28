<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeLeaverPolicies;
use App\Services\Money\FeeLeaverRuns;

class FeeLeaverPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeLeaverPolicies::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-leavers.index';
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['account_id' => ['sometimes', 'integer', 'min:1'], 'invoice_id' => ['sometimes', 'integer', 'min:1']];
        }

        return $this->bindingRules() + match (true) {
            $this->routeIs('fee-leavers.policy') => FeeLeaverPolicies::rules(),$this->routeIs('fee-leavers.prepare') => FeeLeaverRuns::prepareRules(),default => FeeLeaverRuns::operationRules()
        };
    }
}
