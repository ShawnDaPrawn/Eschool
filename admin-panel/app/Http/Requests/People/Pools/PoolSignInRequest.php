<?php

declare(strict_types=1);

namespace App\Http\Requests\People\Pools;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class PoolSignInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['school' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9][a-z0-9-]*$/D'], 'email' => ['required', 'string', 'max:191'], 'password' => ['required', 'string', 'max:128'],
            'new_password' => ['nullable', 'string', Password::defaults(), 'max:72', 'confirmed'], 'new_password_confirmation' => ['nullable', 'string', 'max:72'],
            'acceptances' => ['sometimes', 'array:navigateed,school,pool_consent'], 'acceptances.*' => ['string', 'size:64']];
    }
}
