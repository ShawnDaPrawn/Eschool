<?php

declare(strict_types=1);

namespace App\Http\Requests\Pathways;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Pathways\PathwayAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PathwayRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(PathwayAccess::class)->actor($this->user());

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private planning fields in the request body.');
        abort_if($this->allFiles() !== [] || array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the pathway fields.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }

    public function rules(): array
    {
        return match ($this->route()->getActionMethod()) {
            'configuration' => [],
            'index', 'show' => ['page' => ['sometimes', 'integer', 'between:1,100000'], 'manage' => ['sometimes', 'in:0,1,true,false']],
            'store', 'update' => [
                'operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
                'kind' => ['required', 'in:programme,subject_choice_guide'], 'status' => ['required', 'in:published,withdrawn'],
                'title' => ['required', 'string', 'max:191'], 'institution' => ['nullable', 'string', 'max:191'],
                'source_title' => ['required', 'string', 'max:240'], 'source_url' => ['required', 'url:https', 'max:2048'],
                'source_year' => ['required', 'integer', 'between:2000,2100'], 'reviewed_on' => ['required', 'date_format:Y-m-d'], 'expires_on' => ['required', 'date_format:Y-m-d'],
                'guidance' => ['required', 'string', 'max:6000'], 'rules' => ['required', 'array'],
            ],
            'project' => [
                'learner_id' => ['required', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:1'], 'source' => ['required', 'in:released,scenario,selection'],
                'session_year_id' => ['nullable', 'integer', 'min:1'], 'term_id' => ['nullable', 'integer', 'min:1'],
                'marks' => ['sometimes', 'array', 'max:50'], 'subject_ids' => ['sometimes', 'array', 'max:50'],
            ],
        };
    }
}
