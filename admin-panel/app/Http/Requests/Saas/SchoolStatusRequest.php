<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Models\Platform\School;
use Illuminate\Validation\Rule;

class SchoolStatusRequest extends PlatformStaffRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([School::ACTIVE, School::SUSPENDED, School::ARCHIVED])],
            // The reason is kept in the audit log with who changed the status.
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
