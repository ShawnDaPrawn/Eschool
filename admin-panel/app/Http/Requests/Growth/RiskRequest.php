<?php

declare(strict_types=1);

namespace App\Http\Requests\Growth;

use App\Services\Growth\Interventions;
use App\Services\Growth\RiskAuthority;
use Illuminate\Foundation\Http\FormRequest;

class RiskRequest extends FormRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('api.v2.risk.'));
    }

    protected function prepareForValidation(): void
    {
        abort_if(! $this->isMethodSafe() && $this->query->count() > 0, 422, 'Private changes must be sent in the request body.');
    }

    public function year(): int
    {
        $value = $this->input('session_year_id');

        return is_scalar($value) && (int) $value > 0 ? (int) $value : (int) schoolSetting('session_year');
    }

    public function authorize(): bool
    {
        $a = app(RiskAuthority::class);
        $a->enabled(! $this->isMethodSafe());
        $a->actor($this->user());
        $op = $this->operation();
        if ($op === 'configure') {
            $a->principal($this->user(), true);
        } elseif (in_array($op, ['detail', 'history', 'update'], true)) {
            app(Interventions::class)->authorized($this->user(), (int) $this->route('intervention'), ! $this->isMethodSafe());
        } elseif (in_array($op, ['assignments', 'professionals', 'assign', 'revoke'], true)) {
            $a->administer($this->user(), (int) $this->route('learner'), $this->year());
        } elseif ($op === 'create') {
            $a->year($this->year(), true);
            $a->caseActor($this->user(), (int) $this->route('learner'), $this->year(), $a->currentGrade((int) $this->route('learner'), $this->year()), true);
        }

        return true;
    }

    public function rules(): array
    {
        $year = ['required', 'integer', 'min:1'];
        $uuid = ['required', 'uuid'];
        $revision = ['required', 'integer', 'min:1'];
        $op = $this->operation();
        if ($op === 'create') {
            return ['session_year_id' => $year, 'operation_id' => $uuid, 'owner_id' => $year, 'owner_authority' => ['sometimes', 'in:academic,professional,leadership'],
                'action' => ['required', 'string', 'max:20000'], 'started_on' => ['required', 'date_format:Y-m-d'],
                'review_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:started_on']];
        }
        if ($op === 'update') {
            return ['operation_id' => $uuid, 'expected_revision' => $revision, 'action' => ['required', 'string', 'max:20000'],
                'review_on' => ['required', 'date_format:Y-m-d'], 'state' => ['required', 'in:active,completed,cancelled'],
                'outcome' => ['nullable', 'string', 'max:20000', 'required_if:state,completed'],
                'owner_id' => ['prohibited'], 'owner_authority' => ['prohibited'], 'learner_id' => ['prohibited'],
                'session_year_id' => ['prohibited'], 'grade_level_id' => ['prohibited'], 'started_on' => ['prohibited'], 'created_by' => ['prohibited']];
        }
        if ($op === 'transfer') {
            return ['operation_id' => $uuid, 'expected_revision' => $revision, 'owner_id' => $year, 'reason' => ['required', 'string', 'min:3', 'max:1000']];
        }
        if ($op === 'configure') {
            return ['operation_id' => $uuid, 'academic_threshold_bp' => ['required', 'integer', 'between:1,10000'],
                'minimum_coverage_bp' => ['required', 'integer', 'between:1,10000'], 'absence_threshold_bp' => ['required', 'integer', 'between:1,10000'],
                'minimum_registers' => ['required', 'integer', 'between:1,30'], 'expected_revision' => ['required', 'integer', 'min:0']];
        }
        if (in_array($op, ['assign', 'revoke'], true)) {
            return ['session_year_id' => $year, 'operation_id' => $uuid, 'expected_revision' => ['required', 'integer', $op === 'assign' ? 'min:0' : 'min:1']] +
                ($op === 'assign' ? ['starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on']] : []);
        }
        if (in_array($op, ['signals', 'interventions', 'learners', 'professionals', 'assignments', 'owners'], true)) {
            return ['session_year_id' => $year, 'page' => ['sometimes', 'integer', 'min:1'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'intervention_id' => ['sometimes', 'integer', 'min:1']];
        }

        return [];
    }
}
