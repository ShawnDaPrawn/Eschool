<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\ContentPolicy;

class ContentPanelRequest extends ContentRequest
{
    protected function prepareForValidation(): void
    {
        $action = substr((string) $this->route()->getName(), strrpos((string) $this->route()->getName(), '.') + 1);
        if (in_array($action, ['create', 'upload'], true) && is_array($this->input('selection'))) {
            $selection = $this->input('selection');
            $selection['group_ids'] = $selection['group_ids'] ?? [];
            $selection['topic_ids'] = $selection['topic_ids'] ?? [];
            $this->merge(['selection' => json_encode($selection, JSON_THROW_ON_ERROR)]);
        }
        if ($action === 'save') {
            $this->merge(['selection' => array_replace((array) $this->input('selection'), ['group_ids' => $this->input('selection.group_ids', []), 'topic_ids' => $this->input('selection.topic_ids', [])])]);
        }
    }

    public function authorize(): bool
    {
        parent::authorize();
        if ($this->filled('item_id')) {
            app(ContentPolicy::class)->staff($this->user(), (int) $this->input('item_id'));
        }

        return true;
    }

    public function rules(): array
    {
        return parent::rules() + ['item_id' => ['sometimes', 'integer', 'min:1'], 'group_id' => ['sometimes', 'integer', 'min:1'], 'version_id' => ['sometimes', 'integer', 'min:1'], 'history_page' => ['sometimes', 'integer', 'between:1,10000'], 'publication_preview' => ['sometimes', 'boolean']];
    }
}
