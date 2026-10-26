<?php

declare(strict_types=1);

namespace App\Http\Requests\FamilyForms;

use App\Http\Controllers\Panel\FamilyForms\FamilyPanelController;
use App\Services\FamilyForms\Consents;
use App\Services\FamilyForms\FamilyPanelScope;
use App\Services\FamilyForms\SchoolFormTime;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class FamilyPanelRequest extends FormRequest
{
    private array $timeErrors = [];

    public function authorize(): bool
    {
        return app(FamilyPanelScope::class)->authorize($this);
    }

    public function operation(): string
    {
        return (string) $this->route('operation');
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->operation(), ['prepare', 'create'], true)) {
            foreach (['event_starts_at', 'event_ends_at', 'respond_by'] as $field) {
                $value = $this->input($field.'_local');
                try {
                    $this->merge([$field => is_string($value) ? app(SchoolFormTime::class)->utc($value, app(CurrentSchool::class)->school()->timezone) : null]);
                } catch (ValidationException) {
                    $this->timeErrors[] = $field.'_local';
                    $this->merge([$field => null]);
                }
            }
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['activity_group_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,100000']];
        }
        $rules = match ($this->operation()) {
            'prepare', 'create' => Consents::rules('create'),
            'cancel' => Consents::rules('cancel'),
        };
        if ($this->operation() === 'prepare') {
            unset($rules['learner_ids'], $rules['learner_ids.*']);
            $rules['title'] = ['nullable', 'string', 'max:191'];
            $rules['wording'] = ['nullable', 'string', 'max:20000'];
        }
        if (in_array($this->operation(), ['prepare', 'create'], true)) {
            foreach (['event_starts_at', 'event_ends_at', 'respond_by'] as $field) {
                $rules[$field.'_local'] = ['required', 'string', 'max:16'];
            }
        }

        return $rules + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->timeErrors as $field) {
                $validator->errors()->add($field, 'Choose a valid, unambiguous school date and time.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(FamilyPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
