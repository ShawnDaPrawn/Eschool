<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformIncidents;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** The HQ incident register forms (SA.12); the service validates the same rules again. */
class IncidentRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'incidents.manage');
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.incidents.store' => PlatformIncidents::openRules(),
            'saas.incidents.update' => PlatformIncidents::updateRules(),
            'saas.incidents.schools.store' => PlatformIncidents::schoolRules(),
            'saas.incidents.schools.notified' => PlatformIncidents::notifiedRules(),
            'saas.incidents.entries.store' => PlatformIncidents::entryRules(),
            default => [],
        };
    }
}
