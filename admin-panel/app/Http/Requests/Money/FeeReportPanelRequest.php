<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeAccountingSettings;
use App\Services\Money\FeePayments;
use App\Services\Money\FeeReporting;

class FeeReportPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeePayments::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-reporting.index';
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['currency' => ['sometimes', 'regex:/\A[A-Z]{3}\z/D'], 'status' => ['sometimes', 'in:all,overdue,credit']];
        }

        return $this->bindingRules() + ($this->routeIs('fee-reporting.settings-save') ? FeeAccountingSettings::rules() : FeeReporting::exportRules());
    }
}
