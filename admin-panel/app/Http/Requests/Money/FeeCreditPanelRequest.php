<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeCreditNotes;

class FeeCreditPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeCreditNotes::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-credit-notes.index';
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['account_id' => ['sometimes', 'integer', 'min:1'], 'invoice_line_id' => ['sometimes', 'integer', 'min:1'], 'holder_user_id' => ['required_with:invoice_line_id', 'integer', 'min:1']];
        }

        return $this->bindingRules() + FeeCreditNotes::rules($this->routeIs('fee-credit-notes.post'));
    }
}
