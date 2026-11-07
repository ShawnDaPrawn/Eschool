<?php

declare(strict_types=1);

namespace App\Http\Requests\OpenStandards;

use App\Services\OpenStandards\OneRoster\OneRosterExports;
use Illuminate\Foundation\Http\FormRequest;

class OneRosterExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(OneRosterExports::class)->allowed($this->user());
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500'], 'include_demographics' => ['sometimes', 'boolean']];
    }

    public function messages(): array
    {
        return ['reason.required' => __('Say why the school is sharing its roster, and with whom.'), 'reason.min' => __('Say why the school is sharing its roster, and with whom.')];
    }
}
