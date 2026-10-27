<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeCharges;
use App\Services\Money\FeeItems;

class FeeItemPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeItems::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return $this->routeIs('fee-item-charges.*') ? 'fee-item-charges.index' : 'fee-items.index';
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['fee_item_id' => ['sometimes', 'integer', 'min:1'], 'fee_account_id' => ['sometimes', 'integer', 'min:1'], 'session_year_id' => ['sometimes', 'integer', 'min:1']];
        }

        return $this->bindingRules() + ($this->routeIs('fee-items.save', 'fee-items.create') ? FeeItems::rules() : FeeCharges::rules($this->routeIs('fee-item-charges.post')));
    }
}
