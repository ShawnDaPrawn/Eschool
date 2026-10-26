<?php

declare(strict_types=1);

namespace App\Http\Requests\Cards;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Cards\CardReaders;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CardEncodingRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(CardReaders::class)->authenticate($this->bearerToken() ?? '', 'encoder', true);

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethodSafe() ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        abort_if($this->query->count() > 0 || $this->allFiles() !== [] || array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422, 'Send encoding confirmation only in the private request body.');
    }

    public function rules(): array
    {
        return $this->isMethodSafe() ? [] : ['operation_id' => ['required', 'uuid'], 'picc' => ['required', 'regex:/^[a-fA-F0-9]{32}$/D'], 'mac' => ['required', 'regex:/^[a-fA-F0-9]{16}$/D']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the encoding confirmation.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
