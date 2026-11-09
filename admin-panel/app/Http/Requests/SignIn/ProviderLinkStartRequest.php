<?php

declare(strict_types=1);

namespace App\Http\Requests\SignIn;

use Illuminate\Foundation\Http\FormRequest;

/** Starting to link one's own Google or Microsoft account in the panel (Action Plan 5.9): the current password again. */
class ProviderLinkStartRequest extends FormRequest
{
    /** The password is never put back into the form. */
    protected $dontFlash = ['password'];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'string', 'max:4096']];
    }
}
