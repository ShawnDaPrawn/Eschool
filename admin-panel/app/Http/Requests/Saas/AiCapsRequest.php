<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Ai\Operations\PlatformAiCaps;
use App\Services\Platform\Saas\PlatformStaffAccess;

/** Set one AI feature's daily spending caps from the HQ AI control centre (Owner only); the service validates again. */
class AiCapsRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'ai.caps');
    }

    public function rules(): array
    {
        return PlatformAiCaps::rules($this->all());
    }
}
