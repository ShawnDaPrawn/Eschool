<?php

declare(strict_types=1);

namespace App\Http\Requests\Discipline;

use App\Models\Discipline\DisciplineRecord;
use App\Services\Discipline\DisciplineAuthority;
use App\Services\Discipline\DisciplineDetentions;
use App\Services\Discipline\DisciplineRecords;
use Illuminate\Foundation\Http\FormRequest;

class DisciplineRequest extends FormRequest
{
    public function operation(): string
    {
        return substr((string) $this->route()->getName(), strlen('api.v2.discipline.'));
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

    /** Reused inside the private receipt transaction, before replay and append. */
    public function checkAuthority(): void
    {
        $a = app(DisciplineAuthority::class);
        $op = $this->operation();
        $a->enabled(! $this->isMethodSafe());
        $a->actor($this->user(), ! in_array($op, ['family', 'acknowledge'], true));
        if ($op === 'family') {
            $a->family($this->user(), (int) $this->route('learner'));
        } elseif ($op === 'acknowledge') {
            $row = DisciplineRecord::select(DisciplineRecords::META)->whereNotNull('published_at')->findOrFail((int) $this->route('record'));
            $a->family($this->user(), (int) $row->learner_id, true);
        } elseif ($op === 'configure') {
            $a->manage($this->user(), $this->year(), true);
        } elseif (in_array($op, ['detail', 'history', 'review', 'hearing'], true)) {
            $row = app(DisciplineRecords::class)->authorize($this->user(), (int) $this->route('record'));
            if ($op === 'review') {
                $a->head($this->user(), (int) $row->session_year_id, (int) $row->grade_level_id, true);
                if ($row->hearing_decided_by !== null) {
                    $a->principal($this->user(), (int) $row->session_year_id, true);
                }
            } elseif ($op === 'hearing') {
                $a->principal($this->user(), (int) $row->session_year_id, true);
            }
        } elseif ($op === 'create') {
            $date = $this->input('occurred_on');
            abort_unless(is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date), 422);
            $a->record($this->user(), (int) $this->route('learner'), $this->year(), $date);
        } elseif (in_array($op, ['supervisors', 'detention_create'], true)) {
            $grade = $this->input('grade_level_id');
            abort_unless(is_scalar($grade) && ctype_digit((string) $grade), 422);
            $a->head($this->user(), $this->year(), (int) $grade, ! $this->isMethodSafe());
        } elseif (in_array($op, ['detention_detail', 'enroll', 'attendance', 'cancel'], true)) {
            $row = app(DisciplineDetentions::class)->authorize($this->user(), (int) $this->route('detention'), ! $this->isMethodSafe());
            if (in_array($op, ['enroll', 'cancel'], true)) {
                $a->head($this->user(), (int) $row->session_year_id, (int) $row->grade_level_id, true);
            }
        }
    }

    public function authorize(): bool
    {
        $this->checkAuthority();

        return true;
    }

    public function rules(): array
    {
        $id = ['required', 'integer', 'min:1'];
        $uuid = ['required', 'uuid'];
        $rev = ['required', 'integer', 'min:1'];
        $op = $this->operation();
        if ($op === 'configure') {
            return ['operation_id' => $uuid, 'session_year_id' => $id, 'expected_revision' => ['required', 'integer', 'min:0'], 'title' => ['required', 'string', 'max:150'],
                'counter_scope' => ['required', 'in:same_infraction,all_infractions'], 'reset' => ['required', 'in:school_year,term,rolling_days'], 'rolling_days' => ['nullable', 'integer', 'between:1,365'],
                'levels' => ['required', 'array', 'min:2', 'max:8'], 'sanctions' => ['required', 'array', 'min:1', 'max:30'], 'infractions' => ['required', 'array', 'min:1', 'max:100']];
        }
        if ($op === 'create') {
            return ['operation_id' => $uuid, 'session_year_id' => $id, 'occurred_on' => ['required', 'date_format:Y-m-d'], 'infraction_code' => ['required', 'string', 'max:40'],
                'description' => ['required', 'string', 'max:2000'], 'staff_note' => ['nullable', 'string', 'max:2000'], 'sanction_code' => ['nullable', 'string', 'max:40']];
        }
        if (in_array($op, ['review', 'hearing'], true)) {
            return ['operation_id' => $uuid, 'expected_revision' => $rev, 'decision' => ['required', $op === 'review' ? 'in:confirm,overturn' : 'in:no_action,sanction'],
                'reason' => ['required', 'string', 'min:3', 'max:1000'], 'family_reason' => ['nullable', 'string', 'max:1000'], 'sanction_code' => ['nullable', 'string', 'max:40']];
        }
        if ($op === 'acknowledge') {
            return ['operation_id' => $uuid, 'expected_revision' => $rev];
        }
        if ($op === 'detention_create') {
            return ['operation_id' => $uuid, 'session_year_id' => $id, 'grade_level_id' => $id, 'supervisor_id' => $id, 'on_date' => ['required', 'date_format:Y-m-d'],
                'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'], 'capacity' => ['required', 'integer', 'between:1,200'], 'location' => ['required', 'string', 'max:200']];
        }
        if ($op === 'enroll') {
            return ['operation_id' => $uuid, 'record_id' => $id, 'expected_revision' => $rev];
        }
        if ($op === 'attendance') {
            return ['operation_id' => $uuid, 'expected_revision' => $rev, 'expected_session_revision' => $rev,
                'attendance' => ['required', 'in:present,absent,excused'], 'reason' => ['required', 'string', 'min:3', 'max:1000']];
        }
        if ($op === 'cancel') {
            return ['operation_id' => $uuid, 'expected_revision' => $rev, 'reason' => ['required', 'string', 'min:3', 'max:1000']];
        }
        if ($op === 'supervisors') {
            return ['session_year_id' => $id, 'grade_level_id' => $id, 'on_date' => ['required', 'date_format:Y-m-d'], 'page' => ['sometimes', 'integer', 'min:1']];
        }
        if (in_array($op, ['framework', 'records', 'learners', 'detentions', 'family'], true)) {
            return ['session_year_id' => $id, 'page' => ['sometimes', 'integer', 'min:1'], 'search' => ['sometimes', 'nullable', 'string', 'max:100'], 'status' => ['sometimes', 'nullable', 'in:pending_review,confirmed,hearing,overturned']];
        }

        return [];
    }
}
