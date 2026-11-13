<?php

declare(strict_types=1);

namespace App\Http\Requests\SignIn;

use App\Services\Platform\SignIn\ProviderSignIn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** An app signs in with the ID token of its native Google or Microsoft sign-in (Action Plan 5.9). */
class ProviderAppSignInRequest extends FormRequest
{
    /** Neither the token nor the nonce is ever put back into a response. */
    protected $dontFlash = ['id_token', 'nonce'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'app' => ['required', Rule::in(array_keys(ProviderSignIn::APPS))],
            'id_token' => ['required', 'string', 'max:16384'],
            'nonce' => ['required', 'string', 'max:128'],
            'fcm_id' => ['nullable', 'string', 'max:4096'],
            'device_type' => ['nullable', 'string', 'max:20'],
        ];
    }
}
