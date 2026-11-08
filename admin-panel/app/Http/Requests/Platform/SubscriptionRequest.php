<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Learning plan requests (Action Plan 3.20). The services check the guardian
 * or Super Admin, the child and the school on every call; this only shapes
 * the input: documented body fields, no query string on a write.
 */
class SubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            abort_if($this->query->count() !== 0, 422, __('Use only the documented fields.'));

            return [];
        }
        abort_if($this->query->count() !== 0, 422, __('Use only the documented fields.'));
        $rules = match ($this->route()?->getActionMethod()) {
            'request', 'cancel' => ['operation_id' => ['required', 'uuid']],
            'addLearner' => ['learner_id' => ['required', 'integer', 'min:1']],
            default => [],
        };
        abort_if(array_diff(array_keys($this->all()), array_keys($rules)) !== [], 422, __('Use only the documented fields.'));

        return $rules;
    }
}
