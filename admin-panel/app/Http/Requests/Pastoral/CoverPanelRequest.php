<?php

declare(strict_types=1);

namespace App\Http\Requests\Pastoral;

use App\Http\Controllers\Panel\Pastoral\CoverPanelController;
use App\Models\Pastoral\CoverAssignment;
use App\Services\Academics\AcademicAuthority;
use App\Services\Panel\SessionYearContext;
use App\Services\Pastoral\CoverAuthority;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CoverPanelRequest extends CoverRequest
{
    protected function prepareForValidation(): void
    {
        $today = app(AcademicAuthority::class)->today();
        $this->merge(['session_year_id' => $this->input('session_year_id', app(SessionYearContext::class)->selectedId()),
            'from' => $this->input('from', $today), 'to' => $this->input('to', CarbonImmutable::parse($today)->addDays(30)->toDateString())]);
    }

    public function authorize(): bool
    {
        $a = app(CoverAuthority::class);
        $actor = $a->actor($this->user(), ! $this->isMethodSafe());
        if (! $this->isMethodSafe()) {
            abort_unless((int) $this->input('session_year_id') === app(SessionYearContext::class)->currentId(), 403, __('Switch to the current school year before changing cover.'));
        }
        if ($this->route('absence') !== null) {
            $absence = $a->absence($actor, (int) $this->route('absence'), ! $this->isMethodSafe());
            abort_unless($this->isMethodSafe() || (int) $absence->session_year_id === app(SessionYearContext::class)->currentId(), 403);
        }
        if ($this->route('occurrence') !== null) {
            $cover = CoverAssignment::findOrFail((int) $this->route('occurrence'));
            $absence = $a->absence($actor, (int) $cover->cover_absence_id, true);
            abort_unless((int) $absence->session_year_id === app(SessionYearContext::class)->currentId() && $a->manages($actor, (int) $absence->session_year_id), 403);
        }

        return true;
    }

    public function rules(): array
    {
        $common = ['review_required' => ['sometimes', 'in:1'], 'confirm_current' => ['exclude_unless:review_required,1', 'required', 'accepted'], 'session_year_id' => ['required', 'integer', 'min:1'], 'from' => ['required', 'date_format:Y-m-d'], 'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'], 'page' => ['sometimes', 'integer', 'min:1'], 'choose' => ['sometimes', 'integer', 'min:1']];
        $op = substr((string) $this->route()->getName(), strlen('relief-cover.'));
        if (in_array($op, ['index', 'show'], true)) {
            return $common;
        }
        if ($op === 'request') {
            return $common + ['operation_id' => ['required', 'uuid'], 'teacher_id' => ['required', 'integer', 'min:1'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'reason' => ['nullable', 'string', 'max:500']];
        }

        return $common + ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']] + ($op === 'assign' ? ['substitute_teacher_id' => ['required', 'integer', 'min:1']] : ['reason' => ['required', 'string', 'max:500']]);
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(app(CoverPanelController::class)->failure($this, $validator->errors()->all(), 422));
    }
}
