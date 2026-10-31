<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Privacy\DataRequests;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** The HQ data request forms (SA.11); the service validates the same rules again. */
class DataRequestRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), DataRequests::ABILITY);
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.data-requests.store' => DataRequests::logRules(),
            'saas.data-requests.close', 'saas.data-requests.withdraw' => DataRequests::noteRules(),
            default => [],
        };
    }
}
