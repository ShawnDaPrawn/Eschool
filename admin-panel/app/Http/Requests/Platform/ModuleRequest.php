<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->hasRole('Super Admin') || (! $user->hasAnyRole(['Parent', 'Student', 'Teacher']) && $user->can('setting-create')));
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : ['enabled' => ['required', 'boolean']];
    }
}
