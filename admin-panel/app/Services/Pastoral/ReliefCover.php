<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Academics\TimetableLesson;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\Pastoral\CoverOperation;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ReliefCover
{
    private function authorize(User $actor, string $action, array $input): void
    {
        $a = app(CoverAuthority::class);
        if ($action === 'request') {
            abort_unless($a->manages($actor, $input['session_year_id']) || $a->own($actor, $input['teacher_id']), 403);
        } elseif ($action === 'assign') {
            $cover = CoverAssignment::findOrFail($input[0]);
            $absence = CoverAbsence::findOrFail($cover->cover_absence_id);
            abort_unless($a->manages($actor, (int) $absence->session_year_id), 403);
        } else {
            $a->absence($actor, $input[0], true);
        }
    }

    private function operation(User $actor, string $uuid, string $action, array $input, callable $effect): Model
    {
        abort_unless(Str::isUuid($uuid) && (string) config('app.key') !== '', 422, 'Choose a fresh request identifier.');

        $context = app(CurrentSchool::class);
        $before = $context->snapshot();
        $schoolId = $context->id();
        try {
            return DB::transaction(function () use ($actor, $uuid, $action, $input, $effect, $context, $schoolId) {
                abort_unless($context->id() === $schoolId, 403);
                School::whereKey($schoolId)->lockForUpdate()->firstOrFail();
                $actor = app(CoverAuthority::class)->actor($actor, true);
                $this->authorize($actor, $action, $input);
                $hash = hash_hmac('sha256', json_encode([$actor->id, $action, $input], JSON_THROW_ON_ERROR), (string) config('app.key'));
                $prior = CoverOperation::where('operation_id', $uuid)->first();
                if ($prior !== null) {
                    abort_unless((int) $prior->user_id === (int) $actor->id && hash_equals($prior->request_hash, $hash), 409, 'This request identifier belongs to different cover changes.');

                    return $prior->result_kind === 'absence' ? CoverAbsence::findOrFail($prior->result_id) : CoverAssignment::findOrFail($prior->result_id);
                }
                $result = $effect();
                abort_unless($context->id() === $schoolId && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
                $this->authorize(app(CoverAuthority::class)->actor($actor, true), $action, $input);
                CoverOperation::create(['user_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash,
                    'result_kind' => $result instanceof CoverAbsence ? 'absence' : 'assignment', 'result_id' => $result->id]);

                abort_unless($context->id() === $schoolId && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);

                return $result;
            }, 3);
        } finally {
            $context->restore($before);
        }
    }

    public function request(User $actor, array $input, string $uuid): CoverAbsence
    {
        $authority = app(CoverAuthority::class);
        $fresh = $authority->actor($actor, true);
        $data = Validator::make($input, ['session_year_id' => ['required', 'integer', 'min:1'], 'teacher_id' => ['required', 'integer', 'min:1'],
            'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'reason' => ['nullable', 'string', 'max:500']])->validate();
        $data = ['session_year_id' => (int) $data['session_year_id'], 'teacher_id' => (int) $data['teacher_id'],
            'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'reason' => $data['reason'] ?? null];
        abort_unless($authority->manages($fresh, $data['session_year_id']) || $authority->own($fresh, $data['teacher_id']), 403);
        $result = $this->operation($fresh, $uuid, 'request', $data, function () use ($fresh, $authority, $data) {
            $year = SessionYear::findOrFail($data['session_year_id']);
            abort_if($data['starts_on'] < app(AcademicAuthority::class)->today() || $data['starts_on'] < substr((string) $year->start_date, 0, 10)
                || $data['ends_on'] > substr((string) $year->end_date, 0, 10) || CarbonImmutable::parse($data['starts_on'])->diffInDays(CarbonImmutable::parse($data['ends_on'])) > 30, 422, 'Choose up to 31 days in the school year, starting today or later.');
            $authority->teacher($data['teacher_id'], $data['starts_on']);
            $authority->teacher($data['teacher_id'], $data['ends_on']);
            abort_if(CoverAbsence::where('teacher_id', $data['teacher_id'])->where('session_year_id', $data['session_year_id'])->where('state', 'requested')->whereDate('starts_on', '<=', $data['ends_on'])->whereDate('ends_on', '>=', $data['starts_on'])->exists(), 409, 'This teacher already has a cover request for part of these dates.');
            abort_if(app(CoverAvailability::class)->hasUnmapped($data['teacher_id'], $data['session_year_id'], $data['starts_on'], $data['ends_on']), 409, 'Map this teacher’s legacy timetable before requesting cover.');
            $absence = CoverAbsence::create($data + ['requested_by' => $fresh->id, 'state' => 'requested', 'revision' => 1]);
            app(CoverSources::class)->map($absence);
            Audit::record('cover.requested', $absence, [], ['teacher_id' => $data['teacher_id'], 'starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on']]);

            return $absence;
        });

        /** @var CoverAbsence $result */
        return $result;
    }

    public function assign(User $actor, int $id, int $substitute, int $revision, string $uuid): CoverAssignment
    {
        $authority = app(CoverAuthority::class);
        $fresh = $authority->actor($actor, true);
        $cover = CoverAssignment::findOrFail($id);
        $absence = CoverAbsence::findOrFail($cover->cover_absence_id);
        abort_unless($authority->manages($fresh, (int) $absence->session_year_id), 403);
        $result = $this->operation($fresh, $uuid, 'assign', [$id, $substitute, $revision], function () use ($id, $substitute, $revision, $authority) {
            $cover = CoverAssignment::lockForUpdate()->findOrFail($id);
            abort_unless($cover->revision === $revision && $cover->state !== 'cancelled' && app(CoverSources::class)->valid($cover), 409, 'Refresh the changed cover occurrence before assigning it.');
            abort_if($cover->on_date->toDateString() < app(AcademicAuthority::class)->today(), 422, 'Past cover occurrences cannot be assigned.');
            $authority->teacher($substitute, $cover->on_date->toDateString());
            $lesson = TimetableLesson::findOrFail($cover->timetable_lesson_id);
            abort_unless(app(CoverAvailability::class)->assignable($substitute, $cover, $lesson), 409, 'This teacher is unavailable for this occurrence.');
            $before = ['state' => $cover->state, 'substitute_teacher_id' => $cover->substitute_teacher_id, 'revision' => $cover->revision];
            $cover->fill(['state' => 'assigned', 'substitute_teacher_id' => $substitute, 'revision' => $cover->revision + 1])->save();
            Audit::record('cover.assigned', $cover, $before, ['state' => 'assigned', 'substitute_teacher_id' => $substitute, 'revision' => $cover->revision]);

            return $cover;
        });

        /** @var CoverAssignment $result */
        return $result;
    }

    public function change(User $actor, int $id, int $revision, string $action, string $reason, string $uuid): CoverAbsence
    {
        abort_unless(in_array($action, ['cancel', 'refresh'], true), 422);
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'max:500']])->validate();
        $authority = app(CoverAuthority::class);
        $absence = $authority->absence($actor, $id, true);
        abort_if($absence->ends_on->toDateString() < app(AcademicAuthority::class)->today() && ! $authority->manages($actor, (int) $absence->session_year_id), 403);
        $result = $this->operation($actor, $uuid, $action, [$id, $revision, $reason], function () use ($id, $revision, $action, $reason) {
            $absence = CoverAbsence::lockForUpdate()->findOrFail($id);
            abort_unless($absence->revision === $revision && $absence->state === 'requested', 409, 'This cover request changed.');
            if ($action === 'cancel') {
                CoverAssignment::where('cover_absence_id', $id)->where('state', '!=', 'cancelled')->update(['state' => 'cancelled', 'substitute_teacher_id' => null, 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
                $absence->fill(['state' => 'cancelled', 'cancel_reason' => $reason]);
            } else {
                app(CoverSources::class)->map($absence);
            }
            $absence->revision++;
            $absence->save();
            Audit::record('cover.'.$action, $absence, ['revision' => $revision], ['revision' => $absence->revision, 'state' => $absence->state]);

            return $absence;
        });

        /** @var CoverAbsence $result */
        return $result;
    }
}
