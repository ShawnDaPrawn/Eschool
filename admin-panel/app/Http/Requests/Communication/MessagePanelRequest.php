<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Models\Communication\Audience;
use App\Policies\Communication\MessagePolicy;
use App\Services\Communication\CommunicationAudiences;
use App\Services\Communication\MessageDraftRestoration;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MessagePanelRequest extends MessageRequest
{
    public function supportsActivityGroups(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->action(), ['create', 'save'], true) && $this->has('scope_selector')) {
            $value = $this->input('scope_selector');
            abort_unless(is_string($value) && preg_match('/^(school|grade|homeroom|group|activity_group|custom):(0|[1-9][0-9]{0,14})$/D', $value, $matches), 422);
            $this->merge(['audience' => ['kind' => $matches[1], 'target_id' => $matches[1] === 'school' ? null : (int) $matches[2], 'session_year_id' => $this->input('session_year_id'), 'parents' => $this->input('parents', '0')]]);
        }
        if (in_array($this->action(), ['audience-create', 'audience-save'], true) && $this->audienceKind() === Audience::SMART) {
            // The smart group form sends its rules as "kind:id" choices.
            $rules = [];
            foreach ((array) $this->input('rule_selectors', []) as $value) {
                abort_unless(is_string($value) && preg_match('/^(grade|homeroom|group|activity_group):([1-9][0-9]{0,14})$/D', $value, $matches), 422);
                $rules[] = ['kind' => $matches[1], 'target_id' => (int) $matches[2]];
            }
            $this->merge(['rules' => $rules]);
        } elseif (in_array($this->action(), ['audience-create', 'audience-save'], true)) {
            $this->merge(['user_ids' => $this->input('user_ids', [])]);
        }
    }

    public function authorize(): bool
    {
        parent::authorize();
        if ($this->filled('message_id')) {
            app(MessagePolicy::class)->staff($this->user(), (int) $this->input('message_id'));
        }
        if ($this->filled('audience_id')) {
            app(CommunicationAudiences::class)->owned($this->user(), (int) $this->input('audience_id'));
        }

        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        if (in_array($this->action(), ['create', 'save'], true)) {
            throw new HttpResponseException(app(MessageDraftRestoration::class)->retain($this, $this->route('message') === null ? null : (int) $this->route('message'), $validator->errors()));
        }
        parent::failedValidation($validator);
    }

    public function rules(): array
    {
        $rules = parent::rules() + ['message_id' => ['sometimes', 'integer', 'min:1'], 'audience_id' => ['sometimes', 'integer', 'min:1'], 'contact_page' => ['sometimes', 'integer', 'between:1,10000'], 'preview_message' => ['sometimes', 'boolean'], 'rule_selectors' => ['sometimes', 'array', 'max:'.Audience::MAX_RULES]];
        if ($this->action() === 'audience-save' && $this->route('audience') !== null && $this->audienceKind() === Audience::SMART && ! $this->boolean('active')) {
            // A stale group's former scopes are absent from the panel's choices.
            $rules['rules'] = ['present', 'array', 'max:'.Audience::MAX_RULES];
        }

        return $rules;
    }
}
