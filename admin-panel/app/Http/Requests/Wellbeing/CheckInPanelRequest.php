<?php

declare(strict_types=1);

namespace App\Http\Requests\Wellbeing;

use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\CheckIns;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Wellbeing check-ins (Action Plan 4.11): only a learner's authorised pastoral
 * staff open this page or change a check-in. A change is bound to the account
 * and school that showed the page, carries nothing in the query string, and a
 * refused answer note is never flashed into the session.
 */
class CheckInPanelRequest extends FormRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('wellbeing-check-ins.'));
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private changes must be sent in the request body.');
    }

    public function authorize(): bool
    {
        $checkIns = app(CheckIns::class);
        $checkIns->professional($this->user(), ! $this->isMethodSafe());
        if ($this->isMethodSafe()) {
            return true;
        }
        abort_unless((int) $this->input('expected_account_id') === (int) $this->user()->id
            && (int) $this->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        if (in_array($this->operation(), ['record', 'cancel'], true)) {
            $checkIns->authorized($this->user(), (int) $this->route('checkIn'), true);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1']];
        }
        $identity = ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];

        return $identity + match ($this->operation()) {
            'schedule' => ['learner_ids' => ['required', 'array', 'min:1', 'max:'.CheckIns::PER_GROUP], 'learner_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
                'prompt' => ['required', 'in:'.implode(',', array_keys(CheckIns::PROMPTS))], 'due_on' => ['required', 'date_format:Y-m-d']],
            'record' => ['expected_revision' => ['required', 'integer', 'min:1'], 'scale' => ['required', 'integer', 'between:1,5'], 'note' => ['sometimes', 'nullable', 'string', 'max:500']],
            default => ['expected_revision' => ['required', 'integer', 'min:1']],
        };
    }

    public function messages(): array
    {
        return ['learner_ids.required' => __('Choose at least one learner.'), 'scale.required' => __('Choose the answer the learner gave.')];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Errors only: the private note is not copied into the session's old input.
        throw new HttpResponseException(redirect()->route('wellbeing-check-ins.index')->withErrors($validator));
    }
}
