<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Services\Commerce\TicketCounters;

class TicketPortalRequest extends VendorPortalRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('ticket_number'))) {
            $this->merge(['ticket_number' => strtoupper(trim($this->input('ticket_number')))]);
        }
        if (is_string($this->input('qr_token')) && str_starts_with($this->input('qr_token'), 'navigateed:ticket:')) {
            $this->merge(['qr_token' => substr($this->input('qr_token'), 18)]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return [];
        }
        $bind = ['expected_school_id' => ['required', 'integer', 'min:1'], 'expected_account_id' => ['required', 'integer', 'min:1']];

        return $bind + ($this->routeIs('vendor.tickets.redeem') ? TicketCounters::redeemRules() : TicketCounters::lookupRules());
    }
}
