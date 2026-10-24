<?php

declare(strict_types=1);

namespace App\Http\Requests\Communication;

use App\Policies\Communication\MessagePolicy;
use App\Services\Communication\CommunicationAudiences;

class MessagePanelRequest extends MessageRequest
{
    protected function prepareForValidation(): void
    {
        if (in_array($this->action(), ['create', 'save'], true) && $this->has('scope_selector')) {
            $value = $this->input('scope_selector');
            abort_unless(is_string($value) && preg_match('/^(school|grade|homeroom|group|custom):(0|[1-9][0-9]{0,14})$/D', $value, $matches), 422);
            $this->merge(['audience' => ['kind' => $matches[1], 'target_id' => $matches[1] === 'school' ? null : (int) $matches[2], 'session_year_id' => $this->input('session_year_id'), 'parents' => $this->input('parents', '0')]]);
        }
        if (in_array($this->action(), ['audience-create', 'audience-save'], true)) {
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

    public function rules(): array
    {
        return parent::rules() + ['message_id' => ['sometimes', 'integer', 'min:1'], 'audience_id' => ['sometimes', 'integer', 'min:1'], 'contact_page' => ['sometimes', 'integer', 'between:1,10000'], 'preview_message' => ['sometimes', 'boolean']];
    }
}
