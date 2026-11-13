<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Models\Settings;
use App\Policies\Academics\CurriculumPolicy;
use App\Services\Academics\NewYearService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The new-year wizard's last step (make the year current) and its preview: the
 * year to switch to and what to carry forward into it.
 */
class SwitchToNewYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (in_array('curriculum', (array) $this->input('carry', []), true)) {
            app(CurriculumPolicy::class)->authorise($this->user(), 'publish');
        }

        // The preview only reads; making a year current needs the right to edit years.
        return $this->isMethod('GET')
            ? (bool) $this->user()?->canAny(['session-year-create', 'session-year-edit'])
            : (bool) $this->user()?->can('session-year-edit');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'source_year_id' => ['sometimes', 'integer', 'min:1'],
            'year_id' => ['required', 'integer', Rule::exists('session_years', 'id')->whereNull('deleted_at')],
            'carry' => ['nullable', 'array'],
            'carry.*' => ['string', Rule::in(array_keys(NewYearService::CARRY))],
            'welcome' => ['nullable', 'boolean'],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->has('source_year_id')) {
            abort_unless((int) $this->input('source_year_id') === (int) Settings::where('type', 'session_year')->value('message'), 409,
                __('The school current year changed. Reload the transition review before activating.'));
        }
    }

    /** @return list<string> */
    public function carry(): array
    {
        return array_values(array_unique((array) $this->validated('carry', [])));
    }
}
