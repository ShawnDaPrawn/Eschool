<?php

declare(strict_types=1);

namespace App\Http\Requests\SignIn;

use Illuminate\Foundation\Http\FormRequest;

/** A signed-in app links its person's Google or Microsoft account (Action Plan 5.9): the ID token, the link nonce and the password again. */
class ProviderAppLinkRequest extends FormRequest
{
    protected $dontFlash = ['id_token', 'nonce', 'password'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_token' => ['required', 'string', 'max:16384'],
            'nonce' => ['required', 'string', 'max:128'],
            'password' => ['required', 'string', 'max:4096'],
        ];
    }
}
