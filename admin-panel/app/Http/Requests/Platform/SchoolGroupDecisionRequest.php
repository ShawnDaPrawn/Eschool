<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Groups\SchoolGroups;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** The school's answer to a school group (5.7): its Super Admin only; the service checks again. */
class SchoolGroupDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        try {
            app(SchoolGroups::class)->schoolAdmin($this->user());

            return true;
        } catch (HttpExceptionInterface) {
            return false;
        }
    }

    public function rules(): array
    {
        return $this->route()?->getName() === 'school-groups.leave' ? ['reason' => ['nullable', 'string', 'max:500']] : [];
    }
}
