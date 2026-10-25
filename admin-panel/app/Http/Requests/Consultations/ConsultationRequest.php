<?php

declare(strict_types=1);

namespace App\Http\Requests\Consultations;

use App\Services\Consultations\ConsultationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    } // Fresh route scope precedes validation.

    public function action(): string
    {
        return Str::after((string) $this->route()->getName(), 'api.v2.consultations.');
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send consultation changes in the private request body.');
        $known = array_filter(array_keys($this->rules()), fn ($key) => ! str_contains($key, '.'));
        if (array_diff(array_keys($this->validationData()), $known) !== []) {
            throw ValidationException::withMessages(['request' => 'The request contains unsupported fields.']);
        }
    }

    public function rules(): array
    {
        $action = $this->action();
        if ($this->isMethod('GET')) {
            return match ($action) {
                'access', 'ballot', 'configuration', 'results', 'manager-results', 'history-entry' => [],
                'mine', 'managed' => ['session_year_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,100000']],
                'responses', 'history' => ['page' => ['sometimes', 'integer', 'between:1,100000']],
                'audiences' => ['session_year_id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'kind' => ['required', 'in:grade,homeroom,group,activity_group,custom'],
                    'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'between:1,100000'], 'selected' => ['sometimes', 'nullable', 'integer', 'min:1']],
            };
        }
        if (in_array($action, ['create', 'edit'], true)) {
            return ConsultationRules::configuration();
        }
        if ($action === 'vote') {
            return [
                'answers' => ['present', 'array', 'max:20'], 'answers.*' => ['required', 'array:question_id,option_ids,text'],
                'answers.*.question_id' => ['required', 'integer', 'min:1', 'distinct'], 'answers.*.option_ids' => ['sometimes', 'array', 'max:50'],
                'answers.*.option_ids.*' => ['integer', 'min:1', 'distinct'], 'answers.*.text' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']]
            + (in_array($action, ['close', 'cancel'], true) ? ['reason' => ['required', 'string', 'max:2000']] : []);
    }
}
