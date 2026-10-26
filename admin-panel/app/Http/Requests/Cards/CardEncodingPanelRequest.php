<?php

declare(strict_types=1);

namespace App\Http\Requests\Cards;

use App\Services\Cards\CardEncodingPanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CardEncodingPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(CardEncodingPanelScope::class)->authorize($this);
    }

    public function validationData(): array
    {
        return $this->isMethodSafe() ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        $allowed = array_keys($this->rules());
        $allowed[] = '_token';
        abort_if($this->allFiles() !== [] || array_diff(array_keys($this->validationData()), $allowed) !== [], 422);
    }

    public function rules(): array
    {
        $binding = ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];

        // The header's historical-year hint never changes a current identity.
        return $this->isMethodSafe() ? $binding + ['session_year_id' => ['sometimes', 'nullable', 'integer']] : $binding + [
            'operation_id' => ['required', 'uuid'], 'device_id' => ['required', 'uuid'], 'uid' => ['required', 'regex:/^04[a-fA-F0-9]{12}$/D'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->view('cards.invalid', ['messages' => $validator->errors()->all()], 422, ['Cache-Control' => 'no-store, private']));
    }
}
