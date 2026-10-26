<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Http\Controllers\Panel\Activities\GroupChatPanelController;
use App\Services\Activities\Chat\GroupChatPanelScope;
use App\Services\Activities\Chat\GroupChatRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GroupChatPanelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(GroupChatPanelScope::class)->authorize($this);
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['page' => ['sometimes', 'integer', 'min:1', 'max:100000'], 'before' => ['sometimes', 'nullable', 'uuid'],
                'before_message_id' => ['sometimes', 'nullable', 'integer', 'min:1']];
        }
        $text = ['body' => ['required', 'string', 'max:'.app(GroupChatRules::class)->maxCharacters()],
            'language' => ['required', 'string', 'regex:/^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8}){0,2}$/D', 'max:20']];
        $rules = match ($this->route('operation')) {
            'create' => $text + ['title' => ['required', 'string', 'max:191'], 'kind' => ['required', 'in:question,announcement'], 'audience' => ['required', 'in:learners,guardians,staff']],
            'send' => $text + ['visibility' => ['required', 'in:thread,staff'], 'reply_to' => ['nullable', 'integer', 'min:1']],
            'state' => ['revision' => ['required', 'integer', 'min:1'], 'closed' => ['required', 'boolean']],
            'remove' => ['revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:2000']],
            default => abort(404),
        };

        return $rules + ['operation_id' => ['required', 'uuid'], 'expected_account_id' => ['required', 'integer', 'min:1'], 'expected_school_id' => ['required', 'integer', 'min:1']];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(GroupChatPanelController::class)->render($this, $validator->errors()->all(), 422));
    }
}
