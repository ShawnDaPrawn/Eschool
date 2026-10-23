<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Policies\People\ProfilePolicy;
use App\Services\People\Profiles\ProfileDefinitions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileConfigurationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $operation = match ($this->method()) {
            'GET' => 'list', 'POST' => 'create', 'DELETE' => 'delete', default => 'edit'
        };
        app(ProfilePolicy::class)->manageDefinitions($this->user(), $operation);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['profile_type' => ['sometimes', Rule::in(config('profile_fields.types'))]];
        }
        if ($this->isMethod('DELETE')) {
            return [];
        }

        return str_contains($this->route()->uri(), '/categories') ? ProfileDefinitions::categoryRules() : ProfileDefinitions::fieldRules();
    }
}
