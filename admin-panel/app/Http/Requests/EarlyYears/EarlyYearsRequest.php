<?php

declare(strict_types=1);

namespace App\Http\Requests\EarlyYears;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Early years writes (Action Plan 4.3). A refused form goes back with its
 * errors only: notes about young children never enter the session as old input.
 */
abstract class EarlyYearsRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(redirect()->to(url()->previous())->withErrors($validator->errors()));
    }
}
