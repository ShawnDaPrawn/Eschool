<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

/**
 * Staff drafts on the allocations page (Action Plan 1.20): academic
 * management only, through TeachingGroupRequest::authorize (the route name
 * names allocations, so reading the draft needs management too).
 */
class StaffDraftRequest extends TeachingGroupRequest
{
    public function rules(): array
    {
        $year = ['integer', 'min:1'];
        if ($this->isMethodSafe()) {
            return ['year' => ['sometimes', ...$year], 'offset' => ['sometimes', 'integer', 'min:0']];
        }

        return [
            'year' => ['required', ...$year],
            'offset' => ['sometimes', 'integer', 'min:0'],
            'fingerprint' => ['required', 'string', 'size:64'],
            // The form's last field: missing means the server dropped trailing choices.
            'complete' => ['required', 'accepted'],
            'groups' => ['sometimes', 'array', 'max:2000'],
            'groups.*' => ['array', 'max:20'],
            'groups.*.*' => ['nullable', 'integer', 'min:1'],
            'homerooms' => ['sometimes', 'array', 'max:2000'],
            'homerooms.*' => ['array', 'max:20'],
            'homerooms.*.*' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['complete.required' => __('Some of the draft did not arrive, so nothing was applied. Reload the draft and apply it again.'),
            'complete.accepted' => __('Some of the draft did not arrive, so nothing was applied. Reload the draft and apply it again.')];
    }
}
