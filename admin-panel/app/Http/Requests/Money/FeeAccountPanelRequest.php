<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeeAccounts;

class FeeAccountPanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return FeeAccounts::PERMISSION;
    }

    protected function returnRoute(): string
    {
        return 'fee-accounts.index';
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('fee-accounts.save') && ! $this->exists('learner_ids')) {
            $this->merge(['learner_ids' => []]);
        }
        if (is_array($holders = $this->input('holders'))) {
            $this->merge(['holders' => array_values(array_filter($holders, fn ($h) => ! is_array($h) || ($h['user_id'] ?? '') !== '' || ($h['share_percent'] ?? '') !== ''))]);
        }
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? $this->readRules() : $this->bindingRules() + ($this->routeIs('fee-accounts.archive') ? FeeAccounts::operationRules() : FeeAccounts::rules($this->routeIs('fee-accounts.save')));
    }
}
