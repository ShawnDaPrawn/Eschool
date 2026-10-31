<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Validation\Rule;

/** HQ: set up or check one school's SES streams, or the platform's pair (no school_id). */
class CheckMailStreamsRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'mail.manage');
    }

    public function rules(): array
    {
        return ['school_id' => ['nullable', 'integer', Rule::exists('schools', 'id')]];
    }
}
