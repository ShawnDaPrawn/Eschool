<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformPackages;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends PlatformStaffRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('schools', 'slug')],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'package_key' => ['required', Rule::in(array_keys(app(PlatformPackages::class)->assignable()))],
            'admin_first_name' => ['required', 'string', 'max:100'],
            'admin_last_name' => ['required', 'string', 'max:100'],
            // A person has one account across schools; adding an existing
            // account to a new school is not part of this first version.
            'admin_email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'admin_mobile' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() \-]+$/'],
        ];
    }

    public function messages(): array
    {
        return ['admin_email.unique' => 'That email already has an account. Use an email that is not in use yet.'];
    }
}
