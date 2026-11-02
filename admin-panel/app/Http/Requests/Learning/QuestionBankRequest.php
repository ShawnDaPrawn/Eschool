<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning;

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
            'index' => ['scope' => ['nullable', 'string', 'regex:/^[1-9][0-9]{0,9}-[1-9][0-9]{0,9}$/'], 'page' => ['nullable', 'integer', 'min:1', 'max:10000']],
            'use', 'stop' => ['group_id' => ['required', 'integer', 'min:1']],
            'update' => QuestionBanks::rules(),
            default => [],
        };
    }
}
