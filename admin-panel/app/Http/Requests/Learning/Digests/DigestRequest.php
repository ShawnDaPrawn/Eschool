<?php

declare(strict_types=1);

namespace App\Http\Requests\Learning\Digests;

use App\Models\Learning\DigestLanguageTemplate;
use App\Services\Learning\Digests\DigestAuthority;
use App\Services\Learning\Digests\DigestQueries;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DigestRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Submit weekly update changes in the request body.');
    }

    public function authorize(): bool
    {
        $authority = app(DigestAuthority::class);
        $method = $this->route()->getActionMethod();
        $authority->actor($this->user(), ! $this->isMethodSafe());
        if (in_array($method, ['templates', 'createTemplate', 'reviewTemplate'], true)) {
            $authority->manager($this->user(), ! $this->isMethodSafe());
            if ($this->route('template') !== null) {
                DigestLanguageTemplate::select(['id'])->findOrFail((int) $this->route('template'));
            }
        } elseif ($method === 'preferences') {
            $authority->parent($this->user(), true);
        } elseif ($this->route('learner') !== null) {
            $authority->family($this->user(), (int) $this->route('learner'));
        } elseif ($this->route('digest') !== null) {
            app(DigestQueries::class)->owned($this->user())->select(['id'])->findOrFail((int) $this->route('digest'));
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return ['before' => ['nullable', 'integer', 'min:1']];
        }

        return self::writeRules($this->route()->getActionMethod());
    }

    public static function writeRules(string $method): array
    {
        $shared = ['operation_id' => ['required', 'uuid']];

        return $shared + match ($method) {
            'preferences' => ['language' => ['required', Rule::in(config('weekly_digests.languages'))], 'revision' => ['required', 'integer', 'min:0']],
            'createTemplate' => ['language' => ['required', Rule::in(config('weekly_digests.languages'))], 'title' => ['required', 'string', 'max:120'], 'body' => ['required', 'string', 'max:1200'],
                'notice_prompt' => ['required', 'string', 'max:120'], 'actions' => ['required', 'array:practice,marks,attendance,calendar'],
                'actions.practice' => ['required', 'string', 'max:160'], 'actions.marks' => ['required', 'string', 'max:160'], 'actions.attendance' => ['required', 'string', 'max:160'], 'actions.calendar' => ['required', 'string', 'max:160']],
            'reviewTemplate' => ['revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:approve,revoke']],
            default => [],
        };
    }
}
