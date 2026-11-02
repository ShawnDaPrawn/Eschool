<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformFeatureFlags;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** The HQ feature flag forms (SA.14); the service validates the same rules again. */
class FeatureFlagRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'flags.manage');
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.flags.store' => PlatformFeatureFlags::createRules(),
            'saas.flags.update' => PlatformFeatureFlags::updateRules(),
            'saas.flags.schools' => PlatformFeatureFlags::schoolRules(),
            default => [],
        };
    }
}
