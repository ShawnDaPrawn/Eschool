<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeLifecyclePolicies;

class FeeLifecyclePanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeLifecyclePolicies::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-lifecycle.index';
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + FeeLifecyclePolicies::rules();
    }
}
