<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Panel\SessionYearContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Step one of the new-year wizard: next year's name, dates, terms and fee due
 * settings. Dates come from date fields (YYYY-MM-DD).
 */
class CreateNextYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('session-year-create');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('session_years', 'name')->whereNull('deleted_at')],
            'start_date' => ['required', 'date_format:Y-m-d'],
            // A year that has already ended could not be made current.
            'end_date' => ['required', 'date_format:Y-m-d', 'after:start_date', 'after_or_equal:today'],
            'fee_due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date', 'before_or_equal:end_date'],
            'fee_due_charges' => ['required', 'numeric', 'min:0', 'max:100'],
            'terms' => ['nullable', 'array', 'max:6'],
            'terms.*.name' => ['nullable', 'string', 'max:255', 'required_with:terms.*.start_date,terms.*.end_date'],
            'terms.*.start_date' => ['nullable', 'date_format:Y-m-d', 'required_with:terms.*.end_date', 'after_or_equal:start_date', 'before_or_equal:end_date'],
            'terms.*.end_date' => ['nullable', 'date_format:Y-m-d', 'required_with:terms.*.start_date', 'after_or_equal:start_date', 'before_or_equal:end_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $current = app(SessionYearContext::class)->current();
            if ($current && $this->filled('start_date') && (string) $this->input('start_date') <= (string) $current->start_date) {
                $validator->errors()->add('start_date', __('new_year_must_start_after', ['year' => $current->name]));
            }
            foreach ((array) $this->input('terms', []) as $index => $term) {
                if (! empty($term['start_date']) && ! empty($term['end_date']) && $term['end_date'] < $term['start_date']) {
                    $validator->errors()->add("terms.$index.end_date", __('new_year_term_ends_before_it_starts'));
                }
            }
        });
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => __('new_year_name'),
            'start_date' => __('new_year_starts_on'),
            'end_date' => __('new_year_ends_on'),
            'fee_due_date' => __('new_year_fee_due_date'),
            'fee_due_charges' => __('new_year_fee_due_charges'),
            'terms.*.name' => __('new_year_term'),
            'terms.*.start_date' => __('new_year_term_starts_on'),
            'terms.*.end_date' => __('new_year_term_ends_on'),
        ];
    }
}
