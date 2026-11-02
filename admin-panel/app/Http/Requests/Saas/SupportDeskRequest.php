<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Saas\PlatformSupportDesk;

/** The HQ support desk forms (SA.17); the service checks the ability and validates the same rules again. */
class SupportDeskRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), PlatformSupportDesk::ABILITY);
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.support.messages.store' => PlatformSupportDesk::messageRules(),
            'saas.support.status' => PlatformSupportDesk::statusRules(),
            'saas.support.assignee' => PlatformSupportDesk::assigneeRules(),
            default => [],
        };
    }
}
