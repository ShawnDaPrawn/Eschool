<?php

declare(strict_types=1);

namespace App\Http\Requests\Assistance;

use App\Http\Controllers\Panel\Assistance\AssistancePanelController;
use App\Services\Assistance\AssistancePanelScope;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssistancePanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(AssistancePanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'between:1,1000'], 'document_page' => ['sometimes', 'integer', 'between:1,1000'],
                'message_page' => ['sometimes', 'integer', 'between:1,1000'], 'document' => ['sometimes', 'uuid'],
                'turn' => ['sometimes', 'uuid'], 'session_year_id' => ['sometimes', 'integer', 'min:1']];
        }
        abort_if($this->query->count() !== 0, 422, 'Private school-support writes accept body fields only.');
        $operation = ['operation_id' => ['required', 'uuid']];
        $revision = ['expected_revision' => ['required', 'integer', 'min:1']];
        $language = ['language' => ['required', 'in:'.implode(',', config('assistance.languages'))]];

        return ['expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']] + match ($this->route('operation')) {
            'claim', 'close' => $operation + $revision,
            'reply' => $operation + $language + ['text' => ['required', 'string', 'max:4000']],
            'reassign' => $operation + $revision + ['assigned_user_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:1000']],
            'publish' => $operation + ['document_key' => ['sometimes', 'nullable', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
                'title' => ['required', 'string', 'max:191'], 'markdown' => ['required', 'string', 'max:16000'],
                'source_reference' => ['required', 'string', 'max:2048'], 'expires_on' => ['sometimes', 'nullable', 'date_format:Y-m-d'], 'identifiers_reviewed' => ['required', 'accepted']],
            'withdraw' => $operation,
            'translate' => $operation + $language + ['message_id' => ['required', 'uuid']],
            'run' => ['identifiers_reviewed' => ['required', 'accepted']],
            default => [],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(AssistancePanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
