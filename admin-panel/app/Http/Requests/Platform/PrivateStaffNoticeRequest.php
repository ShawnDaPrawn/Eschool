<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Facilitators\FacilitatorAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PrivateStaffNoticeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->isMethodSafe()) {
            abort_if($this->query->count() > 0 || $this->getInputSource()->count() > 0, 422, 'This read-marker action does not accept editable fields.');
        }
    }

    public function authorize(): bool
    {
        app(FacilitatorAccess::class)->ready($this->user());

        return true;
    }

    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'between:1,1000']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the update selection.',
            ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
