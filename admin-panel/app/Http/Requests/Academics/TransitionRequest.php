<?php

declare(strict_types=1);

namespace App\Http\Requests\Academics;

use App\Policies\Academics\TransitionPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Transitions\ExitEvidence;
use App\Services\Academics\Transitions\TransitionInput;
use App\Services\Academics\Transitions\TransitionPolicies;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Http\FormRequest;

class TransitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(AcademicAuthority::class)->manage($this->user());
        if ($this->route('learner') !== null) {
            app(TransitionPolicy::class)->learner($this->user(), (int) $this->route('learner'));
        }
        if ($this->route('package') !== null) {
            if (str_ends_with((string) $this->route()->getName(), '.export-download')) {
                abort_unless($this->hasValidSignature() && (int) $this->query('reader') === (int) $this->user()->id && (int) $this->query('school') === app(CurrentSchool::class)->id(), 403);
            }
            app(TransitionPolicy::class)->package($this->user(), (int) $this->route('package'));
        }

        return true;
    }

    public function rules(): array
    {
        $action = substr((string) $this->route()->getName(), strrpos((string) $this->route()->getName(), '.') + 1);
        $write = ['operation_id' => ['required', 'uuid'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']];

        return match ($action) {
            'preview' => TransitionInput::rules(),
            'commit' => TransitionInput::rules() + $write,
            'export-preview' => ExitEvidence::rules(),
            'export-prepare' => ExitEvidence::rules() + $write,
            'policy' => TransitionPolicies::rules(),
            'retention' => ['days' => ['required', 'integer', 'between:1,30'], 'revision' => ['required', 'integer', 'min:0']],
            default => ['session_year_id' => ['sometimes', 'integer', 'min:1'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'page' => ['sometimes', 'integer', 'between:1,10000']],
        };
    }
}
