<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class AcceptGuardianInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['password' => ['nullable', 'string', Password::defaults()], 'device_type' => ['nullable', 'string', 'in:android,ios,web'], 'fcm_id' => ['nullable', 'string', 'max:4096']];
    }
}
