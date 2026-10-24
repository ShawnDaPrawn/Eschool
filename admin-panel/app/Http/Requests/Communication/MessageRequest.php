<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Policies\Communication\MessagePolicy;
use App\Services\Communication\CommunicationAudiences;
use App\Services\Communication\CommunicationRules;
use App\Services\Communication\MessageFlow;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MessageRequest extends FormRequest
{
    public function action(): string
    {
        $name = (string) $this->route()->getName();

        return substr($name, strrpos($name, '.') + 1);
    }

    public function authorize(): bool
    {
        $action = $this->action();
        $actor = $this->user();
        $rules = app(CommunicationRules::class);
        $rules->actor($actor);
        if (in_array($action, ['configuration', 'inbox', 'reader', 'read'], true)) {
            if ($this->route('message') !== null) {
                app(MessagePolicy::class)->reader($actor, (int) $this->route('message'));
            }

            return true;
        }
        $rules->staff($actor);
        if (in_array($action, ['rules', 'rule-save'], true)) {
            abort_unless($rules->manages($actor), 403);
        }
        if ($this->route('audience') !== null) {
            app(CommunicationAudiences::class)->owned($actor, (int) $this->route('audience'));
        }
        if ($this->route('message') !== null) {
            $message = app(MessagePolicy::class)->staff($actor, (int) $this->route('message'), in_array($action, ['save', 'submit'], true));
            if ($action === 'decision') {
                abort_unless(app(MessagePolicy::class)->canReview($actor, $message), 404);
            }
        }

        return true;
    }

    public function rules(): array
    {
        $revision = MessageFlow::revisionRules();
        $fingerprint = ['fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']];

        return match ($this->action()) {
            'create','save' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'title' => ['required', 'string', 'max:191'],
                'markdown' => ['required', 'string', 'max:20000'], 'channels' => ['required', 'array', 'min:1', 'max:3'], 'channels.*' => ['required', Rule::in(['in_app', 'push', 'email']), 'distinct'],
                'scheduled_local' => ['nullable', 'string', 'date_format:Y-m-d\TH:i'], 'audience' => ['required', 'array:kind,session_year_id,target_id,parents'],
                'audience.kind' => ['required', Rule::in(['school', 'grade', 'homeroom', 'group', 'custom'])], 'audience.session_year_id' => ['required', 'integer', 'min:1'],
                'audience.target_id' => ['required_unless:audience.kind,school', 'nullable', 'integer', 'min:1'], 'audience.parents' => ['required', 'boolean']],
            'submit' => $revision + $fingerprint,
            'decision' => $revision + $fingerprint + ['decision' => ['required', Rule::in(['approve', 'reject'])], 'reason' => ['required', 'string', 'min:3', 'max:2000']],
            'withdraw' => $revision + ['reason' => ['required', 'string', 'min:3', 'max:2000']],
            'audience-create','audience-save' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'session_year_id' => ['required', 'integer', 'min:1'],
                'name' => ['required', 'string', 'max:191'], 'active' => ['required', 'boolean'], 'user_ids' => ['required', 'array', 'min:1', 'max:1000'], 'user_ids.*' => ['required', 'integer', 'min:1', 'distinct']],
            'rule-save' => ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'allow_school' => ['required', 'boolean'], 'requires_approval' => ['required', 'boolean']],
            'contacts','audiences' => ['session_year_id' => ['required', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,10000']],
            default => ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'page' => ['sometimes', 'integer', 'between:1,10000']],
        };
    }
}
