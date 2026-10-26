<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Models\Learning\ExamAnswer;
use App\Services\Learning\ExamCoachAccess;
use App\Services\Platform\Files\SafeImages;
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

        if ($this->route('answer') !== null && $group !== null) {
            ExamAnswer::select('id')->where('teaching_group_id', (int) $group)->findOrFail($this->route('answer'));
        }

        return true;
    }

    public function rules(): array
    {
        $method = $this->route()->getActionMethod();
        if ($this->isMethodSafe()) {
            $allowed = in_array($method, ['staffPapers', 'learnerPapers', 'plans', 'answers', 'staffAnswers'], true) ? ['page'] : [];
            abort_if(array_diff(array_keys($this->query->all()), $allowed) !== [], 422);

            return ['page' => ['sometimes', 'integer', 'between:1,1000']];
        }
        abort_if($this->query->count() !== 0, 422, 'Private exam coach writes accept body fields only.');
        $allowed = match ($method) {
            'uploadAnswer' => ['operation_id', 'question_number', 'photo', 'approved', 'share_with_teacher', 'crop_x', 'crop_y', 'crop_width', 'crop_height'],
            'sendAnswer' => ['operation_id', 'working'],
            'revokeAnswer' => ['operation_id'],
            'reviewAnswer' => ['operation_id', 'expected_revision', 'mark_units', 'reason'],
            'draft' => ['operation_id', 'expected_revision', 'title', 'framework', 'paper_year', 'paper_code', 'group_ids', 'licence', 'questions'],
            'publish' => ['operation_id', 'expected_revision'],
            'withdraw' => ['operation_id', 'expected_revision', 'reason'],
            'createPlan' => ['operation_id', 'exam_timetable_id', 'schedule_signature', 'paper_version_ids', 'study_minutes'],
            default => [],
        };
        abort_if(array_diff(array_keys($this->all()), $allowed) !== [], 422, 'Use only the documented exam coach fields.');

        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($method) {
            'uploadAnswer' => $operation + SafeImages::rules() + ['question_number' => ['required', 'string', 'regex:/^[A-Za-z0-9().-]{1,16}$/'],
                'approved' => ['required', 'accepted'], 'share_with_teacher' => ['required', 'accepted'],
                'crop_x' => ['required', 'integer', 'between:0,8192'], 'crop_y' => ['required', 'integer', 'between:0,8192'],
                'crop_width' => ['required', 'integer', 'between:1,8192'], 'crop_height' => ['required', 'integer', 'between:1,8192']],
            'sendAnswer' => $operation + ['working' => ['present', 'nullable', 'string', 'max:2000']],
            default => $operation,
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the exam coach inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
