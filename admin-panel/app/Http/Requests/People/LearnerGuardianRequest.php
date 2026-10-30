<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\Platform\Identity\GuardianLinks;
use Illuminate\Foundation\Http\FormRequest;

/** The learner's guardians page; GuardianLinks checks who may manage them. */
class LearnerGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    protected function prepareForValidation(): void
    {
        foreach (['can_approve', 'can_see_fees'] as $flag) {
            if ($this->isMethod('POST') || $this->isMethod('PUT')) {
                $this->merge([$flag => $this->boolean($flag)]);
            }
        }
    }

    public function rules(): array
    {
        return match ((string) $this->route()?->getName()) {
            'learner-guardians.store' => GuardianLinks::addRules(),
            'learner-guardians.update' => GuardianLinks::rightsRules(),
            'learner-guardians.end' => ['reason' => ['required', 'string', 'min:5', 'max:500']],
            default => [],
        };
    }
}
