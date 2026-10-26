<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

class SchoolModuleRequest extends PlatformStaffRequest
{
    public function rules(): array
    {
        return ['enabled' => ['required', 'boolean']];
    }
}
