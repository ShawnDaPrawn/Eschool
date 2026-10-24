<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableImport;
use Illuminate\Foundation\Http\FormRequest;

class TimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    } // Fresh policy precedes idempotency replay.

    public function rules(): array
    {
        $name = (string) $this->route()->getName();
        if ($this->isMethodSafe()) {
            if (str_ends_with($name, '.next')) {
                return ['learner_id' => ['sometimes', 'integer', 'min:1']];
            }

            return ['session_year_id' => [str_ends_with($name, '.week') ? 'sometimes' : 'required', 'integer', 'min:1'], 'starts_on' => ['sometimes', 'date_format:Y-m-d'], 'learner_id' => ['sometimes', 'integer', 'min:1']];
        }
        if (str_contains($name, '.rooms.')) {
            return TimetableConfiguration::roomRules() + ($this->isMethod('PUT') ? ['revision' => ['required', 'integer', 'min:1']] : []);
        }
        if (str_contains($name, '.bells.')) {
            return TimetableConfiguration::bellRules() + ($this->isMethod('PUT') ? ['revision' => ['required', 'integer', 'min:1']] : []);
        }
        if (str_ends_with($name, '.import')) {
            return TimetableImport::rules();
        }
        $rules = ['rows' => ['required', 'array', 'min:1', 'max:300']];
        foreach (TimetableEngine::rules() as $field => $rule) {
            // Per-row dates are validated by the service so relative date rules
            // refer to that row, rather than a root-level field.
            if ($field === 'ends_on') {
                $rule = ['required', 'date_format:Y-m-d'];
            }
            $rules['rows.*.'.$field] = $rule;
        }
        if (str_ends_with($name, '.commit')) {
            $rules['fingerprint'] = ['required', 'string', 'size:64', 'regex:/^[a-f0-9]{64}$/'];
        }

        return $rules;
    }
}
