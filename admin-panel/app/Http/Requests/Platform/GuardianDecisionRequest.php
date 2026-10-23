<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GuardianDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Parent') === true;
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approve', 'withdraw'])], 'consent_version' => ['required', 'string', 'max:32']];
    }
}
