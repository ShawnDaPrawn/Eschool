<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Services\Commerce\TicketHolders;
use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(TicketHolders::class)->actor($this->user(), ! $this->isMethodSafe());
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422);

        return true;
    }

    public function rules(): array
    {
        return match (true) {
            $this->isMethodSafe() => ['page' => ['sometimes', 'integer', 'min:1', 'max:1000000']],
            $this->routeIs('api.v2.commerce.tickets.receive-code') => [],
            $this->routeIs('api.v2.commerce.tickets.transfer-preview') => ['receive_code' => ['required', 'string', 'max:8192']],
            default => TicketHolders::transferRules(),
        };
    }
}
