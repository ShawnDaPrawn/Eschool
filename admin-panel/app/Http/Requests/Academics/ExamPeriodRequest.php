<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Exam periods (exam mode, Action Plan 1.8): staff who keep the exam timetable. */
class ExamPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('exam-timetable-create');
    }

    public function rules(): array
    {
        if ($this->isMethod('GET') || $this->isMethod('DELETE')) {
            return ['session_year_id' => ['nullable', 'integer', Rule::exists('session_years', 'id')]];
        }

        return [
            'session_year_id' => ['required', 'integer', Rule::exists('session_years', 'id')],
            'name' => ['required', 'string', 'max:120'],
            'grade_level_id' => ['required', 'integer', Rule::exists('grade_levels', 'id')],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d'],
            'lessons_continue' => ['nullable', 'boolean'],
        ];
    }
}
