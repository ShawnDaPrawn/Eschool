<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformAnnouncements;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** The HQ announcement forms (SA.13); the service checks the ability and validates the same rules again. */
class AnnouncementRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'announcements.manage');
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'saas.announcements.store' => PlatformAnnouncements::draftRules(),
            'saas.announcements.update' => PlatformAnnouncements::updateRules(),
            'saas.announcements.publish' => PlatformAnnouncements::publishRules(),
            'saas.announcements.withdraw' => PlatformAnnouncements::withdrawRules(),
            default => [],
        };
    }
}
