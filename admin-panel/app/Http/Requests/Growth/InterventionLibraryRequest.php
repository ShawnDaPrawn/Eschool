<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Services\Growth\InterventionLibrary;
use App\Services\Growth\RiskAuthority;
use App\Services\Growth\RiskQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The intervention library (Action Plan 4.11): anyone who can use the
 * intervention workbench reads it; only a dated principal changes it. Like the
 * other risk writes, a change is bound to the account and school that showed
 * the page and carries nothing in the query string.
 */
class InterventionLibraryRequest extends FormRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('risk-interventions.library'));
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private changes must be sent in the request body.');
        $steps = $this->input('steps_text');
        if (is_string($steps)) {
            $this->merge(['steps' => array_values(array_filter(array_map('trim', preg_split('/\R/', $steps) ?: []), fn ($line) => $line !== ''))]);
        }
    }

    public function authorize(): bool
    {
        $risk = app(RiskAuthority::class);
        if ($this->isMethodSafe()) {
            $risk->enabled();
            app(RiskQueries::class)->configuration($this->user());

            return true;
        }
        $risk->principal($this->user(), true);
        abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id
            && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return [];
        }
        $identity = ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
        $op = $this->operation();
        if ($op === '.adopt') {
            return $identity;
        }
        if ($op === '.archive') {
            return $identity + ['expected_revision' => ['required', 'integer', 'min:1'], 'archived' => ['required', 'boolean']];
        }

        return $identity + ['name' => ['required', 'string', 'min:3', 'max:120'], 'concern_area' => ['required', 'in:'.implode(',', InterventionLibrary::AREAS)],
            'steps' => ['required', 'array', 'min:1', 'max:'.InterventionLibrary::MAX_STEPS], 'steps.*' => ['required', 'string', 'min:3', 'max:300'],
            'suggested_weeks' => ['required', 'integer', 'between:1,26'], 'progress_measure' => ['required', 'string', 'min:3', 'max:500']]
            + ($op === '.update' ? ['expected_revision' => ['required', 'integer', 'min:1']] : []);
    }

    public function messages(): array
    {
        return ['steps.required' => __('Write at least one step, one per line.'), 'steps.max' => __('A library action has at most :max steps.', ['max' => InterventionLibrary::MAX_STEPS])];
    }
}
