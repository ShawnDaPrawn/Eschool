<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Subscriptions\SubscriptionAdmin;

/** The HQ learning plan forms (3.20, SA.8); the services check billing.manage and validate the same rules again. */
class SubscriptionAdminRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'billing.manage');
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.subscriptions.payments.store' => SubscriptionAdmin::paymentRules(),
            'saas.subscriptions.decline', 'saas.subscriptions.end' => SubscriptionAdmin::reasonRules(),
            'saas.subscriptions.licences.store' => SubscriptionAdmin::licenceRules(),
            'saas.subscriptions.sponsorships.store' => SubscriptionAdmin::sponsorshipRules(),
            default => [],
        };
    }
}
