<?php

declare(strict_types=1);

namespace App\Http\Requests\FamilyForms;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\FamilyForms\Consents;
use App\Services\FamilyForms\FamilyFormAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class FamilyFormRequest extends FormRequest
{
    public function action(): string
    {
        return Str::after((string) $this->route()->getName(), 'api.v2.family-forms.');
    }

    public function authorize(): bool
    {
        // Domain callbacks recheck module writes and source scope before receipts.
        app(FamilyFormAccess::class)->actor($this->user(), revocation: true);

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private family fields in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function profileValues(): array
    {
        return array_map(fn ($value) => isset($value['clear']) ? null : array_values($value)[0], $this->validated('values'));
    }

    public function rules(): array
    {
        return match ($this->action()) {
            'configuration', 'health.read' => [],
            'listing' => ['learner_id' => ['sometimes', 'integer', 'min:1'], 'activity_group_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,100000']],
            'read' => ['learner_id' => ['required', 'integer', 'min:1']],
            'managed' => ['page' => ['sometimes', 'integer', 'between:1,100000']],
            'create', 'respond', 'cancel' => Consents::rules($this->action()),
            'health.save' => ['values' => ['required', 'array', 'min:1', 'max:100'], 'values.*' => ['required', 'array:text,choices,flag,clear', 'size:1'],
                'values.*.text' => ['sometimes', 'string', 'max:10000'], 'values.*.choices' => ['sometimes', 'array', 'max:100'], 'values.*.choices.*' => ['string', 'max:512'],
                'values.*.flag' => ['sometimes', 'boolean'], 'values.*.clear' => ['sometimes', 'boolean', 'accepted']],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the family form.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
