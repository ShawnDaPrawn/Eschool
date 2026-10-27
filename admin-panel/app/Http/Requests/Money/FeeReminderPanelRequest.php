<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeePayments;
use App\Services\Money\FeeReminderSettings;

class FeeReminderPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeePayments::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-reminders.index';
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + FeeReminderSettings::rules();
    }
}
