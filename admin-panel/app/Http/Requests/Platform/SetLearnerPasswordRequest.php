<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * A guardian (with an emailed link) or a learner (with a printed code)
 * choosing the learner's password. Anyone holding a valid link or code may.
 */
class SetLearnerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];

        // The printed-code page also asks who the learner is.
        if ($this->routeIs('learner-setup.code.store')) {
            $rules['gr_number'] = ['required', 'string', 'max:191'];
            $rules['code'] = ['required', 'string', 'max:32'];
        }

        return $rules;
    }
}
