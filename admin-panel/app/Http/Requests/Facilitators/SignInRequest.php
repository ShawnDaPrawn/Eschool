<?php

declare(strict_types=1);

namespace App\Http\Requests\Facilitators;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class SignInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['school' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9][a-z0-9-]*$/D'], 'email' => ['required', 'string', 'max:191'], 'password' => ['required', 'string', 'max:128'],
            'new_password' => ['nullable', 'string', Password::defaults(), 'max:128', 'confirmed'], 'new_password_confirmation' => ['nullable', 'string', 'max:128'],
            'acceptances' => ['sometimes', 'array:navigateed,school'], 'acceptances.navigateed' => ['sometimes', 'string', 'size:64'], 'acceptances.school' => ['sometimes', 'string', 'size:64']];
    }
}
