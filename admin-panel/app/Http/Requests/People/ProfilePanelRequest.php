<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProfilePanelRequest extends ProfileRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->isMethod('GET')) {
            return;
        }
        $values = $this->input('values', []);
        if (! is_array($values)) {
            return;
        }
        foreach ($values as &$value) {
            if (! is_array($value)) {
                continue;
            }
            if (isset($value['choices'])) {
                unset($value['clear']);
            }
            if (array_key_exists('text', $value) && ($value['text'] === null || $value['text'] === '')) {
                $value = ['clear' => true];
            }
        }
        unset($value);
        $this->merge(['values' => $values]);
    }

    /** Private input must never be flashed into session storage on errors. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json(['message' => __('Check the profile fields.'), 'errors' => $validator->errors()], 422));
    }
}
