<?php

declare(strict_types=1);

namespace App\Http\Requests\EarlyYears;

use App\Services\EarlyYears\EarlyYearsAuthority;

class DevelopmentAreaRequest extends EarlyYearsRequest
{
    public function authorize(): bool
    {
        app(EarlyYearsAuthority::class)->manage($this->user(), true);

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'description' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:999'],
            'active' => ['required', 'boolean'],
            'revision' => [$this->route('area') === null ? 'nullable' : 'required', 'integer', 'min:0'],
        ];
    }
}
