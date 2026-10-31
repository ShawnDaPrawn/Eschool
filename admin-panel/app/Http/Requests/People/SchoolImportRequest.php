<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\People\SchoolImports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** School onboarding imports (Action Plan 0.11): staff allowed at least one kind. */
class SchoolImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && app(SchoolImports::class)->allowed($this->user()) !== [];
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['kind' => ['nullable', Rule::in(array_keys(app(SchoolImports::class)->kinds()))]];
        }

        return [
            'kind' => ['required', Rule::in(array_keys(app(SchoolImports::class)->kinds()))],
            'file' => ['nullable', 'file', 'max:1024', 'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel,text/tab-separated-values'],
            'csv' => ['nullable', 'string', 'max:1100000'],
            'mapped' => ['nullable', 'boolean'],
            'mapping' => ['nullable', 'array', 'max:40'],
            'mapping.*' => ['nullable', 'string', 'max:200'],
            'medium_id' => ['nullable', 'integer', Rule::exists('mediums', 'id')],
            'session_year_id' => ['nullable', 'integer', Rule::exists('session_years', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'starts_on' => ['nullable', 'date_format:Y-m-d'],
            'invite' => ['nullable', 'boolean'],
            'fingerprint' => [$this->routeIs('school-imports.store') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }
}
