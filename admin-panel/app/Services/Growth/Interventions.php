<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\Intervention;
use App\Models\Growth\InterventionVersion;
use App\Models\Growth\RiskCaseload;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** School actions, distinct from clinical case notes; bodies are current-owner only. */
class Interventions
{
    public const META = ['id', 'learner_id', 'session_year_id', 'grade_level_id', 'owner_id', 'created_by', 'owner_authority', 'started_on', 'review_on', 'state', 'revision', 'updated_at'];

    public function assign(User $actor, int $learner, int $year, int $professional, array $input): array
    {
        app(RiskAuthority::class)->administer($actor, $learner, $year);
        app(SensitiveRecordsAccess::class)->actor(User::findOrFail($professional));
        $data = Validator::make($input, ['starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'], 'expected_revision' => ['required', 'integer', 'min:0']])->validate();

        return DB::transaction(function () use ($actor, $learner, $year, $professional, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(RiskAuthority::class)->administer($actor, $learner, $year);
            app(SensitiveRecordsAccess::class)->actor(User::findOrFail($professional));
            $row = RiskCaseload::where('learner_id', $learner)->where('session_year_id', $year)->where('professional_id', $professional)->lockForUpdate()->first();
            abort_unless((int) ($row?->revision ?? 0) === (int) $data['expected_revision'], 409, 'The risk caseload changed. Reload before saving.');
            $row ??= new RiskCaseload(['learner_id' => $learner, 'session_year_id' => $year, 'professional_id' => $professional]);
            $before = $row->exists ? $this->grant($row) : [];
            $row->fill(['starts_on' => $data['starts_on'], 'ends_on' => $data['ends_on'], 'revoked_at' => null, 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('growth.risk_caseload_changed', $row, $before, $this->grant($row));

            return $this->grant($row);
        });
    }

    public function revoke(User $actor, int $learner, int $year, int $professional, int $revision): array
    {
        app(RiskAuthority::class)->administer($actor, $learner, $year);

        return DB::transaction(function () use ($actor, $learner, $year, $professional, $revision) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            app(RiskAuthority::class)->administer($actor, $learner, $year);
            $row = RiskCaseload::where('learner_id', $learner)->where('session_year_id', $year)->where('professional_id', $professional)->lockForUpdate()->firstOrFail();
            abort_unless($row->revision === $revision && $row->revoked_at === null, 409, 'The risk caseload changed. Reload before ending access.');
            $before = $this->grant($row);
            $row->forceFill(['revoked_at' => now(), 'revision' => $row->revision + 1])->save();
            Audit::record('growth.risk_caseload_ended', $row, $before, $this->grant($row));

            return $this->grant($row);
        });
    }

    private function grant(RiskCaseload $row): array
    {
        return ['id' => (int) $row->id, 'starts_on' => $row->starts_on->toDateString(), 'ends_on' => $row->ends_on->toDateString(),
            'revoked' => $row->revoked_at !== null, 'revision' => $row->revision];
    }

    public function create(User $actor, int $learner, int $year, array $input): array
    {
        $authority = app(RiskAuthority::class);
        $authority->actor($actor);
        $authority->year($year, true);
        $grade = $authority->currentGrade($learner, $year);
        $authority->caseActor($actor, $learner, $year, $grade, true);
        $data = Validator::make($input, ['owner_id' => ['required', 'integer', 'min:1'], 'owner_authority' => ['sometimes', 'in:academic,professional,leadership'], 'action' => ['required', 'string', 'max:20000'],
            'started_on' => ['required', 'date_format:Y-m-d'], 'review_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:started_on']])->validate();
        $schoolYear = SessionYear::findOrFail($year);
        abort_unless($data['started_on'] >= $schoolYear->start_date && $data['started_on'] <= $schoolYear->end_date, 422, 'Start the intervention within its school year.');
        if ((int) $data['owner_id'] !== (int) $actor->id) {
            $authority->administer($actor, $learner, $year);
        }
        $owner = User::findOrFail($data['owner_id']);
        $kind = $data['owner_authority'] ?? $authority->caseKind($owner, $learner, $year, $grade, true);
        $authority->caseActor($owner, $learner, $year, $grade, true, $kind);

