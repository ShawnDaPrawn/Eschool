<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaff;
use App\Services\Platform\Saas\PlatformStaffAccess;

class ManagePlatformStaffRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'staff.manage');
    }

    public function rules(): array
    {
        return match ($this->route()?->getName()) {
            'saas.staff.store' => PlatformStaff::rules(new: true),
            'saas.staff.update' => PlatformStaff::rules(new: false),
            'saas.staff.revoke', 'saas.staff.two-factor.reset' => ['reason' => ['required', 'string', 'min:10', 'max:500']],
            default => [], // Resending an invitation has no input.
        };
    }
}
