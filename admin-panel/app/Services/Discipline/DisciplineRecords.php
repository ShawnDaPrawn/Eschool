<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Academics\GradeLevel;
use App\Models\Discipline\DetentionEntry;
use App\Models\Discipline\DetentionSession;
use App\Models\Discipline\DisciplineAcknowledgement;
use App\Models\Discipline\DisciplineFramework;
use App\Models\Discipline\DisciplineRecord;
use App\Models\Discipline\DisciplineRecordVersion;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Term;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\DisciplineChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DisciplineRecords
{
    public const META = ['id', 'learner_id', 'session_year_id', 'grade_level_id', 'framework_id', 'framework_revision', 'recorded_by', 'occurred_on', 'infraction_code', 'level_code', 'sanction_code', 'status', 'hearing_decision', 'hearing_decided_by', 'revision', 'published_at', 'acknowledged_by_parent_at', 'acknowledged_by_parent_id'];

    public function create(User $actor, int $learner, int $year, array $input): array
    {
        $a = app(DisciplineAuthority::class);
        $a->actor($actor);
        $a->year($year, true);
        $data = Validator::make($input, ['occurred_on' => ['required', 'date_format:Y-m-d'], 'infraction_code' => ['required', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:2000'], 'staff_note' => ['nullable', 'string', 'max:2000'], 'sanction_code' => ['sometimes', 'nullable', 'string', 'max:40']])->validate();
        $grade = $a->record($actor, $learner, $year, $data['occurred_on']);

        return DB::transaction(function () use ($actor, $learner, $year, $data, $grade, $a) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            abort_unless($a->record($actor, $learner, $year, $data['occurred_on']) === $grade, 409, 'The dated enrolment changed. Reload before recording.');
            $framework = DisciplineFramework::where('session_year_id', $year)->first();
            abort_unless($framework !== null, 422, 'Configure the school discipline framework first.');
            $definition = $framework->definition;
            $infraction = collect($definition['infractions'])->firstWhere('code', $data['infraction_code']);
            abort_unless($infraction !== null, 422, 'Choose an infraction from the current framework.');
            $start = $this->windowStart($year, $grade, $data['occurred_on'], $definition);
            $count = DisciplineRecord::where('learner_id', $learner)->where('session_year_id', $year)->where('grade_level_id', $grade)
                ->whereIn('status', ['confirmed', 'hearing'])->whereBetween('occurred_on', [$start, $data['occurred_on']])
                ->when($definition['counter_scope'] === 'same_infraction', fn ($q) => $q->where('infraction_code', $data['infraction_code']))->count() + 1;
            $levels = $definition['levels'];
            $base = array_search($infraction['base_level'], array_column($levels, 'code'), true);
            $selected = (int) $base;
            foreach ($levels as $i => $level) {
                if ($count >= (int) $level['threshold']) {
                    $selected = max($selected, $i);
                }
            }
            $level = $levels[$selected];
            $pending = $selected > 0 || $level['kind'] === 'hearing';
            $sanction = $pending ? null : ($data['sanction_code'] ?? $level['sanctions'][0]);
            abort_unless($pending || in_array($sanction, $level['sanctions'], true), 422, 'Choose an approved sanction for this level.');
            $row = DisciplineRecord::create(['learner_id' => $learner, 'session_year_id' => $year, 'grade_level_id' => $grade, 'framework_id' => $framework->id,
                'framework_revision' => $framework->revision, 'recorded_by' => $actor->id, 'occurred_on' => $data['occurred_on'],
                'infraction_code' => $data['infraction_code'], 'level_code' => $level['code'], 'sanction_code' => $sanction, 'status' => $pending ? 'pending_review' : 'confirmed',
                'description' => $data['description'], 'staff_note' => $data['staff_note'] ?? null, 'revision' => 1, 'published_at' => $pending ? null : now(),
                'basis' => ['definition' => $definition, 'infraction_name' => $infraction['name'], 'window_start' => $start, 'occurrence_count' => $count]]);
            $this->version($row, $actor, 'recorded');
            Audit::record('discipline.recorded', $row, [], ['revision' => 1, 'status' => $row->status]);

            return $this->metadata($row);
        });
    }

    private function windowStart(int $year, int $grade, string $date, array $definition): string
    {
        $start = SessionYear::withTrashed()->findOrFail($year)->start_date;
        if ($definition['reset'] === 'rolling_days') {
            return max($start, CarbonImmutable::parse($date)->subDays((int) $definition['rolling_days'] - 1)->toDateString());
        }
        if ($definition['reset'] === 'term') {
            $g = GradeLevel::findOrFail($grade);
            $term = Term::where('session_year_id', $year)->forGrade((int) $g->sort_order)->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();
            abort_unless($term !== null, 422, 'A term reset requires a mapped term for the occurrence date.');

            return $term->start_date->toDateString();
        }

        return $start;
    }

    public function authorize(User $actor, int $id): DisciplineRecord
    {
        app(DisciplineAuthority::class)->actor($actor);
        $row = DisciplineRecord::select(self::META)->findOrFail($id);
        app(DisciplineAuthority::class)->read($actor, $row);

        return $row;
    }

    public function read(User $actor, int $id): array
    {
        $meta = $this->authorize($actor, $id);
        $row = DisciplineRecord::findOrFail($id);
        $this->authorize($actor, $id);
        Audit::record('discipline.record_read', $meta, [], ['revision' => $meta->revision]);

        return $this->metadata($row) + ['description' => $row->description, 'staff_note' => $row->staff_note, 'decision_reason' => $row->decision_reason,
            'family_reason' => $row->family_reason, 'basis' => $row->basis];
    }

    public function versions(User $actor, int $id): array
    {
        $this->authorize($actor, $id);
        $versions = DisciplineRecordVersion::where('record_id', $id)->orderByDesc('revision')->limit(100)->get()->map(fn ($v) => ['revision' => $v->revision, 'actor_id' => (int) $v->actor_id, 'created_at' => $v->created_at->toISOString(), 'record' => $v->payload])->all();
        $this->authorize($actor, $id);

        return $versions;
    }

    public function review(User $actor, int $id, array $input): array
    {
        $meta = $this->authorize($actor, $id);
        app(DisciplineAuthority::class)->head($actor, (int) $meta->session_year_id, (int) $meta->grade_level_id, true);
        $data = Validator::make($input, ['expected_revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:confirm,overturn'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'], 'family_reason' => ['nullable', 'string', 'max:1000'], 'sanction_code' => ['nullable', 'string', 'max:40']])->validate();

        return DB::transaction(function () use ($actor, $id, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = $this->authorize($actor, $id);
            app(DisciplineAuthority::class)->head($actor, (int) $meta->session_year_id, (int) $meta->grade_level_id, true);
            if ($meta->hearing_decided_by !== null) {
                app(DisciplineAuthority::class)->principal($actor, (int) $meta->session_year_id, true);
            }
            abort_unless($meta->revision === (int) $data['expected_revision'], 409, 'The entry changed. Reload before reviewing.');
            abort_if($meta->status === 'overturned', 409, 'The retained entry was already overturned.');
            $row = DisciplineRecord::lockForUpdate()->findOrFail($id);
            if ($data['decision'] === 'overturn') {
                $row->status = 'overturned';
            } else {
                abort_unless($row->status === 'pending_review', 409, 'Only a pending escalation can be confirmed.');
                $level = collect($row->basis['definition']['levels'])->firstWhere('code', $row->level_code);
                if ($level['kind'] === 'hearing') {
                    $row->status = 'hearing';
                    $row->sanction_code = null;
                } else {
                    abort_unless(in_array($data['sanction_code'] ?? null, $level['sanctions'], true), 422, 'Choose an approved sanction.');
                    $row->status = 'confirmed';
                    $row->sanction_code = $data['sanction_code'];
                }
                $row->published_at ??= now();
            }
            $row->decision_reason = $data['reason'];
            $row->family_reason = $data['family_reason'] ?? null;
            $row->revision++;
            $row->acknowledged_by_parent_at = null;
            $row->acknowledged_by_parent_id = null;
            $row->save();
            $this->version($row, $actor, $data['decision']);
            Audit::record('discipline.reviewed', $row, [], ['revision' => $row->revision, 'status' => $row->status]);

            return $this->metadata($row);
        });
    }

    public function hearing(User $actor, int $id, array $input): array
    {
        $meta = $this->authorize($actor, $id);
        app(DisciplineAuthority::class)->principal($actor, (int) $meta->session_year_id, true);
        $data = Validator::make($input, ['expected_revision' => ['required', 'integer', 'min:1'], 'decision' => ['required', 'in:no_action,sanction'],
            'reason' => ['required', 'string', 'min:3', 'max:1000'], 'family_reason' => ['nullable', 'string', 'max:1000'], 'sanction_code' => ['nullable', 'string', 'max:40', 'required_if:decision,sanction']])->validate();

        return DB::transaction(function () use ($actor, $id, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = $this->authorize($actor, $id);
            app(DisciplineAuthority::class)->principal($actor, (int) $meta->session_year_id, true);
            abort_unless($meta->revision === (int) $data['expected_revision'] && $meta->status === 'hearing', 409, 'Only the current unresolved hearing can be decided.');
            $row = DisciplineRecord::lockForUpdate()->findOrFail($id);
            $definition = $row->basis['definition'];
            if ($data['decision'] === 'sanction') {
                abort_unless(in_array($data['sanction_code'], array_column($definition['sanctions'], 'code'), true), 422, 'Choose a defined school sanction.');
            }
            $row->fill(['status' => 'confirmed', 'hearing_decision' => $data['decision'], 'hearing_decided_by' => $actor->id,
                'sanction_code' => $data['decision'] === 'sanction' ? $data['sanction_code'] : null, 'decision_reason' => $data['reason'], 'family_reason' => $data['family_reason'] ?? null,
                'revision' => $row->revision + 1, 'acknowledged_by_parent_at' => null, 'acknowledged_by_parent_id' => null])->save();
            $this->version($row, $actor, 'hearing_resolved');
            Audit::record('discipline.hearing_resolved', $row, [], ['revision' => $row->revision, 'decision' => $data['decision']]);

            return $this->metadata($row);
        });
    }

    public function family(User $actor, int $learner, int $year, int $page = 1): array
    {
        app(DisciplineAuthority::class)->family($actor, $learner);
        app(DisciplineAuthority::class)->year($year);
        $rows = DisciplineRecord::select(array_merge(self::META, ['description', 'basis', 'family_reason']))->where('learner_id', $learner)->where('session_year_id', $year)
            ->whereNotNull('published_at')->orderByDesc('occurred_on')->orderByDesc('id')->paginate(50, ['*'], 'page', max(1, $page));
        $detentions = DetentionEntry::whereIn('record_id', $rows->pluck('id'))->where('learner_id', $learner)->orderBy('id')->get();
        $sessions = DetentionSession::whereIn('id', $detentions->pluck('session_id'))->get()->keyBy('id');
        $acks = DisciplineAcknowledgement::whereIn('record_id', $rows->pluck('id'))->where('guardian_id', $actor->id)->get()->keyBy(fn ($a) => $a->record_id.':'.$a->record_revision);
        $result = [];
        foreach ($rows as $row) {
            $d = $row->basis['definition'];
            $level = collect($d['levels'])->firstWhere('code', $row->level_code);
            $sanction = collect($d['sanctions'])->firstWhere('code', $row->sanction_code);
            $result[] = ['id' => (int) $row->id, 'occurred_on' => $row->occurred_on->toDateString(), 'description' => $row->description, 'infraction' => $row->basis['infraction_name'],
                'level' => $level['name'], 'sanction' => $row->status === 'overturned' ? null : ($sanction['name'] ?? null), 'status' => $row->status, 'family_reason' => $row->family_reason,
                'revision' => $row->revision, 'acknowledged' => $acks->has($row->id.':'.$row->revision), 'detentions' => $detentions->where('record_id', $row->id)->map(function ($entry) use ($sessions, $row) {
                    $session = $sessions->get($entry->session_id);

                    return ['session_id' => (int) $session->id, 'on_date' => $session->on_date->toDateString(), 'starts_at' => $session->starts_at, 'ends_at' => $session->ends_at,
                        'timezone' => $session->timezone, 'location' => $session->location, 'attendance' => $entry->attendance,
                        'active' => $row->status !== 'overturned' && $session->status === 'planned' && $entry->status === 'active'];
                })->values()->all()];
        }
        app(DisciplineAuthority::class)->family($actor, $learner);

        return ['learner_id' => $learner, 'session_year_id' => $year, 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'records' => $result];
    }

    public function acknowledge(User $actor, int $id, int $revision): array
    {
        app(DisciplineAuthority::class)->actor($actor, false);
        $meta = DisciplineRecord::select(self::META)->whereNotNull('published_at')->findOrFail($id);
        app(DisciplineAuthority::class)->family($actor, (int) $meta->learner_id, true);

        return DB::transaction(function () use ($actor, $id, $revision) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $row = DisciplineRecord::select(self::META)->whereNotNull('published_at')->lockForUpdate()->findOrFail($id);
            app(DisciplineAuthority::class)->family($actor, (int) $row->learner_id, true);
            abort_unless($row->revision === $revision, 409, 'The published entry changed. Read the current entry before acknowledging.');
            $ack = DisciplineAcknowledgement::firstOrCreate(['record_id' => $id, 'guardian_id' => $actor->id, 'record_revision' => $revision], ['acknowledged_at' => now()]);
            if ($row->acknowledged_by_parent_at === null) {
                DisciplineRecord::whereKey($id)->update(['acknowledged_by_parent_at' => $ack->acknowledged_at, 'acknowledged_by_parent_id' => $actor->id]);
            }
            Audit::record('discipline.acknowledged', $row, [], ['revision' => $revision]);

            return ['record_id' => $id, 'revision' => $revision, 'acknowledged_at' => $ack->acknowledged_at->toISOString()];
        });
    }

    public function metadata(DisciplineRecord $row): array
    {
        return ['id' => (int) $row->id, 'learner_id' => (int) $row->learner_id, 'session_year_id' => (int) $row->session_year_id, 'grade_level_id' => (int) $row->grade_level_id,
            'recorded_by' => (int) $row->recorded_by, 'occurred_on' => $row->occurred_on->toDateString(), 'infraction_code' => $row->infraction_code, 'level_code' => $row->level_code,
            'sanction_code' => $row->sanction_code, 'status' => $row->status, 'hearing_decision' => $row->hearing_decision, 'revision' => $row->revision, 'published' => $row->published_at !== null];
    }

    private function version(DisciplineRecord $row, User $actor, string $event): void
    {
        Outbox::record(new DisciplineChanged(app(CurrentSchool::class)->id(), (int) $row->session_year_id, (int) $row->grade_level_id, (int) $row->id, $row->revision));
        DisciplineRecordVersion::create(['record_id' => $row->id, 'actor_id' => $actor->id, 'revision' => $row->revision, 'created_at' => now(),
            'payload' => $this->metadata($row) + ['event' => $event, 'description' => $row->description, 'staff_note' => $row->staff_note, 'basis' => $row->basis, 'decision_reason' => $row->decision_reason, 'family_reason' => $row->family_reason]]);
    }
}
