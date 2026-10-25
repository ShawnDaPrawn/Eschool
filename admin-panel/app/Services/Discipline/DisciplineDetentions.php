<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Academics\GradeLevel;
use App\Models\Discipline\DetentionEntry;
use App\Models\Discipline\DetentionSession;
use App\Models\Discipline\DetentionVersion;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DisciplineDetentions
{
    private function supervisor(User $person, string $date): void
    {
        $fresh = app(DisciplineAuthority::class)->actor($person);
        abort_unless(in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true), 403);
        abort_unless(SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $fresh->id)->where('role', 'Teacher')->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $date))->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))->exists(), 403);
    }

    public function create(User $actor, int $year, int $grade, array $input): array
    {
        $a = app(DisciplineAuthority::class);
        $a->head($actor, $year, $grade, true);
        $y = $a->year($year, true);
        GradeLevel::findOrFail($grade);
        $data = Validator::make($input, ['supervisor_id' => ['required', 'integer', 'min:1'], 'on_date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'location' => ['required', 'string', 'max:200'], 'capacity' => ['required', 'integer', 'between:1,200']])->validate();
        abort_unless($data['on_date'] >= $y->start_date && $data['on_date'] <= $y->end_date && $data['on_date'] >= app(AcademicAuthority::class)->today(), 422, 'Schedule detention within the current year and not in the past.');
        $this->supervisor(User::findOrFail($data['supervisor_id']), $data['on_date']);

        return DB::transaction(function () use ($actor, $year, $grade, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(DisciplineAuthority::class)->head($actor, $year, $grade, true);
            app(DisciplineAuthority::class)->year($year, true);
            $this->supervisor(User::findOrFail($data['supervisor_id']), $data['on_date']);
            abort_if(DetentionSession::where('supervisor_id', $data['supervisor_id'])->whereDate('on_date', $data['on_date'])->where('status', 'planned')
                ->where('starts_at', '<', $data['ends_at'])->where('ends_at', '>', $data['starts_at'])->exists(), 409, 'The supervisor already has an overlapping detention session.');
            $row = DetentionSession::create($data + ['session_year_id' => $year, 'grade_level_id' => $grade, 'timezone' => app(CurrentSchool::class)->school()->timezone, 'revision' => 1, 'status' => 'planned']);
            $this->version($row, $actor, ['kind' => 'created']);
            Audit::record('discipline.detention_created', $row, [], ['revision' => 1]);

            return $this->metadata($row);
        });
    }

    public function authorize(User $actor, int $id, bool $write = false): DetentionSession
    {
        $fresh = app(DisciplineAuthority::class)->actor($actor);
        $row = DetentionSession::select('id', 'session_year_id', 'grade_level_id', 'supervisor_id', 'on_date', 'starts_at', 'ends_at', 'timezone', 'status', 'capacity', 'revision')->findOrFail($id);
        app(DisciplineAuthority::class)->year((int) $row->session_year_id);
        app(DisciplineAuthority::class)->enabled($write);
        if ((int) $row->supervisor_id === (int) $fresh->id) {
            $this->supervisor($fresh, $row->on_date->toDateString());
        } else {
            app(DisciplineAuthority::class)->head($fresh, (int) $row->session_year_id, (int) $row->grade_level_id, $write);
        }

        return $row;
    }

    public function enroll(User $actor, int $session, int $record, int $revision): array
    {
        $meta = $this->authorize($actor, $session, true);
        app(DisciplineAuthority::class)->head($actor, (int) $meta->session_year_id, (int) $meta->grade_level_id, true);

        return DB::transaction(function () use ($actor, $session, $record, $revision) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $s = $this->authorize($actor, $session, true);
            app(DisciplineAuthority::class)->head($actor, (int) $s->session_year_id, (int) $s->grade_level_id, true);
            abort_unless($s->revision === $revision && $s->status === 'planned', 409, 'The detention session changed. Reload before adding a learner.');
            $r = app(DisciplineRecords::class)->authorize($actor, $record);
            abort_unless($r->status === 'confirmed' && (int) $r->session_year_id === (int) $s->session_year_id && (int) $r->grade_level_id === (int) $s->grade_level_id, 422, 'Only a confirmed same-year/grade detention may be rostered.');
            $body = DisciplineRecord::select('id', 'basis')->findOrFail($record);
            $sanction = collect($body->basis['definition']['sanctions'])->firstWhere('code', $r->sanction_code);
            abort_unless(($sanction['kind'] ?? null) === 'detention', 422, 'This entry does not carry an approved detention sanction.');
            abort_unless($s->on_date->toDateString() >= $r->occurred_on->toDateString(), 422, 'The detention cannot precede its occurrence.');
            abort_if(DetentionEntry::where('record_id', $record)->where('status', 'active')->exists(), 409, 'This discipline entry already has an active detention assignment.');
            abort_if(DetentionEntry::where('session_id', $session)->where('learner_id', $r->learner_id)->exists(), 409, 'This learner already has a retained slot in this session.');
            $overlap = DetentionSession::whereDate('on_date', $s->on_date->toDateString())->where('status', 'planned')->where('starts_at', '<', $s->ends_at)->where('ends_at', '>', $s->starts_at)->select('id');
            abort_if(DetentionEntry::where('learner_id', $r->learner_id)->where('status', 'active')->whereIn('session_id', $overlap)
                ->whereIn('record_id', DisciplineRecord::where('status', 'confirmed')->select('id'))->exists(), 409, 'The learner already has an overlapping detention session.');
            abort_unless(DetentionEntry::where('session_id', $session)->where('status', 'active')->whereIn('record_id', DisciplineRecord::where('status', 'confirmed')->select('id'))->count() < $s->capacity, 409, 'The detention session is full.');
            $entry = DetentionEntry::create(['session_id' => $session, 'record_id' => $record, 'learner_id' => $r->learner_id, 'revision' => 1, 'status' => 'active']);
            DetentionSession::whereKey($session)->update(['revision' => $revision + 1]);
            $s->revision++;
            $this->version($s, $actor, ['kind' => 'learner_added', 'entry_id' => (int) $entry->id, 'record_id' => $record]);
            Audit::record('discipline.detention_rostered', $entry, [], ['revision' => $s->revision]);

            return ['session_id' => $session, 'entry_id' => (int) $entry->id, 'session_revision' => $s->revision, 'entry_revision' => 1];
        });
    }

    public function read(User $actor, int $id): array
    {
        $meta = $this->authorize($actor, $id);
        $body = DetentionSession::select('id', 'location')->findOrFail($id);
        $entries = DetentionEntry::where('session_id', $id)->orderBy('id')->get();
        $learners = Students::whereIn('id', $entries->pluck('learner_id'))->with('user')->get()->keyBy('id');
        $records = DisciplineRecord::select('id', 'status')->whereIn('id', $entries->pluck('record_id'))->get()->keyBy('id');
        $result = $entries->map(fn ($e) => ['id' => (int) $e->id, 'record_id' => (int) $e->record_id, 'learner_id' => (int) $e->learner_id,
            'name' => trim(($learners->get($e->learner_id)?->user?->first_name ?? '').' '.($learners->get($e->learner_id)?->user?->last_name ?? '')),
            'attendance' => $e->attendance, 'status' => $e->status, 'source_valid' => $records->get($e->record_id)?->status === 'confirmed',
            'reason' => $e->reason, 'revision' => $e->revision])->all();
        $this->authorize($actor, $id);

        return $this->metadata($meta) + ['location' => $body->location, 'entries' => $result];
    }

    public function attendance(User $actor, int $session, int $entry, int $revision, int $sessionRevision, string $state, string $reason): array
    {
        $meta = $this->authorize($actor, $session, true);
        Validator::make(['state' => $state, 'reason' => $reason], ['state' => ['required', 'in:present,absent,excused'], 'reason' => ['required', 'string', 'min:3', 'max:1000']])->validate();
        abort_unless($meta->on_date->toDateString() <= app(AcademicAuthority::class)->today(), 422, 'Do not capture future detention attendance.');

        return DB::transaction(function () use ($actor, $session, $entry, $revision, $sessionRevision, $state, $reason) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $s = $this->authorize($actor, $session, true);
            abort_unless($s->revision === $sessionRevision && $s->status === 'planned', 409, 'The detention register changed. Reload before capturing.');
            $e = DetentionEntry::where('session_id', $session)->lockForUpdate()->findOrFail($entry);
            abort_unless($e->revision === $revision && $e->status === 'active', 409, 'This detention allocation changed. Reload before capturing.');
            abort_unless(DisciplineRecord::whereKey($e->record_id)->where('status', 'confirmed')->exists(), 409, 'The discipline sanction is no longer active.');
            if ($e->attendance !== null) {
                app(DisciplineAuthority::class)->head($actor, (int) $s->session_year_id, (int) $s->grade_level_id, true);
            }
            $e->fill(['attendance' => $state, 'reason' => $reason, 'revision' => $revision + 1])->save();
            DetentionSession::whereKey($session)->update(['revision' => $sessionRevision + 1]);
            $s->revision++;
            $this->version($s, $actor, ['kind' => 'attendance', 'entry_id' => $entry, 'state' => $state, 'reason' => $reason, 'entry_revision' => $e->revision]);
            Audit::record('discipline.detention_attendance', $e, [], ['revision' => $e->revision, 'state' => $state]);

            return ['session_id' => $session, 'entry_id' => $entry, 'attendance' => $state, 'entry_revision' => $e->revision, 'session_revision' => $s->revision];
        });
    }

    public function cancel(User $actor, int $session, int $revision, string $reason): array
    {
        $s = $this->authorize($actor, $session, true);
        app(DisciplineAuthority::class)->head($actor, (int) $s->session_year_id, (int) $s->grade_level_id, true);
        Validator::make(['reason' => $reason], ['reason' => ['required', 'string', 'min:3', 'max:1000']])->validate();

        return DB::transaction(function () use ($actor, $session, $revision, $reason) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $s = $this->authorize($actor, $session, true);
            app(DisciplineAuthority::class)->head($actor, (int) $s->session_year_id, (int) $s->grade_level_id, true);
            abort_unless($s->revision === $revision && $s->status === 'planned', 409, 'The detention session changed.');
            DetentionSession::whereKey($session)->update(['status' => 'cancelled', 'revision' => $revision + 1]);
            DetentionEntry::where('session_id', $session)->where('status', 'active')->update(['status' => 'cancelled']);
            $s->status = 'cancelled';
            $s->revision++;
            $this->version($s, $actor, ['kind' => 'cancelled', 'reason' => $reason]);
            Audit::record('discipline.detention_cancelled', $s, [], ['revision' => $s->revision]);

            return $this->metadata($s);
        });
    }

    private function metadata(DetentionSession $s): array
    {
        return ['id' => (int) $s->id, 'session_year_id' => (int) $s->session_year_id, 'grade_level_id' => (int) $s->grade_level_id, 'supervisor_id' => (int) $s->supervisor_id,
            'on_date' => $s->on_date->toDateString(), 'starts_at' => $s->starts_at, 'ends_at' => $s->ends_at, 'timezone' => $s->timezone, 'capacity' => $s->capacity, 'status' => $s->status, 'revision' => $s->revision];
    }

    private function version(DetentionSession $s, User $actor, array $payload): void
    {
        DetentionVersion::create(['session_id' => $s->id, 'actor_id' => $actor->id, 'revision' => $s->revision, 'created_at' => now(), 'payload' => $payload]);
    }
}
