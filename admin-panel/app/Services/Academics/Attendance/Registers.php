<?php

declare(strict_types=1);

namespace App\Services\Academics\Attendance;

use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceOperation;
use App\Models\Academics\AttendanceRegister;
use App\Models\Attendance;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Academics\RegisterPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicRegisterChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Registers
{
    public static function targetRules(): array
    {
        return ['session_year_id' => ['required', 'integer', 'min:1'], 'on_date' => ['required', 'date_format:Y-m-d'],
            'class_section_id' => ['nullable', 'required_without:timetable_lesson_id', 'prohibits:timetable_lesson_id', 'integer', 'min:1'],
            'timetable_lesson_id' => ['nullable', 'required_without:class_section_id', 'integer', 'min:1']];
    }

    public static function syncRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'base_revision' => ['required', 'integer', 'min:0'], 'roster_hash' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'reason' => ['nullable', 'string', 'max:500'], 'rows' => ['required', 'array', 'min:1', 'max:500'],
            'rows.*.learner_id' => ['required', 'integer', 'min:1', 'distinct'], 'rows.*.state' => ['required', 'in:present,absent,late,excused,first_half,second_half,holiday'],
            'rows.*.expected_revision' => ['required', 'integer', 'min:0'], 'rows.*.source' => ['required', 'in:tap,qr,offline'],
            'rows.*.qr_token' => ['nullable', 'required_if:rows.*.source,qr', 'string', 'max:4096']];
    }

    private function lockSchool(): void
    {
        School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
    }

    public function open(User $actor, array $input): array
    {
        $data = Validator::make($input, self::targetRules())->validate();
        $target = app(RegisterRosters::class)->target($data);
        app(RegisterPolicy::class)->authorise($actor, $target);

        return DB::transaction(function () use ($actor, $data) {
            $this->lockSchool();
            $target = app(RegisterRosters::class)->target($data);
            app(RegisterPolicy::class)->authorise($actor, $target);
            $register = AttendanceRegister::firstOrCreate(['session_year_id' => $target->session_year_id, 'on_date' => $data['on_date'], 'scope_key' => $target->scope_key], $target->getAttributes());

            return $this->matrix($actor, (int) $register->id);
        });
    }

    public function matrix(User $actor, int $id): array
    {
        $register = AttendanceRegister::findOrFail($id);
        app(RegisterPolicy::class)->authorise($actor, $register);
        $rosters = app(RegisterRosters::class);
        $learners = $rosters->learners($register);
        $entries = AttendanceEntry::where('attendance_register_id', $id)->get()->keyBy('learner_id');
        $legacy = app(RegisterLegacy::class)->rows($register);
        $canWrite = $register->on_date->toDateString() === app(AcademicAuthority::class)->today() || app(AcademicAuthority::class)->manages($actor)
            || app(AcademicAuthority::class)->currentLeadership($actor, 'grade_head', (int) $register->grade_level_id);

        return ['register_id' => (int) $register->id, 'label' => $register->label, 'session_year_id' => (int) $register->session_year_id, 'class_section_id' => $register->class_section_id === null ? null : (int) $register->class_section_id,
            'timetable_lesson_id' => $register->timetable_lesson_id === null ? null : (int) $register->timetable_lesson_id, 'on_date' => $register->on_date->toDateString(), 'revision' => $register->revision,
            'roster_hash' => $rosters->fingerprint($register), 'can_write' => $canWrite, 'learners' => $learners->map(function ($learner) use ($entries, $legacy) {
                $entry = $entries->get($learner->id);
                $original = $legacy->get($learner->id);

                return ['learner_id' => (int) $learner->id, 'name' => trim($learner->user->first_name.' '.$learner->user->last_name), 'state' => $entry?->state ?? ($original === null ? 'unmarked' : app(RegisterLegacy::class)->state((int) $original->type)),
                    'qr_code' => app(RegisterQr::class)->issue($learner), 'revision' => $entry?->revision ?? 0, 'source' => $entry?->source ?? ($original === null ? null : 'legacy'), 'amended_at' => $entry?->amended_at?->toISOString()];
            })->all()];
    }

    public function sync(User $actor, int $id, array $input): array
    {
        $data = Validator::make($input, self::syncRules())->validate();
        $register = AttendanceRegister::findOrFail($id);
        app(RegisterPolicy::class)->authorise($actor, $register, true);

        return DB::transaction(function () use ($actor, $id, $data) {
            $this->lockSchool();
            $register = AttendanceRegister::lockForUpdate()->findOrFail($id);
            app(RegisterPolicy::class)->authorise($actor, $register, true);
            $hash = hash('sha256', json_encode($this->canonical($data), JSON_THROW_ON_ERROR));
            $operation = AttendanceOperation::where('actor_id', $actor->id)->where('operation_id', $data['operation_id'])->first();
            if ($operation !== null) {
                abort_unless((int) $operation->attendance_register_id === $id && hash_equals($operation->payload_hash, $hash), 409, __('This operation ID was used for different attendance.'));

                return $operation->result;
            }
            abort_unless($register->revision === (int) $data['base_revision'], 409, __('The register changed. Compare it before syncing your queued entries.'));
            $rosters = app(RegisterRosters::class);
            abort_unless(hash_equals($rosters->fingerprint($register), $data['roster_hash']), 409, __('The dated roster changed. Review the register first.'));
            $eligible = $rosters->learners($register)->pluck('id')->map(fn ($value) => (int) $value)->all();
            $saved = [];
            $amended = [];
            $legacy = app(RegisterLegacy::class)->rows($register);
            foreach ($data['rows'] as $row) {
                abort_unless(in_array((int) $row['learner_id'], $eligible, true), 404);
                if ($row['source'] === 'qr') {
                    $learner = app(RegisterQr::class)->learner($row['qr_token'] ?? '');
                    abort_unless((int) $learner->id === (int) $row['learner_id'], 422, __('The QR learner does not match this row.'));
                }
                $entry = AttendanceEntry::where('attendance_register_id', $id)->where('learner_id', $row['learner_id'])->first();
                abort_unless(($entry?->revision ?? 0) === (int) $row['expected_revision'], 409, __('A learner entry changed. Compare it before syncing.'));
                if ($entry !== null && $entry->state === $row['state']) {
                    $saved[] = ['learner_id' => (int) $entry->learner_id, 'revision' => $entry->revision];

                    continue;
                }
                $original = $legacy->get($row['learner_id']);
                $legacyChanged = $entry === null && $original !== null && app(RegisterLegacy::class)->state((int) $original->type) !== $row['state'];
                abort_if($register->timetable_lesson_id !== null && in_array($row['state'], ['first_half', 'second_half', 'holiday'], true), 422, __('Use a period attendance state for this lesson.'));
                $reason = trim($data['reason'] ?? '');
                if (($entry !== null || $legacyChanged || $register->on_date->toDateString() !== app(AcademicAuthority::class)->today()) && mb_strlen($reason) < 3) {
                    throw ValidationException::withMessages(['reason' => __('Give a reason for the attendance correction.')]);
                }
                $before = $entry?->getAttributes() ?? ($original === null ? [] : ['legacy_attendance_id' => (int) $original->id, 'legacy_type' => (int) $original->type]);
                $isAmendment = $entry !== null || $legacyChanged;
                $entry ??= new AttendanceEntry(['attendance_register_id' => $id, 'learner_id' => (int) $row['learner_id'], 'captured_by' => (int) $actor->id, 'revision' => 0]);
                $entry->forceFill(['state' => $row['state'], 'source' => $row['source'], 'revision' => $entry->revision + 1, 'reason' => $reason === '' ? null : $reason,
                    'amended_by' => $isAmendment ? (int) $actor->id : null, 'amended_at' => $isAmendment ? now() : null])->save();
                Audit::record($isAmendment ? 'academics.register_amended' : 'academics.register_captured', $entry, $before, $entry->getAttributes());
                if ($register->timetable_lesson_id === null) {
                    $this->project($register, $entry);
                }
                $saved[] = ['learner_id' => (int) $entry->learner_id, 'revision' => $entry->revision];
                if ($isAmendment) {
                    $amended[] = ['id' => (int) $entry->id, 'revision' => $entry->revision];
                }
            }
            $register->forceFill(['revision' => $register->revision + 1])->save();
            $result = ['register_id' => $id, 'revision' => $register->revision, 'entries' => $saved];
            AttendanceOperation::create(['attendance_register_id' => $id, 'actor_id' => (int) $actor->id, 'operation_id' => $data['operation_id'], 'payload_hash' => $hash, 'result' => $result]);
            Outbox::record(new AcademicRegisterChanged(app(CurrentSchool::class)->id(), $id, $amended));

            return $result;
        });
    }

    private function canonical(array $data): array
    {
        if (! array_is_list($data)) {
            ksort($data);
        }

        return array_map(fn ($value) => is_array($value) ? $this->canonical($value) : $value, $data);
    }

    private function project(AttendanceRegister $register, AttendanceEntry $entry): void
    {
        $date = $register->on_date->toDateString();
        $rows = Attendance::where('student_id', $entry->learner_id)->where('session_year_id', $register->session_year_id)->whereDate('date', $date)->orderBy('id')->get();
        foreach ($rows as $existing) {
            abort_if($existing->attendance_entry_id !== null && (int) $existing->attendance_entry_id !== (int) $entry->id, 409, __('This learner already belongs to another register for this day.'));
        }
        $projection = $rows->first() ?? new Attendance;
        $before = array_diff_key($projection->getAttributes(), ['remark' => true]);
        $attributes = ['class_section_id' => (int) $register->class_section_id, 'student_id' => (int) $entry->learner_id, 'session_year_id' => (int) $register->session_year_id,
            'date' => $date, 'type' => app(RegisterLegacy::class)->type($entry->state),
            'status' => 1, 'attendance_entry_id' => (int) $entry->id, 'source' => $entry->source, 'amended_by' => $entry->amended_by, 'amended_at' => $entry->amended_at, 'updated_at' => now()];
        if ($projection->exists) {
            SchoolTable::query('attendances')->where('id', $projection->id)->update($attributes);
            SchoolTable::query('attendances')->whereIn('id', $rows->skip(1)->pluck('id'))->update(['deleted_at' => now()]);
        } else {
            $projection->id = SchoolTable::query('attendances')->insertGetId($attributes + ['created_at' => now()]);
        }
        Audit::record('academics.attendance_projected', $projection, $before, $attributes, app(CurrentSchool::class)->id());
    }
}