        return DB::transaction(function () use ($actor, $learner, $year, $grade, $data, $authority, $kind) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $authority->caseActor($actor, $learner, $year, $grade, true);
            if ((int) $data['owner_id'] !== (int) $actor->id) {
                $authority->administer($actor, $learner, $year);
            }
            $authority->caseActor(User::findOrFail($data['owner_id']), $learner, $year, $grade, true, $kind);
            abort_unless($authority->currentGrade($learner, $year) === $grade, 409, 'The learner enrolment changed. Reload before saving.');
            $row = Intervention::create($data + ['learner_id' => $learner, 'session_year_id' => $year, 'grade_level_id' => $grade,
                'created_by' => $actor->id, 'owner_authority' => $kind, 'revision' => 1, 'state' => 'active']);
            $this->version($row, $actor);
            Audit::record('growth.intervention_created', $row, [], ['revision' => 1]);

            return $this->metadata($row);
        });
    }

    public function authorized(User $actor, int $id, bool $write = false): Intervention
    {
        app(RiskAuthority::class)->actor($actor);
        $row = Intervention::select(self::META)->findOrFail($id);
        $fresh = app(RiskAuthority::class)->caseActor($actor, (int) $row->learner_id, (int) $row->session_year_id, (int) $row->grade_level_id, $write, $row->owner_authority);
        abort_unless((int) $row->owner_id === (int) $fresh->id, 404);

        return $row;
    }

    public function read(User $actor, int $id): array
    {
        $meta = $this->authorized($actor, $id);
        // Separate lookup after policy: denied readers never select body columns.
        $body = Intervention::select('id', 'action', 'outcome')->findOrFail($id);
        Audit::record('growth.intervention_read', $meta, [], ['revision' => $meta->revision]);

        $this->authorized($actor, $id);

        return $this->metadata($meta) + ['action' => $body->action, 'outcome' => $body->outcome];
    }

    public function update(User $actor, int $id, array $input): array
    {
        $this->authorized($actor, $id, true);
        $data = Validator::make($input, ['expected_revision' => ['required', 'integer', 'min:1'], 'action' => ['required', 'string', 'max:20000'],
            'review_on' => ['required', 'date_format:Y-m-d'], 'state' => ['required', 'in:active,completed,cancelled'],
            'outcome' => ['nullable', 'string', 'max:20000', 'required_if:state,completed'],
            'owner_id' => ['prohibited'], 'owner_authority' => ['prohibited'], 'learner_id' => ['prohibited'],
            'session_year_id' => ['prohibited'], 'grade_level_id' => ['prohibited'], 'started_on' => ['prohibited'], 'created_by' => ['prohibited']])->validate();

        return DB::transaction(function () use ($actor, $id, $data) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = $this->authorized($actor, $id, true);
            abort_unless($meta->revision === (int) $data['expected_revision'], 409, 'This intervention changed. Reload before saving.');
            abort_unless($data['review_on'] >= $meta->started_on->toDateString(), 422, 'Review date cannot precede the intervention.');
            abort_unless($meta->state === 'active', 409, 'Retained completed interventions are read-only.');
            $row = Intervention::lockForUpdate()->findOrFail($id);
            unset($data['expected_revision']);
            $row->fill($data + ['revision' => $row->revision + 1])->save();
            $this->version($row, $actor);
            Audit::record('growth.intervention_updated', $row, [], ['revision' => $row->revision, 'state' => $row->state]);

            return $this->metadata($row);
        });
    }

    public function history(User $actor, int $id): array
    {
        $this->authorized($actor, $id);

        $result = InterventionVersion::where('intervention_id', $id)->orderByDesc('revision')->limit(100)->get()
            ->map(fn ($row) => ['revision' => $row->revision, 'created_at' => $row->created_at->toISOString(), 'actor_id' => (int) $row->actor_id, 'record' => $row->payload])->all();
        $this->authorized($actor, $id);

        return $result;
    }

    /** Explicit same-kind care handoff; metadata administrators never select the body. */
    public function transferOwner(User $actor, int $id, int $owner, int $revision, string $reason): array
    {
        app(RiskAuthority::class)->actor($actor);
        $meta = Intervention::select(self::META)->findOrFail($id);
        app(RiskAuthority::class)->administer($actor, (int) $meta->learner_id, (int) $meta->session_year_id);
        Validator::make(['reason' => $reason, 'revision' => $revision], ['reason' => ['required', 'string', 'min:3', 'max:1000'],
            'revision' => ['required', 'integer', 'min:1']])->validate();

        return DB::transaction(function () use ($actor, $id, $owner, $revision, $reason) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $meta = Intervention::select(self::META)->lockForUpdate()->findOrFail($id);
            $authority = app(RiskAuthority::class);
            $authority->administer($actor, (int) $meta->learner_id, (int) $meta->session_year_id);
            $authority->enabled(true);
            abort_unless($meta->revision === $revision, 409, 'This intervention changed. Reload before changing its owner.');
            $authority->caseActor(User::findOrFail($owner), (int) $meta->learner_id, (int) $meta->session_year_id,
                (int) $meta->grade_level_id, false, $meta->owner_authority);
            $previous = (int) $meta->owner_id;
            Intervention::whereKey($id)->update(['owner_id' => $owner, 'revision' => $revision + 1, 'updated_at' => now()]);
            InterventionVersion::create(['intervention_id' => $id, 'actor_id' => $actor->id, 'revision' => $revision + 1,
                'payload' => ['kind' => 'owner_change', 'previous_owner_id' => $previous, 'owner_id' => $owner, 'reason' => $reason], 'created_at' => now()]);
            $meta->owner_id = $owner;
            $meta->revision = $revision + 1;
            Audit::record('growth.intervention_owner_changed', $meta, ['revision' => $revision], ['revision' => $revision + 1]);

            return $this->metadata($meta);
        });
    }

    private function version(Intervention $row, User $actor): void
    {
        InterventionVersion::create(['intervention_id' => $row->id, 'actor_id' => $actor->id, 'revision' => $row->revision,
            'payload' => ['kind' => 'record', 'action' => $row->action, 'outcome' => $row->outcome, 'review_on' => $row->review_on->toDateString(), 'state' => $row->state], 'created_at' => now()]);
    }

    private function metadata(Intervention $row): array
    {
        return ['id' => (int) $row->id, 'learner_id' => (int) $row->learner_id, 'session_year_id' => (int) $row->session_year_id,
            'grade_level_id' => (int) $row->grade_level_id, 'owner_authority' => $row->owner_authority, 'owner_id' => (int) $row->owner_id, 'started_on' => $row->started_on->toDateString(),
            'review_on' => $row->review_on->toDateString(), 'state' => $row->state, 'revision' => $row->revision];
    }
}
