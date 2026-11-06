<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** The learner's and guardians' tutor memory controls; TutorMemory checks who may see or clear it. */
class TutorMemoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        abort_if($this->query->count() !== 0, 422, 'Tutor memory requests accept no query fields.');
        if ($this->isMethodSafe()) {
            return [];
        }
        abort_if(array_diff(array_keys($this->all()), ['operation_id']) !== [], 422, 'Use only the documented Tutor fields.');

        return ['operation_id' => ['required', 'uuid']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the Tutor inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
