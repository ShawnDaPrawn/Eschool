<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformPackages;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** The HQ package and price editor forms (SA.7); the service validates the same rules again. */
class PackageRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'packages.manage');
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.packages.store' => PlatformPackages::createRules(),
            'saas.packages.update' => PlatformPackages::detailRules(),
            'saas.packages.modules' => PlatformPackages::moduleRules(),
            'saas.packages.status' => PlatformPackages::statusRules(),
            'saas.packages.prices.store' => PlatformPackages::priceRules(),
            'saas.packages.reapply' => PlatformPackages::reapplyRules(),
            default => [],
        };
    }
}
