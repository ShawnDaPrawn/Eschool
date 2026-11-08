<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Services\Learning\Tests\TestBlueprints;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The practice tests page (Action Plan 3.17): open a group's cycle tests,
 * set one, see its results and withdraw it. TestBlueprints checks the
 * teacher's allocation, the group and the test again.
 */
class PracticeTestPanelRequest extends FormRequest
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
        return match ($this->route()?->getActionMethod()) {
            'index' => ['group' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1', 'max:1000']],
            'create' => TestBlueprints::rules(),
            'withdraw' => ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1']],
            default => [],
        };
    }
}
