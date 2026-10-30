<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformOnboarding;
use App\Services\Platform\Saas\PlatformStaffAccess;

class TrainingRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'accounts.manage');
    }

    public function rules(): array
    {
        return PlatformOnboarding::trainingRules();
    }
}
