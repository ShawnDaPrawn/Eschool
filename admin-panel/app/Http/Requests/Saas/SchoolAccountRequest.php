<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformSchoolAccounts;

/** The HQ school record forms; the service validates the same rules again. */
class SchoolAccountRequest extends PlatformStaffRequest
{
    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.schools.account.save' => PlatformSchoolAccounts::accountRules(),
            'saas.schools.contacts.store', 'saas.schools.contacts.update' => PlatformSchoolAccounts::contactRules(),
            'saas.schools.contracts.store' => PlatformSchoolAccounts::contractRules(),
            'saas.schools.notes.store' => PlatformSchoolAccounts::noteRules(),
            default => [],
        };
    }
}
