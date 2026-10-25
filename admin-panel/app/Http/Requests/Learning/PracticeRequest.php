<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Learning\PracticeAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PracticeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private practice writes accept body fields only.');
    }

    public function authorize(): bool
    {
        app(PracticeAccess::class)->actor($this->user(), ! $this->isMethodSafe());

        return true;
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? ['page' => ['sometimes', 'integer', 'between:1,1000']] : [];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the selected practice inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
