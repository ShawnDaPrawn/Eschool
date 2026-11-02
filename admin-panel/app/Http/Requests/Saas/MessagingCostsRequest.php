<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;

/** The month of the HQ SMS and WhatsApp report (Action Plan 2.7). */
class MessagingCostsRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'messaging.view');
    }

    public function rules(): array
    {
        return ['month' => ['nullable', 'date_format:Y-m']];
    }
}
