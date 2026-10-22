<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Identity\MembershipService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /school/switch: the panel's school switcher. The school must be one
 * the signed-in person is an active member of.
 */
class SwitchSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'school_id' => ['required', 'integer', function (string $attribute, mixed $value, \Closure $fail) {
                if (! app(MembershipService::class)->isActiveMember($this->user(), (int) $value)) {
                    $fail(trans('school_not_available'));
                }
            }],
        ];
    }

    public function schoolId(): int
    {
        return (int) $this->validated('school_id');
    }
}
