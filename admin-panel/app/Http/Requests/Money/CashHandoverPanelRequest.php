<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\CashCustody;

class CashHandoverPanelRequest extends CashupPanelRequest
{
    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return [];
        }

        return $this->bindingRules() + ($this->routeIs('cashups.handovers.propose') ? CashCustody::proposalRules() : CashCustody::outcomeRules());
    }
}
