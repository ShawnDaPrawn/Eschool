<?php

declare(strict_types=1);

namespace App\Http\Requests\Admissions;

use App\Models\Admissions\Intake;
use App\Services\Admissions\ApplicantSession;
use App\Services\Admissions\IntakeAuthority;
use Illuminate\Foundation\Http\FormRequest;

class ApplicantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $intake = $this->attributes->get('admissions.intake');
        abort_unless($intake instanceof Intake, 404);
        $write = in_array($this->route()->getActionMethod(), ['begin', 'save', 'submit', 'decide', 'upload', 'remove'], true);
        app(IntakeAuthority::class)->enabled($write);
        if (! $this->isMethodSafe() && ! in_array($this->route()->getActionMethod(), ['begin', 'resumeRequest', 'exchange', 'another'], true)) {
            app(ApplicantSession::class)->owner($this, $intake, $write);
        }

        return true;
    }

    public function rules(): array
    {
        if ($this->isMethodSafe()) {
            return [];
        }

        return match ($this->route()->getActionMethod()) {
            'begin' => $this->draftRules() + ['intake_grade_id' => ['required', 'integer', 'min:1'], 'website' => ['nullable', 'string', 'max:0']],
            'save' => $this->draftRules() + ['revision' => ['required', 'integer', 'min:1']],
            'resumeRequest' => ['email' => ['required', 'email', 'max:191'], 'website' => ['nullable', 'string', 'max:0']],
            'another' => [],
            'exchange' => ['token' => ['required', 'regex:/^[a-f0-9]{64}$/D']],
            'upload' => ['category' => ['required', 'string', 'max:64'], 'operation_id' => ['required', 'uuid'], 'document' => ['required', 'file', 'max:10240']],
            'remove', 'document' => ['handle' => ['required', 'uuid']],
            'decide' => ['revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:accept,decline']],
            default => ['revision' => ['required', 'integer', 'min:1']],
        };
    }

    private function draftRules(): array
    {
        return ['contact' => ['required', 'array:first_name,last_name,email,mobile,relationship'],
            'contact.first_name' => ['required', 'string', 'max:128'], 'contact.last_name' => ['required', 'string', 'max:128'],
            'contact.email' => ['required', 'email', 'max:191'], 'contact.mobile' => ['nullable', 'string', 'max:64'], 'contact.relationship' => ['required', 'string', 'max:32'],
            'answers' => ['sometimes', 'array', 'max:100']];
    }
}
