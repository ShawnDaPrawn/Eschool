<?php

declare(strict_types=1);

namespace App\Http\Requests\Cards;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Cards\CardReaders;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CardOfflineRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(CardReaders::class)->authenticate($this->bearerToken() ?? '', 'gate', true);

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all() + $this->allFiles();
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private offline records in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? ['request_id' => ['required', 'uuid'], 'after_card_id' => ['sometimes', 'nullable', 'uuid']] : [
            'operation_id' => ['required', 'uuid'], 'manifest' => ['required', 'string', 'max:131072'],
            'credential' => ['required', 'string', 'max:1024'], 'scanned_at' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the offline reader request.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
