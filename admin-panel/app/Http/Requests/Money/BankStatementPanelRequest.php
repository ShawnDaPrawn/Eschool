<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\BankStatements;

class BankStatementPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return BankStatements::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'bank-statements.index';
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return $this->readRules() + ['status' => ['sometimes', 'in:unmatched,matched,excluded,all'], 'account_id' => ['sometimes', 'integer', 'min:1']];
        }

        return $this->bindingRules() + match (true) {
            $this->routeIs('bank-statements.upload') => ['statement' => ['required', 'file', 'max:256', 'extensions:csv', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel']],
            $this->routeIs('bank-statements.import') => BankStatements::importRules(),
            $this->routeIs('bank-statements.review') => BankStatements::selectionRules(),
            default => BankStatements::resolveRules(),
        };
    }
}
