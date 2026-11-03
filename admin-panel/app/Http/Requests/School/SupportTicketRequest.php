<?php

declare(strict_types=1);

namespace App\Http\Requests\School;

use App\Services\Platform\Support\SchoolSupportTickets;
use Illuminate\Foundation\Http\FormRequest;

/** Settings > Navigate Ed support (Action Plan SA.17); SchoolSupportTickets checks the same right and rules again. */
class SupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(SchoolSupportTickets::class)->authorise($this->user());

        return true;
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'support-tickets.store' => SchoolSupportTickets::openRules(),
            'support-tickets.messages.store' => SchoolSupportTickets::messageRules(),
            default => [],
        };
    }
}
