<?php

declare(strict_types=1);

namespace App\Http\Requests\Pathways;

use App\Http\Controllers\Panel\Pathways\PathwayPanelController;
use App\Services\Academics\AcademicNumbers;
use App\Services\Pathways\PathwayPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PathwayPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PathwayPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'between:1,100000']];
        }

        return [
            'expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'kind' => ['required', 'in:programme,subject_choice_guide'],
            'status' => ['required', 'in:published,withdrawn'], 'title' => ['required', 'string', 'max:191'], 'institution' => ['nullable', 'string', 'max:191'],
            'source_title' => ['required', 'string', 'max:240'], 'source_url' => ['required', 'url:https', 'max:2048'], 'source_year' => ['required', 'integer', 'between:2000,2100'],
            'reviewed_on' => ['required', 'date_format:Y-m-d'], 'expires_on' => ['required', 'date_format:Y-m-d'], 'guidance' => ['required', 'string', 'max:6000'],
            'score_subjects' => ['sometimes', 'array', 'max:50'], 'score_subjects.*' => ['array:subject_id,minimum_percent,counts_for_score'],
            'score_subjects.*.subject_id' => ['required', 'integer', 'min:1'], 'score_subjects.*.minimum_percent' => ['nullable', 'string', 'max:6'], 'score_subjects.*.counts_for_score' => ['required', 'boolean'],
            'score_bands' => ['sometimes', 'array', 'max:20'], 'score_bands.*' => ['array:minimum_percent,points'],
            'score_bands.*.minimum_percent' => ['required', 'string', 'max:6'], 'score_bands.*.points' => ['required', 'integer', 'between:0,20'],
            'best_n' => ['nullable', 'integer', 'between:1,20'], 'minimum_score' => ['nullable', 'integer', 'between:0,400'],
            'compulsory_subject_ids' => ['sometimes', 'array', 'max:20'], 'compulsory_subject_ids.*' => ['integer', 'min:1'],
            'elective_groups' => ['sometimes', 'array', 'max:10'], 'elective_groups.*' => ['array:title,choose,subject_ids'], 'elective_groups.*.title' => ['required', 'string', 'max:120'],
            'elective_groups.*.choose' => ['required', 'integer', 'between:1,20'], 'elective_groups.*.subject_ids' => ['required', 'array', 'min:1', 'max:30'], 'elective_groups.*.subject_ids.*' => ['integer', 'min:1'],
        ];
    }

    public function sourceInput(): array
    {
        $data = $this->validated();
        $source = array_intersect_key($data, array_flip(['operation_id', 'revision', 'kind', 'status', 'title', 'institution', 'source_title', 'source_url', 'source_year', 'reviewed_on', 'expires_on', 'guidance']));
        if ($data['kind'] === 'programme') {
            $source['rules'] = ['subjects' => array_map(fn ($row) => ['subject_id' => $row['subject_id'],
                'minimum_bp' => in_array($row['minimum_percent'] ?? null, [null, ''], true) ? null : AcademicNumbers::units($row['minimum_percent'], 'minimum_percent', 10000),
                'counts_for_score' => $row['counts_for_score']], array_values($data['score_subjects'] ?? [])),
                'bands' => array_map(fn ($row) => ['minimum_bp' => AcademicNumbers::units($row['minimum_percent'], 'minimum_percent', 10000), 'points' => $row['points']], array_values($data['score_bands'] ?? [])),
                'best_n' => $data['best_n'] ?? null, 'minimum_score' => $data['minimum_score'] ?? null];
        } else {
            $source['rules'] = ['compulsory_subject_ids' => array_values($data['compulsory_subject_ids'] ?? []), 'elective_groups' => array_values($data['elective_groups'] ?? [])];
        }

        return $source;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(PathwayPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
