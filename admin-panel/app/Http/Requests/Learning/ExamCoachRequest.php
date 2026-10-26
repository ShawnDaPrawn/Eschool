<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Learning\ExamCoachAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ExamCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        $access = app(ExamCoachAccess::class);
        $access->actor($this->user(), ! $this->isMethodSafe());
        $group = $this->route('group');
        if ($group !== null) {
            str_contains($this->route()->getName(), '.staff.') ? $access->staff($this->user(), (int) $group, ! $this->isMethodSafe())
                : $access->learnerGroup($this->user(), (int) $group, ! $this->isMethodSafe());
        } elseif ($this->route()->getActionMethod() !== 'configuration') {
            $access->learner($this->user());
        }

        return true;
    }

    public function rules(): array
    {
        $method = $this->route()->getActionMethod();
        if ($this->isMethodSafe()) {
            $allowed = in_array($method, ['staffPapers', 'learnerPapers', 'plans'], true) ? ['page'] : [];
            abort_if(array_diff(array_keys($this->query->all()), $allowed) !== [], 422);

            return ['page' => ['sometimes', 'integer', 'between:1,1000']];
        }
        abort_if($this->query->count() !== 0, 422, 'Private exam coach writes accept body fields only.');
        $allowed = match ($method) {
            'draft' => ['operation_id', 'expected_revision', 'title', 'framework', 'paper_year', 'paper_code', 'group_ids', 'licence', 'questions'],
            'publish' => ['operation_id', 'expected_revision'],
            'withdraw' => ['operation_id', 'expected_revision', 'reason'],
            'createPlan' => ['operation_id', 'exam_timetable_id', 'schedule_signature', 'paper_version_ids', 'study_minutes'],
            default => [],
        };
        abort_if(array_diff(array_keys($this->all()), $allowed) !== [], 422, 'Use only the documented exam coach fields.');

        return ['operation_id' => ['required', 'uuid']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the exam coach inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
