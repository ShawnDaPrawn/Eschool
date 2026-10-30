<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use Illuminate\Validation\Rule;

class StoreSchoolRequest extends PlatformStaffRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('schools', 'slug')],
            'timezone' => ['required', Rule::in(timezone_identifiers_list())],
            'package_key' => ['required', Rule::in(array_keys((array) config('school_packages')))],
            'admin_first_name' => ['required', 'string', 'max:100'],
            'admin_last_name' => ['required', 'string', 'max:100'],
            // A person has one account across schools: an address that already
            // has one is invited to join (PlatformSchools::create, Action Plan 1.3).
            'admin_email' => ['required', 'email:rfc', 'max:255'],
            'admin_mobile' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() \-]+$/'],
        ];
    }
}
