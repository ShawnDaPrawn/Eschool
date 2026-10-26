<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Foundation\Http\FormRequest;

/** Base for the /saas forms: authorised only for an active platform staff identity. */
abstract class PlatformStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PlatformStaffAccess::class)->allowed($this->user());
    }
}
