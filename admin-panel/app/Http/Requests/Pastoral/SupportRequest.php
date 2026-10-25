<?php

declare(strict_types=1);

namespace App\Http\Requests\Pastoral;

use App\Services\Pastoral\SupportPlans;
use Illuminate\Foundation\Http\FormRequest;

class SupportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $op = substr((string) $this->route()->getName(), strlen('api.v2.support.'));
        if ($this->isMethod('GET')) {
            return ['page' => ['sometimes', 'integer', 'min:1'], 'search' => ['sometimes', 'nullable', 'string', 'max:100']];
        }

        return SupportPlans::rules(match ($op) {
            'create', 'save' => 'save', 'assign' => 'assign', 'review' => 'review',
            'close' => 'close', 'disclose' => 'disclose',
            'revoke-assignment' => 'revoke-assignment', 'revoke-disclosure' => 'revoke-disclosure',
        });
    }
}
