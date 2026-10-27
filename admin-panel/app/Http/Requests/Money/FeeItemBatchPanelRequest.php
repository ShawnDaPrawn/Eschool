<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeItemBatches;
use App\Services\Money\FeeItems;

class FeeItemBatchPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeItems::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-item-batches.index';
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isMethodSafe()) {
            $this->authorize();
            if (is_string($this->input('source_reference'))) {
                $this->merge(['source_reference' => mb_strtoupper(trim($this->input('source_reference')))]);
            }
        }
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() + ['fee_item_id' => ['sometimes', 'integer', 'min:1']] : $this->bindingRules() + FeeItemBatches::rules($this->routeIs('fee-item-batches.post'));
    }
}
