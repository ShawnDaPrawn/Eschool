<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Saas\SupportAccess;

/** HQ support access forms (SA.9): request, emergency override, end. The service checks and validates again. */
class SupportAccessRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        $ability = $this->route()?->getName() === 'saas.support.emergency' ? 'support.emergency' : 'support.access';

        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), $ability);
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.support.store' => SupportAccess::requestRules(),
            'saas.support.emergency' => SupportAccess::emergencyRules(),
            default => [],
        };
    }
}
