<?php

declare(strict_types=1);

namespace App\Http\Requests\PeerTutoring;

use App\Http\Controllers\Panel\PeerTutoring\PeerPanelController;
use App\Services\PeerTutoring\PeerPanelScope;
use App\Services\PeerTutoring\PeerSessions;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PeerPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(PeerPanelScope::class)->authorize($this);

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['group' => ['nullable', 'integer', 'min:1'], 'learner' => ['nullable', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,100000']];
        }

        return PeerSessions::rules((string) $this->route('action')) + [
            'expected_account_id' => ['required', 'integer', 'min:1'],
            'expected_school_id' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $input = $this->isMethodSafe() ? $this->query->all() : $this->except('_token');
        abort_if(array_diff(array_keys($input), array_keys($this->rules())) !== [], 422);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(PeerPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
