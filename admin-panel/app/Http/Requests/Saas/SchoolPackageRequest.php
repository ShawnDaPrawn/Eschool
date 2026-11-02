<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformPackages;
use Illuminate\Validation\Rule;

class SchoolPackageRequest extends PlatformStaffRequest
{
    public function rules(): array
    {
        return ['package_key' => ['required', Rule::in(array_keys(app(PlatformPackages::class)->assignable()))]];
    }
}
