<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformBillingAccounts;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** The HQ billing forms (1.17, SA.8); the services check billing.manage and validate the same rules again. */
class BillingRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'billing.manage');
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.billing.runs.store', 'saas.billing.runs.issue' => PlatformBilling::runRules(),
            'saas.billing.invoices.credit' => PlatformBilling::creditRules(),
            'saas.billing.payments.store' => PlatformBillingAccounts::paymentRules(),
            'saas.billing.payments.reverse' => PlatformBillingAccounts::reversalRules(),
            default => [],
        };
    }
}
