<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\TransitionPolicy;

class TransitionPanelRequest extends TransitionRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('kind') === 'join' && ! $this->has('group_ids')) {
            $this->merge(['group_ids' => []]);
        }
        if (str_contains((string) $this->route()->getName(), '.export-') && ! $this->isMethodSafe()) {
            $this->merge(['report_ids' => $this->input('report_ids', []), 'document_ids' => $this->input('document_ids', [])]);
        }
    }

    public function authorize(): bool
    {
        parent::authorize();
        if ($this->filled('learner_id')) {
            app(TransitionPolicy::class)->learner($this->user(), (int) $this->input('learner_id'));
        }

        return true;
    }

    public function rules(): array
    {
        $context = ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'learner_id' => ['sometimes', 'integer', 'min:1'], 'kind' => ['sometimes', 'in:join,leave,subject_change'],
            'grade_level_id' => ['sometimes', 'integer', 'min:1'], 'history_page' => ['sometimes', 'integer', 'between:1,10000']];

        return parent::rules() + $context;
    }
}
