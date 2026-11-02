<?php

declare(strict_types=1);

namespace App\Http\Requests\Pathways;

use App\Http\Controllers\Panel\Pathways\CareerProfilePanelController;
use App\Services\Academics\AcademicNumbers;
use App\Services\Pathways\CareerProfiles;
use App\Services\Pathways\PathwayPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CareerProfilePanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PathwayPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return [];
        }

        return [
            'expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1'],
            'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'status' => ['required', 'in:published,withdrawn'],
            'title' => ['required', 'string', 'max:191'], 'summary' => ['required', 'string', 'max:3000'],
            'source_title' => ['required', 'string', 'max:240'], 'source_url' => ['required', 'url:https', 'max:2048'], 'source_year' => ['required', 'integer', 'between:2000,2100'],
            'reviewed_on' => ['required', 'date_format:Y-m-d'], 'expires_on' => ['required', 'date_format:Y-m-d'],
            'career_subjects' => ['required', 'array', 'min:1', 'max:20'], 'career_subjects.*' => ['array:subject_ids,minimum_percent,requirement'],
            'career_subjects.*.subject_ids' => ['required', 'array', 'min:1', 'max:5'], 'career_subjects.*.subject_ids.*' => ['integer', 'min:1'],
            'career_subjects.*.minimum_percent' => ['nullable', 'string', 'max:6'], 'career_subjects.*.requirement' => ['required', 'in:'.implode(',', CareerProfiles::REQUIREMENTS)],
            'topic_ids' => ['sometimes', 'array', 'max:50'], 'topic_ids.*' => ['integer', 'min:1'],
            'career_routes' => ['required', 'array', 'min:1', 'max:10'], 'career_routes.*' => ['array:type,title,provider,entry_note,source_title,source_url,programme_id'],
            'career_routes.*.type' => ['required', 'in:'.implode(',', CareerProfiles::ROUTE_TYPES)], 'career_routes.*.title' => ['required', 'string', 'max:191'],
            'career_routes.*.provider' => ['required', 'string', 'max:191'], 'career_routes.*.entry_note' => ['required', 'string', 'max:1000'],
            'career_routes.*.source_title' => ['required', 'string', 'max:240'], 'career_routes.*.source_url' => ['required', 'url:https', 'max:2048'],
            'career_routes.*.programme_id' => ['nullable', 'uuid'],
        ];
    }

    public function profileInput(): array
    {
        $data = $this->validated();
        $profile = array_intersect_key($data, array_flip(['operation_id', 'revision', 'status', 'title', 'summary', 'source_title', 'source_url', 'source_year', 'reviewed_on', 'expires_on']));
        $profile['details'] = [
            'subjects' => array_map(fn ($row) => ['subject_ids' => array_values(array_map('intval', $row['subject_ids'])),
                'minimum_bp' => in_array($row['minimum_percent'] ?? null, [null, ''], true) ? null : AcademicNumbers::units($row['minimum_percent'], 'minimum_percent', 10000),
                'requirement' => $row['requirement']], array_values($data['career_subjects'])),
            'topic_ids' => array_values(array_map('intval', $data['topic_ids'] ?? [])),
            'routes' => array_map(fn ($row) => ['type' => $row['type'], 'title' => $row['title'], 'provider' => $row['provider'], 'entry_note' => $row['entry_note'],
                'source_title' => $row['source_title'], 'source_url' => $row['source_url'], 'programme_id' => ($row['programme_id'] ?? '') === '' ? null : $row['programme_id']],
                array_values($data['career_routes'])),
        ];

        return $profile;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(CareerProfilePanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
