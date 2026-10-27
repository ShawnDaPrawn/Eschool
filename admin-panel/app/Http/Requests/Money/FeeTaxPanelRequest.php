<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeItems;
use App\Services\Money\FeeTaxSettings;

class FeeTaxPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeItems::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-items.tax';
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + FeeTaxSettings::rules();
    }
}
