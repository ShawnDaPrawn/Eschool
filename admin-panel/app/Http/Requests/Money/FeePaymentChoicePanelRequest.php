<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeAccounts;
use App\Services\Money\FeePaymentChoices;

class FeePaymentChoicePanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeAccounts::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-accounts.index';
    }

    public function rules(): array
    {
        return $this->bindingRules() + FeePaymentChoices::rules();
    }
}
