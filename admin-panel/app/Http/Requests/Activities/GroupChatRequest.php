<?php

declare(strict_types=1);

namespace App\Http\Requests\Activities;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Activities\Chat\GroupChatAccess;
use App\Services\Activities\Chat\GroupChatPurpose;
use App\Services\Activities\Chat\GroupChatRules;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Str;

class GroupChatRequest extends FormRequest
{
    public function action(): string
    {
        return Str::afterLast((string) $this->route()->getName(), '.');
    }

    public function persona(): string
    {
        $value = $this->isMethod('GET') ? $this->query->get('persona') : $this->getInputSource()->get('persona');

        return is_string($value) ? $value : '';
    }

    public function authorize(): bool
    {
        if (in_array($this->action(), ['policy', 'policy-save'], true)) {
            app(GroupChatPurpose::class)->authorize($this, 'staff');
            app(GroupChatRules::class)->authorize($this->user(), $this->action() === 'policy-save');
        } else {
            $persona = $this->persona();
            app(GroupChatPurpose::class)->authorize($this, $persona);
            $access = app(GroupChatAccess::class);
            $write = in_array($this->action(), ['create', 'send'], true) || ($this->action() === 'state' && ! $this->boolean('closed'));
            if ($this->route('thread') !== null) {
                $access->thread($this->user(), (int) $this->route('group'), (string) $this->route('thread'), $persona, $write);
            } else {
                $access->group($this->user(), (int) $this->route('group'), $persona, $write);
            }
        }

        return true;
    }

    public function validationData(): array
    {
        return $this->isMethod('GET') ? $this->query->all() : $this->getInputSource()->all();
    }

    protected function prepareForValidation(): void
    {
        $this->authorize();
        abort_if(! $this->isMethod('GET') && $this->query->count() > 0, 422, 'Send conversation fields in the request body.');
        abort_if($this->allFiles() !== [] || array_diff(array_keys($this->validationData()), array_keys($this->rules())) !== [], 422);
    }

    public function rules(): array
    {
        $operation = ['operation_id' => ['required', 'uuid']];
        $persona = ['persona' => ['required', 'in:staff,parent,student']];
        $text = ['body' => ['required', 'string', 'max:'.app(GroupChatRules::class)->maxCharacters()],
            'language' => ['required', 'string', 'max:20', 'regex:/^[a-zA-Z]{2,3}(?:-[a-zA-Z0-9]{2,8}){0,2}$/D']];

        return match ($this->action()) {
            'policy' => [],
            'policy-save' => $operation + ['revision' => ['required', 'integer', 'min:0']]
                + array_fill_keys(array_keys(GroupChatRules::DEFAULTS), ['required', 'boolean']),
            'receipt' => $persona,
            'index' => $persona + ['before' => ['sometimes', 'nullable', 'uuid']],
            'create' => $operation + $persona + $text + ['kind' => ['required', 'in:announcement,question'],
                'audience' => ['sometimes', 'in:learners,guardians,staff'], 'title' => ['required', 'string', 'max:191']],
            'show' => $persona + ['before_message_id' => ['sometimes', 'nullable', 'integer', 'min:1']],
            'send' => $operation + $persona + $text + ['visibility' => ['required', 'in:thread,staff'], 'reply_to' => ['sometimes', 'nullable', 'integer', 'min:1']],
            'state' => $operation + $persona + ['revision' => ['required', 'integer', 'min:1'], 'closed' => ['required', 'boolean']],
            'remove' => $operation + $persona + ['revision' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:2000']],
            'mark-read' => $persona + ['last_message_id' => ['required', 'integer', 'min:1']],
        };
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the conversation change.', ['errors' => $validator->errors()->toArray()]));
    }
}
