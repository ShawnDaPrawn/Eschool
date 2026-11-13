<?php

declare(strict_types=1);

namespace App\Http\Requests\Commerce;

use App\Http\Controllers\Panel\Money\TicketCataloguePanelController;
use App\Http\Requests\Money\MoneyPanelRequest;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

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
        return $this->isMethodSafe() ? ['section' => ['sometimes', 'string', 'max:32'], 'revisions_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'event_id' => ['sometimes', 'integer', 'min:1'], 'type_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'types_page' => ['sometimes', 'integer', 'min:1', 'max:1000000'], 'new_event' => ['sometimes', 'boolean']] : $this->bindingRules() + ($this->routeIs('ticket-events.event') ? TicketCatalogues::eventRules() + ['confirmed_cancellation' => $this->input('status') === 'cancelled' ? ['required', 'accepted'] : ['sometimes', 'accepted']] : TicketCatalogues::typeRules());
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException($this->formFailure($validator->errors()->all()));
    }

    public function formFailure(array $messages): Response
    {
        $eventForm = $this->routeIs('ticket-events.event');
        foreach (['id', 'event_id'] as $field) {
            if ($this->has($field) && filter_var($this->input($field), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                abort(422, __('Review the event and admission type selection.'));
            }
        }
        $this->attributes->set('ticketDraft', array_filter($this->except(['expected_school_id', 'expected_account_id', '_token']), fn ($value) => is_string($value) || is_int($value)));
        $this->attributes->set('ticketErrors', $messages);
        $this->merge(['event_id' => $eventForm ? $this->input('id') : $this->input('event_id'), 'type_id' => $eventForm ? null : $this->input('id'), 'section' => $eventForm ? ($this->input('status') === 'cancelled' ? 'cancel' : 'event') : 'type']);

        $response = app(TicketCataloguePanelController::class)->index($this)->setStatusCode(422);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;

    }
}
