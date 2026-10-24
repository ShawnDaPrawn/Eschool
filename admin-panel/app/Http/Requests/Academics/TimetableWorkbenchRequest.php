<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TimetableConfiguration;
use Illuminate\Foundation\Http\FormRequest;

class TimetableWorkbenchRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(AcademicAuthority::class)->manage($this->user());

        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['payload' => 'rows', 'values_json' => 'values'] as $field => $target) {
            if ($this->has($field) && is_string($this->input($field)) && strlen($this->input($field)) <= 524288) {
                $parsed = json_decode($this->input($field), true, 32);
                $this->merge([$target => is_array($parsed) ? $parsed : []]);
            }
        }
        if ($this->has('periods') && is_array($this->input('periods'))) {
            $this->merge(['periods' => array_values(array_filter($this->input('periods'), fn ($period) => is_array($period) && (trim((string) ($period['name'] ?? '')) !== '' || ! empty($period['starts_at']) || ! empty($period['ends_at']))))]);
        }
        if (is_array($this->input('columns'))) {
            $this->merge(['columns' => array_filter($this->input('columns'), fn ($column) => is_string($column) && $column !== '')]);
        }
    }

    public function rules(): array
    {
        $common = ['session_year_id' => [$this->isMethodSafe() ? 'sometimes' : 'required', 'integer', 'min:1'], 'week_on' => ['sometimes', 'date_format:Y-m-d']];
        if ($this->isMethodSafe()) {
            return $common + ['edit' => ['sometimes', 'integer', 'min:1'], 'room' => ['sometimes', 'integer', 'min:1'], 'schedule' => ['sometimes', 'integer', 'min:1']];
        }
        $name = (string) $this->route()->getName();
        if (str_ends_with($name, '.room')) {
            return $common + TimetableConfiguration::roomRules() + ['room_id' => ['nullable', 'integer', 'min:1'], 'revision' => ['nullable', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.bells')) {
            return $common + TimetableConfiguration::bellRules() + ['schedule_id' => ['nullable', 'integer', 'min:1'], 'revision' => ['nullable', 'integer', 'min:1']];
        }
        if (str_ends_with($name, '.preview')) {
            return $common + ['lesson' => ['required', 'array', 'max:16']];
        }
        if (str_ends_with($name, '.commit')) {
            return $common + ['payload' => ['required', 'string', 'max:524288'], 'rows' => ['required', 'array', 'min:1', 'max:300'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']];
        }

        return $common + ['csv' => ['required', 'string', 'max:524288'], 'stage' => ['sometimes', 'in:columns,values'],
            'columns' => ['sometimes', 'array', 'max:16'], 'columns.*' => ['string', 'max:100'], 'values_json' => ['sometimes', 'string', 'max:524288'],
            'values' => ['sometimes', 'array', 'max:16'], 'defaults' => ['sometimes', 'array', 'max:16']];
    }
}
