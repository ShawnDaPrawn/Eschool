<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\BillRuns;

class BillRunPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return BillRuns::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-bill-runs.index';
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + match (true) {
            $this->routeIs('fee-bill-runs.prepare') => BillRuns::prepareRules(),
            $this->routeIs('fee-bill-runs.post') => BillRuns::postingRules(),
            default => BillRuns::operationRules(),
        };
    }
}
