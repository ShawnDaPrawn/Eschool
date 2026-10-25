<?php

declare(strict_types=1);

namespace App\Http\Requests\Consultations;

use App\Http\Controllers\Panel\Consultations\ConsultationPanelController;
use App\Services\Consultations\ConsultationPanelScope;
use App\Services\Consultations\ConsultationRules;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConsultationPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(ConsultationPanelScope::class)->authorize($this);
    }

    public function operation(): string
    {
        return (string) $this->route('operation');
    }

    protected function prepareForValidation(): void
    {
        if ($this->isMethodSafe()) {
            return;
        }
        // The route middleware checks fresh account/school/source before parsing.
        foreach (['opens_at', 'closes_at'] as $field) {
            $local = $this->input($field.'_local');
            if (is_string($local) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $local)) {
                try {
                    $this->merge([$field => CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $local, app(CurrentSchool::class)->school()->timezone)->utc()->toISOString()]);
                } catch (\InvalidArgumentException) {
                    $this->merge([$field => null]);
                }
            }
        }
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,100000'],
                'history_page' => ['sometimes', 'integer', 'between:1,100000'], 'event' => ['sometimes', 'nullable', 'integer', 'min:1'],
                'kind' => ['sometimes', 'in:school,grade,homeroom,group,activity_group,custom'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'],
                'audience_page' => ['sometimes', 'integer', 'between:1,100000'], 'selected' => ['sometimes', 'nullable', 'integer', 'min:1'], 'managed' => ['sometimes', 'boolean']];
        }
        $rules = match ($this->operation()) {
            'create', 'save' => ConsultationRules::configuration() + ['opens_at_local' => ['required', 'date_format:Y-m-d\TH:i'], 'closes_at_local' => ['required', 'date_format:Y-m-d\TH:i']],
            'vote' => ['answers' => ['present', 'array', 'max:20'], 'answers.*' => ['array:question_id,option_ids,text'],
                'answers.*.question_id' => ['required', 'integer', 'min:1', 'distinct'], 'answers.*.option_ids' => ['sometimes', 'array', 'max:50'],
                'answers.*.option_ids.*' => ['integer', 'min:1', 'distinct'], 'answers.*.text' => ['nullable', 'string', 'max:2000']],
            'publish' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']],
            'close', 'cancel' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:2000']],
            default => abort(404),
        };

        return $rules + ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(ConsultationPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
