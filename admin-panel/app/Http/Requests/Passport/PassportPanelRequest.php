<?php

declare(strict_types=1);

namespace App\Http\Requests\Passport;

use App\Services\Passport\PassportAuthority;
use Illuminate\Foundation\Http\FormRequest;

/** Read-only: staff find this school's learners by name, never by LURITS. */
class PassportPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(PassportAuthority::class)->staff($this->user());

        return true;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:80']];
    }
}
