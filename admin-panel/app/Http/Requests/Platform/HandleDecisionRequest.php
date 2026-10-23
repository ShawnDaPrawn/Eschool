<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HandleDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasRole('Parent') && $this->user()->email_verified_at !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approve', 'reject'])]];
    }
}
