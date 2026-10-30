<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use App\Services\People\GradePromotion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bulk promotion by grade (Action Plan 1.18): staff who see promotions may
 * check one; promoting needs promote-student-edit.
 */
class GradePromotionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || ! $user->can('promote-student-list')) {
            return false;
        }

        return ! $this->routeIs('promotions.by-grade.store') || $user->can('promote-student-edit');
    }

    public function rules(): array
    {
        $years = ['nullable', 'integer', Rule::exists('session_years', 'id')];
        if ($this->isMethod('GET')) {
            return ['from' => $years, 'to' => $years];
        }

        return [
            'from' => ['required', ...array_slice($years, 1)],
            'to' => ['required', 'different:from', ...array_slice($years, 1)],
            'learners' => ['nullable', 'array', 'max:5000'],
            'learners.*.outcome' => ['nullable', Rule::in(GradePromotion::OUTCOMES)],
            'learners.*.homeroom' => ['nullable', 'string', 'max:191'],
            'complete' => ['nullable', 'boolean'],
            'sheet' => ['nullable', 'string', 'max:200000'],
            'fingerprint' => [$this->routeIs('promotions.by-grade.store') ? 'required' : 'nullable', 'string', 'size:64'],
        ];
    }
}
