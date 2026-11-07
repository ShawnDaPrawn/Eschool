<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Groups\SchoolGroupBilling;
use App\Services\Platform\Groups\SchoolGroups;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformStaffAccess;

/**
 * The HQ school group forms (Action Plan 5.7, SA.18). Groups need
 * groups.manage; a school's billing route and the group's invoices need
 * billing.manage. The services check the ability and validate the same
 * rules again.
 */
class SchoolGroupRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        $name = (string) $this->route()?->getName();
        $ability = str_starts_with($name, 'saas.groups.invoices.') || $name === 'saas.groups.schools.billing' ? 'billing.manage' : 'groups.manage';

        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), $ability);
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.groups.store' => SchoolGroups::createRules(),
            'saas.groups.schools.store' => SchoolGroups::inviteRules(),
            'saas.groups.schools.end', 'saas.groups.admins.revoke' => SchoolGroups::reasonRules(),
            'saas.groups.schools.billing' => SchoolGroups::billingRules(),
            'saas.groups.admins.store' => SchoolGroups::adminRules(),
            'saas.groups.invoices.store' => PlatformBilling::runRules(),
            'saas.groups.invoices.payments.store' => SchoolGroupBilling::paymentRules(),
            'saas.groups.invoices.payments.reverse' => SchoolGroupBilling::reverseRules(),
            default => [],
        };
    }
}
