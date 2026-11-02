<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformAiControls;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** Switch a school's AI feature on or off from the HQ AI control centre (SA.15); the service validates again. */
class AiFeatureSwitchRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'ai.manage');
    }

    public function rules(): array
    {
        return PlatformAiControls::switchRules();
    }
}
