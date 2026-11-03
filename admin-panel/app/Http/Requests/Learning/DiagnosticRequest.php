<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Learning\PracticeAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * The diagnostic start (Action Plan 3.7). DiagnosticService checks the
 * learner, guardian or teacher, the group and the library switch again.
 */
class DiagnosticRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private diagnostic writes accept body fields only.');
    }

    public function authorize(): bool
    {
        app(PracticeAccess::class)->actor($this->user(), ! $this->isMethodSafe());

        return true;
    }

    public function rules(): array
    {
        return $this->route()?->getActionMethod() === 'answer' ? ['answer' => ['required', 'string', 'max:100']] : [];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the answer.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
