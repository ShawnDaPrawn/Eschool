<?php

declare(strict_types=1);

namespace App\Http\Requests\EarlyYears;

use App\Services\EarlyYears\DailyDiary;
use App\Services\EarlyYears\EarlyYearsAuthority;
use Illuminate\Validation\Rule;

class DiaryRequest extends EarlyYearsRequest
{
    public function authorize(): bool
    {
        app(EarlyYearsAuthority::class)->classWrite($this->user(), (int) $this->route('section'));

        return true;
    }

    public function rules(): array
    {
        $choice = fn (string $field) => ['nullable', 'string', Rule::in(array_keys(DailyDiary::CHOICES[$field]))];

        return [
            'date' => ['required', 'date_format:Y-m-d'],
            'everyone' => ['sometimes', 'array:meals,rest,mood,toileting'],
            'everyone.meals' => $choice('meals'), 'everyone.rest' => $choice('rest'), 'everyone.mood' => $choice('mood'), 'everyone.toileting' => $choice('toileting'),
            'rows' => ['required', 'array', 'min:1', 'max:200'],
            'rows.*' => ['array:learner_id,meals,rest,mood,toileting,note,revision'],
            'rows.*.learner_id' => ['required', 'integer', 'min:1', 'distinct'],
            'rows.*.meals' => $choice('meals'), 'rows.*.rest' => $choice('rest'), 'rows.*.mood' => $choice('mood'), 'rows.*.toileting' => $choice('toileting'),
            'rows.*.note' => ['nullable', 'string', 'max:'.DailyDiary::NOTE_MAX],
            'rows.*.revision' => ['required', 'integer', 'min:0'],
        ];
    }
}
