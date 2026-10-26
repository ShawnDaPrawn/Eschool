<?php

declare(strict_types=1);

namespace App\Http\Requests\Classroom;

use App\Exceptions\ApiV2ExceptionRenderer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FoundationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Dedicated middleware and domain services authorize every operation.
    }

    public function validationData(): array
    {
        return $this->isMethodSafe() ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Send private classroom fields in the request body.');
        $allowed = array_keys($this->rules());
        if ($this->panel()) {
            $allowed[] = '_token';
        }
        abort_if(array_diff(array_keys($this->validationData()), $allowed) !== [] || $this->allFiles() !== [], 422);
    }

    public function rules(): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];
        $rules = match ($this->route('foundation_action')) {
            'configuration' => ['page' => ['sometimes', 'integer', 'between:1,100000']],
            'eligibility' => $operation + ['revision' => ['required', 'integer', 'min:0'], 'eligible' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:1000']],
            'register' => $operation + ['name' => ['required', 'string', 'max:100'], 'setup_code' => ['required', 'regex:/^NESET1\.[A-Za-z0-9_-]{43}$/D'], 'days' => ['required', 'integer', 'between:1,30']],
            'grant' => $operation + ['device_id' => ['required', 'uuid'], 'teaching_group_id' => ['required', 'integer', 'min:1'], 'minutes' => ['required', 'integer', 'between:1,60']],
            'revoke', 'device-revoke', 'context', 'read', 'leave' => [],
            'select' => $operation + ['classroom_authorization' => ['required', 'string', 'max:159'], 'locator' => ['required', 'string', 'max:90']],
            'issue' => $operation,
            'review' => $operation + ['answer' => ['required', 'string', 'max:100'], 'rating' => ['required', 'integer', 'between:2,4']],
            default => abort(404),
        };
        if ($this->panel() && ! $this->isMethodSafe()) {
            $rules += ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
        }

        return $rules;
    }

    public function inputForService(): array
    {
        return array_diff_key($this->validated(), array_flip(['expected_account_id', 'expected_school_id']));
    }

    private function panel(): bool
    {
        return str_starts_with((string) $this->route()->getName(), 'foundation-panel.');
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = $this->panel()
            ? response()->view('classroom.invalid', ['messages' => $validator->errors()->all()], 422, ['Cache-Control' => 'no-store, private'])
            : ApiV2ExceptionRenderer::envelope(422, 'Review the classroom request.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']);
        throw new HttpResponseException($response);
    }
}
