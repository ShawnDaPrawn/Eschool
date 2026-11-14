<?php

declare(strict_types=1);

namespace App\Http\Requests\SignIn;

use App\Services\Platform\SignIn\ProviderSignIn;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** An app asks for a one-use nonce before its native Google or Microsoft sign-in (Action Plan 5.9). */
class ProviderNonceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'school' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'app' => ['required', Rule::in(array_keys(ProviderSignIn::APPS))],
        ];
    }
}
