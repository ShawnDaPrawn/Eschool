<?php

declare(strict_types=1);

namespace App\Http\Requests\Assistance;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Services\Assistance\AssistanceAccess;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AssistanceRequest extends FormRequest
{
    public function action(): string
    {
        return (string) $this->route('assistance_action');
    }

    public function authorize(): bool
    {
        $access = app(AssistanceAccess::class);
        $access->actor($this->user(), ! $this->isMethodSafe());
        if (str_starts_with($this->action(), 'office.')) {
            $access->office($this->user(), ! $this->isMethodSafe());
        } elseif ($this->route('learner') !== null) {
            $access->family($this->user(), (int) $this->route('learner'), ! $this->isMethodSafe());
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            $rules = ['page' => ['sometimes', 'integer', 'between:1,1000']];
            if ($this->action() === 'thread.read') {
                $rules['persona'] = ['required', 'in:parent,office'];
            }
            abort_if(array_diff(array_keys($this->query->all()), array_keys($rules)) !== [], 422);

            return $rules;
        }
        abort_if($this->query->count() !== 0, 422, 'Private school-support writes accept body fields only.');
        $operation = ['operation_id' => ['required', 'uuid']];
        $persona = ['persona' => ['required', 'in:parent,office']];
        $message = ['text' => ['required', 'string', 'max:4000'], 'language' => ['required', 'in:'.implode(',', config('assistance.languages'))]];
        $revision = ['expected_revision' => ['required', 'integer', 'min:1']];
        $rules = match ($this->action()) {
            'thread.open' => $operation + $message,
            'thread.reply' => $operation + $message + $persona,
            'thread.close' => $operation + $revision + $persona,
            'office.claim' => $operation + $revision,
            'office.reassign' => $operation + $revision + ['assigned_user_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', 'string', 'max:1000']],
            'answer.prepare' => $operation + ['documents' => ['present', 'array', 'max:5'], 'documents.*' => ['required', 'uuid', 'distinct'],
                'include_calendar' => ['required', 'boolean'], 'include_records' => ['required', 'boolean'],
                'question' => ['required', 'string', 'max:2000'], 'language' => ['required', 'in:'.implode(',', config('assistance.languages'))]],
            'translation.prepare', 'office.translation' => $operation + ['message_id' => ['required', 'uuid'], 'language' => ['required', 'in:'.implode(',', config('assistance.languages'))]],
            'turn.run' => ['identifiers_reviewed' => ['required', 'accepted']],
            'office.publish' => $operation + ['document_key' => ['sometimes', 'nullable', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
                'title' => ['required', 'string', 'max:191'], 'markdown' => ['required', 'string', 'max:16000'],
                'source_reference' => ['required', 'string', 'max:2048'], 'identifiers_reviewed' => ['required', 'accepted'], 'expires_on' => ['sometimes', 'nullable', 'date_format:Y-m-d']],
            'office.withdraw' => $operation,
            default => [],
        };
        $allowed = array_filter(array_keys($rules), fn ($key) => ! str_contains($key, '.'));
        abort_if(array_diff(array_keys($this->all()), $allowed) !== [], 422, 'Use only the documented school-support fields.');

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ApiV2ExceptionRenderer::envelope(422, 'Review the school-support inputs.', ['errors' => $validator->errors()->toArray()], ['Cache-Control' => 'private, no-store']));
    }
}
