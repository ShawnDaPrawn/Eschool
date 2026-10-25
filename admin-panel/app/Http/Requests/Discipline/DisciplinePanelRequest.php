<?php

declare(strict_types=1);

namespace App\Http\Requests\Discipline;

use App\Http\Controllers\Panel\Discipline\DisciplinePanelController;
use App\Services\Discipline\DisciplinePanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class DisciplinePanelRequest extends DisciplineRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('discipline-panel.'));
    }

    public function authorize(): bool
    {
        return app(DisciplinePanelScope::class)->authorize($this);
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        if ($this->operation() === 'configure') {
            foreach (['levels', 'sanctions', 'infractions'] as $field) {
                $rows = $this->input($field);
                if (is_array($rows)) {
                    $rows = array_values(array_filter($rows, fn ($row) => ! is_array($row) || count(array_filter($row, fn ($value) => is_array($value) ? $value !== [] : (is_scalar($value) && trim((string) $value) !== ''))) > 0));
                    if ($field === 'levels') {
                        foreach ($rows as &$row) {
                            if (is_array($row) && ! array_key_exists('sanctions', $row)) {
                                $row['sanctions'] = [];
                            }
                        }
                        unset($row);
                    }
                    $this->merge([$field => $rows]);
                }
            }
        }
    }

    public function rules(): array
    {
        $navigation = ['mode' => ['sometimes', 'in:records,framework,detentions'], 'session_year_id' => ['sometimes', 'integer', 'min:1'], 'learner_id' => ['sometimes', 'integer', 'min:1'],
            'record' => ['sometimes', 'integer', 'min:1'], 'detention' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'min:1'], 'search' => ['sometimes', 'nullable', 'string', 'max:100']];
        if ($this->isMethodSafe()) {
            return $navigation;
        }

        return parent::rules() + $navigation + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1'],
            'review_required' => ['sometimes', 'in:1'], 'confirm_current' => ['exclude_unless:review_required,1', 'required', 'accepted']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(DisciplinePanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
