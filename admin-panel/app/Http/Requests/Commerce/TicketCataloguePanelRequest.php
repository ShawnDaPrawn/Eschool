<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Http\Requests\Money\MoneyPanelRequest;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TicketCataloguePanelRequest extends MoneyPanelRequest
{
    protected function permission(): string
    {
        return VendorPolicy::MANAGE;
    }

    protected function returnRoute(): string
    {
        return 'ticket-events.index';
    }

    public function authorize(): bool
    {
        app(TicketCatalogues::class)->finance($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? ['event_id' => ['sometimes', 'integer', 'min:1'], 'type_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'types_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'new_event' => ['sometimes', 'boolean']] : $this->bindingRules() + ($this->routeIs('ticket-events.event') ? TicketCatalogues::eventRules() : TicketCatalogues::typeRules());
    }

    protected function failedValidation(Validator $validator): void
    {
        $title = __('Events and admission tickets');
        $message = implode(' ', $validator->errors()->all());
        $draft = [];
        $returnUrl = route($this->returnRoute());
        throw new HttpResponseException(response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422)->header('Cache-Control', 'private, no-store'));
    }
}
