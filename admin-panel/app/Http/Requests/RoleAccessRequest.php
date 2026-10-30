<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** The role editor form; RoleEditor validates the choices and checks the right. */
class RoleAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->isMethodSafe()) {
            $this->merge(['keep_extras' => $this->boolean('keep_extras'), 'confirm_sensitive' => $this->boolean('confirm_sensitive')]);
        }
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? [] : [
            'name' => ['required', 'string', 'max:125'], 'levels' => ['sometimes', 'array'], 'abilities' => ['sometimes', 'array'],
            'keep_extras' => ['boolean'], 'confirm_sensitive' => ['boolean'],
        ];
    }
}
