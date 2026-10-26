<?php

declare(strict_types=1);

namespace App\Http\Requests\Cards;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Cards\CardAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class CardRequest extends FormRequest
{
    public function action(): string
    {
        return Str::after((string) $this->route()->getName(), 'api.v2.cards.');
    }

    public function authorize(): bool
    {
        if (! str_starts_with($this->action(), 'device.')) {
            app(CardAccess::class)->actor($this->user(), ! $this->isMethod('GET'), $this->action() === 'freeze' || $this->action() === 'readers.revoke');
        }

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all() + $this->allFiles();
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send private card fields in the request body.');
        abort_if(array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function rules(): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];

        return match ($this->action()) {
            'configuration', 'read', 'portrait', 'credential', 'device.portrait' => [],
            'mine', 'managed' => ['page' => ['sometimes', 'integer', 'between:1,100000']],
            'people' => ['type' => ['required', 'in:learner,staff,guardian'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'between:1,100000']],
            'issue' => $operation + ['person_id' => ['required', 'integer', 'min:1'], 'type' => ['required', 'in:learner,staff,guardian']],
            'portrait.upload' => $operation + ['person_id' => ['required', 'integer', 'min:1'], 'type' => ['required', 'in:learner,staff,guardian'], 'photo' => ['required', 'file']],
            'freeze', 'replace', 'restore' => $operation + ['reason' => ['required', 'string', 'max:2000']] + ($this->action() === 'restore' ? ['picc' => ['sometimes', 'nullable', 'string', 'max:32'], 'mac' => ['sometimes', 'nullable', 'string', 'max:16']] : []),
            'readers.register' => $operation + ['name' => ['required', 'string', 'max:100'], 'kind' => ['required', 'in:gate,encoder'], 'public_key' => ['required', 'string', 'max:64'], 'days' => ['required', 'integer', 'between:1,30']],
            'readers.revoke' => [],
            'device.qr' => $operation + ['credential' => ['required', 'string', 'max:1024']],
            'device.sun' => $operation + ['card_id' => ['required', 'uuid'], 'picc' => ['required', 'regex:/^[a-fA-F0-9]{32}$/D'], 'mac' => ['required', 'regex:/^[a-fA-F0-9]{16}$/D']],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the card request.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'no-store, private']));
    }
}
