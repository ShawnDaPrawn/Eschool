<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformOffboarding;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** The HQ school offboarding forms (SA.11); the service checks the ability and validates again. */
class OffboardingRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        $ability = in_array((string) $this->route()?->getName(), ['saas.schools.offboarding.approve', 'saas.schools.offboarding.hold.lift'], true)
            ? 'offboarding.approve' : 'offboarding.manage';

        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), $ability);
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.schools.offboarding.start' => PlatformOffboarding::startRules(),
            'saas.schools.offboarding.cancel', 'saas.schools.offboarding.hold' => PlatformOffboarding::reasonRules(),
            default => [],
        };
    }
}
