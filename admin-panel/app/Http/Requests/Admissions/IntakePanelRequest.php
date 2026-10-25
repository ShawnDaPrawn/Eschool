<?php

declare(strict_types=1);

namespace App\Http\Requests\Admissions;

use App\Services\Admissions\IntakePanelPage;
use App\Services\Admissions\IntakePanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class IntakePanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(IntakePanelScope::class)->authorize($this);
    }

    protected function prepareForValidation(): void
    {
        if ($this->route('operation') === 'configure') {
            $documents = $this->input('document_categories', '');
            $this->merge(['required_documents' => is_string($documents) ? array_values(array_filter(array_map('trim', explode(',', $documents)))) : [], 'field_ids' => $this->input('field_ids', [])]);
        }
        if (in_array($this->route('operation'), ['bulk-enrol', 'offers'], true)) {
            $selected = $this->input('selected', []);
            $this->merge(['application_ids' => is_array($selected) ? array_values($selected) : [], 'applications' => is_array($selected) ? array_map(fn ($id) => ['id' => $id, 'revision' => $this->input('revisions.'.(string) $id)], array_values($selected)) : []]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['intake_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'starts_on' => ['sometimes', 'date_format:Y-m-d'], 'state' => ['sometimes', 'nullable', 'in:submitted,waitlisted,offered,accepted,enrolled,rejected,withdrawn,offer_expired']];
        }
        $common = ['operation_id' => ['required', 'uuid'], 'expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
        $write = match ($this->route('operation')) {
            'configure' => ['revision' => ['required', 'integer', 'min:0'], 'name' => ['required', 'string', 'max:160'], 'session_year_id' => ['required', 'integer', 'min:1'], 'state' => ['required', 'in:draft,open,closed'], 'opens_at' => ['required', 'date_format:Y-m-d\TH:i'], 'closes_at' => ['required', 'date_format:Y-m-d\TH:i'], 'application_fee_cents' => ['required', 'integer', 'min:0'], 'keep_for_days' => ['required', 'integer', 'min:1', 'max:365'], 'grades' => ['required', 'array', 'max:30'], 'grades.*.grade_level_id' => ['required', 'integer'], 'grades.*.places' => ['required', 'integer', 'min:0'], 'field_ids' => ['required', 'array', 'min:3', 'max:80'], 'field_ids.*' => ['required', 'integer'], 'required_documents' => ['present', 'array', 'max:12'], 'required_documents.*' => ['required', 'string', 'max:64']],
            'offers' => ['application_ids' => ['required', 'array', 'min:1', 'max:100'], 'application_ids.*' => ['required', 'integer', 'distinct', 'min:1']],
            'bulk-enrol' => ['applications' => ['required', 'array', 'min:1', 'max:100'], 'applications.*.id' => ['required', 'integer', 'distinct', 'min:1'], 'applications.*.revision' => ['required', 'integer', 'min:1'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'category_id' => ['required', 'integer', 'min:1']],
            'review' => ['revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:waitlist,reject'], 'reason' => ['required', 'string', 'max:2000']],
            'fee' => ['revision' => ['required', 'integer', 'min:1'], 'amount_cents' => ['required', 'integer', 'min:0'], 'reference' => ['required', 'string', 'max:191']],
            'enrol' => ['revision' => ['required', 'integer', 'min:1'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'category_id' => ['required', 'integer', 'min:1'], 'class_section_id' => ['nullable', 'integer', 'min:1'], 'house_id' => ['nullable', 'integer', 'min:1']],
            default => [],
        };

        return $common + $write;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(IntakePanelPage::class)->failure($this, $validator->errors()->all(), 422));
    }
}
