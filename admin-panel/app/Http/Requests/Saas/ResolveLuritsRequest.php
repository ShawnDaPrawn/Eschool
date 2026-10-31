<?php

declare(strict_types=1);

namespace App\Http\Requests\Saas;

use App\Services\Platform\Saas\PlatformLuritsConflicts;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Validation\Rule;

class ResolveLuritsRequest extends PlatformStaffRequest
{
    public function authorize(): bool
    {
        return parent::authorize() && app(PlatformStaffAccess::class)->can($this->user(), 'identity.review');
    }

    public function rules(): array
    {
        return ['outcome' => ['required', Rule::in(array_keys(PlatformLuritsConflicts::OUTCOMES))]];
    }
}
