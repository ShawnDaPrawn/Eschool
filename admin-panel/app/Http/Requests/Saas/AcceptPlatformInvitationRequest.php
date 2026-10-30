<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptPlatformInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The service verifies and consumes the one-time invitation under lock.
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:191', 'confirmed', Password::defaults()],
            'password_confirmation' => ['required', 'string', 'max:191'],
        ];
    }
}
