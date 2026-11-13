<?php

declare(strict_types=1);

namespace App\Http\Requests\Money;

use App\Services\Money\FeePayments;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\Bursaries;
use App\Services\SchoolBusiness\Giving;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Browser adapter only: domain services retain all permission, owner and posting checks. */
class SchoolBusinessPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        $school = app(CurrentSchool::class);
        abort_unless($this->user() && $school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id
                && (int) $this->input('expected_school_id') === $school->id(), 403);
            abort_if($this->query->count() > 0, 422);
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->routeIs('giving-panel.donate') && is_string($this->input('source_reference'))) {
            $this->merge(['source_reference' => FeePayments::reference($this->input('source_reference'))]);
        }
        foreach (['amount' => 'amount_cents', 'goal' => 'goal_cents', 'award_amount' => 'award_value'] as $field => $target) {
            // Browser amounts are decimal strings. Never convert through a float.
            if (is_string($this->input($field)) && preg_match('/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/D', $this->input($field))) {
                $this->merge([$target => Money::fromDecimalString($this->input($field))->cents]);
            }
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['section' => ['sometimes', 'string', 'max:32'], 'search' => ['sometimes', 'nullable', 'string', 'max:120'],
                'state' => ['sometimes', 'in:submitted,awarded,declined,withdrawn,expired'], 'page' => ['sometimes', 'integer', 'between:1,100000']];
        }
        $binding = ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
        $decimal = ['nullable', 'string', 'regex:/\A[0-9]{1,10}(?:\.[0-9]{1,2})?\z/D'];

        return $binding + match (true) {
            $this->routeIs('bursary-panel.note') => Bursaries::rules('note'),
            $this->routeIs('bursary-panel.decide') => Bursaries::rules('decide') + ['award_amount' => $decimal],
            $this->routeIs('bursary-panel.discount') => Bursaries::rules('discount'),
            $this->routeIs('giving-panel.create') => Giving::rules('campaign') + ['goal' => $decimal],
            $this->routeIs('giving-panel.close') => Giving::rules('close'),
            $this->routeIs('giving-panel.pledge') => Giving::rules('pledge') + ['amount' => ['required', ...$decimal]],
            $this->routeIs('giving-panel.donate') => Giving::rules('donation') + ['amount' => ['required', ...$decimal]],
            $this->routeIs('sponsored-places.add') => ['learner_id' => ['required', 'integer', 'min:1']],
            $this->routeIs('sponsored-places.remove') => ['confirmed' => ['required', 'accepted']],
            default => [],
        };
    }

    public function domainInput(): array
    {
        return $this->safe()->except(['expected_account_id', 'expected_school_id', 'amount', 'goal', 'award_amount']);
    }

    protected function failedValidation(Validator $validator): void
    {
        // Private reasons and donor details stay in the open form, never in flashed session input.
        throw new HttpResponseException(response()->json(['message' => __('Review the highlighted fields.'), 'errors' => $validator->errors()], 422));
    }
}
