<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

use App\Services\Learning\PracticeAnswers;
use App\Services\Learning\QuestionBanks;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The question bank page (Action Plan 3.6): open a subject-and-grade bank,
 * use or stop using a question in one of your groups, and (author or
 * subject lead) change who may reuse it or archive it. QuestionBanks checks
 * the question, the group and the actor's right again.
 */
class QuestionBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        abort_unless($user !== null && ($user->can('curriculum-read') || $user->can('academic-structure-manage')), 403);

        return true;
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return match ($this->route()?->getActionMethod()) {
            'index' => ['scope' => ['nullable', 'string', 'regex:/^[1-9][0-9]{0,9}-[1-9][0-9]{0,9}$/'], 'page' => ['nullable', 'integer', 'min:1', 'max:10000'],
                'group' => ['nullable', 'integer', 'min:1']],
            'write' => ['group' => ['nullable', 'integer', 'min:1']],
            // Writing a question of any type (Action Plan 3.11); PracticeAnswers and PracticeBank check it again.
            'create' => ['operation_id' => ['required', 'uuid'], 'group_id' => ['required', 'integer', 'min:1'], 'topic_id' => ['required', 'integer', 'min:1'],
                'content_version_id' => ['required', 'integer', 'min:1'], 'estimated_seconds' => ['required', 'integer', 'between:60,300'],
                'question_kind' => ['required', 'string', 'in:'.implode(',', PracticeAnswers::KINDS)], 'prompt' => ['required', 'string', 'max:4000'],
                'explanation' => ['required', 'string', 'max:4000'], 'options' => ['nullable', 'string', 'max:7000'], 'answers' => ['nullable', 'string', 'max:4500'],
                'steps' => ['nullable', 'string', 'max:12000'], 'regions' => ['nullable', 'string', 'max:400'], 'labels' => ['nullable', 'string', 'max:4000'],
                'mapping' => ['nullable', 'string', 'max:400'], 'max_seconds' => ['nullable', 'integer', 'between:10,180'], 'diagram_id' => ['nullable', 'uuid'],
                'diagram' => ['nullable', 'file', 'mimes:jpeg,png', 'extensions:jpg,jpeg,png', 'max:4096']],
            'mark' => ['operation_id' => ['required', 'uuid'], 'correct' => ['required', 'boolean']],
            'use', 'stop' => ['group_id' => ['required', 'integer', 'min:1']],
            'update' => QuestionBanks::rules(),
            default => [],
        };
    }
}
