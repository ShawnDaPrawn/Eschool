<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptStaffInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The services verify and consume one-time invitations under lock.
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
