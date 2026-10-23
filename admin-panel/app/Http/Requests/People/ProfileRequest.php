<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function profileValues(): array
    {
        return array_map(fn ($value) => isset($value['clear']) ? null : array_values($value)[0], $this->validated('values'));
    }

    public function rules(): array
    {
        $rules = ['profile_type' => ['sometimes', Rule::in(config('profile_fields.types'))]];
        if (! $this->isMethod('GET')) {
            $rules['values'] = ['required', 'array', 'min:1', 'max:100'];
            $rules['values.*'] = ['required', 'array:text,choices,flag,clear', 'size:1'];
            $rules['values.*.text'] = ['sometimes', 'string', 'max:10000'];
            $rules['values.*.choices'] = ['sometimes', 'array', 'max:100'];
            $rules['values.*.choices.*'] = ['string', 'max:512'];
            $rules['values.*.flag'] = ['sometimes', 'boolean'];
            $rules['values.*.clear'] = ['sometimes', 'boolean', 'accepted'];
        }

        return $rules;
    }
}
