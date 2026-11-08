<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The starting check page (Action Plan 3.7): a teacher opens one of their
 * groups. DiagnosticService checks the teacher's dated allocation again.
 */
class StartingCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        abort_unless($user !== null && ($user->can('curriculum-read') || $user->can('academic-structure-manage')), 403);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return ['group' => ['nullable', 'integer', 'min:1', 'max:4294967295']];
    }
}
