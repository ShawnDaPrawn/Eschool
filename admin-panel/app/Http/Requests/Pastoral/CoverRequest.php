<?php

declare(strict_types=1);

namespace App\Http\Requests\Pastoral;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class CoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isEmpty() && $this->filled('from') && $this->filled('to') && CarbonImmutable::parse($this->input('from'))->diffInDays(CarbonImmutable::parse($this->input('to'))) > 30) {
                $v->errors()->add('to', __('Choose a window of up to 31 days.'));
            }
        });
    }

    public function rules(): array
    {
        $op = substr((string) $this->route()->getName(), strlen('api.v2.cover.'));
        if (in_array($op, ['configuration', 'mine'], true)) {
            return ['session_year_id' => ['required', 'integer', 'min:1'], 'from' => ['required', 'date_format:Y-m-d'],
                'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'], 'page' => ['sometimes', 'integer', 'min:1']];
        }
        if ($op === 'request') {
            return ['operation_id' => ['required', 'uuid'], 'session_year_id' => ['required', 'integer', 'min:1'], 'teacher_id' => ['required', 'integer', 'min:1'],
                'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'reason' => ['nullable', 'string', 'max:500']];
        }
        if (in_array($op, ['assign', 'refresh', 'cancel'], true)) {
            return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']] +
                ($op === 'assign' ? ['substitute_teacher_id' => ['required', 'integer', 'min:1']] : ['reason' => ['required', 'string', 'max:500']]);
        }

        return [];
    }
}
