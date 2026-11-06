<?php

declare(strict_types=1);

namespace App\Services\Platform\Shared;

use App\Models\Platform\School;
use App\Models\Shared\SharedParticipant;
use App\Models\Shared\SharedRecordOperation;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LogicException;

/**
 * Records shared between schools (engineering/specs/platform/shared-records.md).
 *
 * A shared record is a global row (shared_*) that names no school. Each
 * school that takes part has its own shared_participants row (school-owned):
 * its role and whether it accepted. Everything private to a school stays in
 * that school's own rows. This class is the only place that reads another
 * school's rows for a shared record, and it reads only:
 *
 *  - the other participants' school, role and state (so their names can be shown);
 *  - the columns a feature declared as published in config/shared_records.php,
 *    once the feature has checked that every school concerned accepted.
 *
 * It is also the only place that writes into another school: the invited
 * school's participant row (state invited), and work a feature runs in a
 * participant's school with inSchool() (for example notifying its own families).
 */
class SharedRecords
{
    public function __construct(private readonly CurrentSchool $school) {}

    /** @return list<string> */
    public static function types(): array
    {
        return array_keys((array) config('shared_records.types', []));
    }

    private function type(string $type): void
    {
        if (! in_array($type, self::types(), true)) {
            throw new LogicException("Unknown shared record type {$type}.");
        }
    }

    /**
     * Platform staff working inside a school (impersonation, a support
     * session) never make, answer or read shared records for it: a support
     * session never reads across schools.
     */
    public function ownStaff(): void
    {
        abort_if($this->school->isImpersonating() || $this->school->isSupportSession(), 403, 'Shared records are made by the school\'s own staff.');
    }

    /** This school's own row for the record, or null when it does not take part. */
    public function here(string $type, int $id): ?SharedParticipant
    {
        $this->type($type);

        return SharedParticipant::query()->where('record_type', $type)->where('record_id', $id)->first();
    }

    /** This school's row; a school that does not take part gets "not found", never a hint the record exists. */
    public function requireHere(string $type, int $id): SharedParticipant
    {
        return $this->here($type, $id) ?? abort(404);
    }

    /**
     * The record ids this school takes part in.
     *
     * @param  list<string>|null  $states
     * @return list<int>
     */
    public function idsHere(string $type, ?array $states = null): array
    {
        $this->type($type);

        return SharedParticipant::query()->where('record_type', $type)->when($states !== null, fn (Builder $q) => $q->whereIn('state', $states))
            ->orderBy('record_id')->pluck('record_id')->map(fn ($id) => (int) $id)->all();
    }

    /** The creating school's own row: it takes part and has accepted by creating. */
    public function start(string $type, int $id, string $role, User $actor): SharedParticipant
    {
        $this->type($type);
        $this->ownStaff();
        abort_if($this->here($type, $id) !== null, 409, 'This school already takes part.');

        return SharedParticipant::create(['record_type' => $type, 'record_id' => $id, 'role' => $role, 'state' => SharedParticipant::ACCEPTED,
            'decided_by' => $actor->id, 'decided_at' => now(), 'revision' => 1]);
    }

    /**
     * Invite another school: its own participant row, written inside that
     * school, in state invited. It sees the invitation in its own lists; this
     * school sees only its name and state until it accepts.
     */
    public function invite(string $type, int $id, int $schoolId, string $role): void
    {
        $this->type($type);
        $this->ownStaff();
        abort_if($schoolId === $this->school->id(), 422, 'Choose another school.');
        $target = School::query()->active()->find($schoolId);
        abort_if($target === null, 422, 'That school cannot take part.');
        abort_if($this->participants($type, $id)->contains('school_id', $schoolId), 409, 'That school is already invited.');
        $this->inSchool($schoolId, function () use ($type, $id, $role) {
            $row = SharedParticipant::create(['record_type' => $type, 'record_id' => $id, 'role' => $role, 'state' => SharedParticipant::INVITED, 'revision' => 1]);
            Audit::record('shared.invited', $row, [], ['record_type' => $type, 'record_id' => $id, 'role' => $role]);
        });
    }

    /** Accept or decline this school's own invitation. A decision is final. */
    public function decide(string $type, int $id, User $actor, bool $accept): SharedParticipant
    {
        $this->ownStaff();
        $row = $this->requireHere($type, $id);
        abort_unless($row->state === SharedParticipant::INVITED, 409, 'This invitation was already answered.');
        $before = $row->only(['state', 'revision']);
        $row->forceFill(['state' => $accept ? SharedParticipant::ACCEPTED : SharedParticipant::DECLINED, 'decided_by' => $actor->id, 'decided_at' => now(), 'revision' => $row->revision + 1])->save();
        Audit::record('shared.decided', $row, $before, $row->only(['state', 'revision']));

        return $row;
    }

    /**
     * Every school taking part in the record (this one included): school id,
     * school name, role and state. Nothing else is read from the other rows.
     *
     * @return Collection<int, array{school_id: int, name: string, role: string, state: string}>
     */
    public function participants(string $type, int $id): Collection
    {
        $this->type($type);
        $this->ownStaff();
        // Across schools on purpose: every school's participant row of this one record, three columns.
        $rows = SharedParticipant::acrossSchools()->where('record_type', $type)->where('record_id', $id)->orderBy('id')->get(['school_id', 'role', 'state']);
        $names = School::query()->whereIn('id', $rows->pluck('school_id'))->pluck('name', 'id');

        return $rows->map(fn ($row) => ['school_id' => (int) $row->school_id, 'name' => (string) ($names[$row->school_id] ?? ''), 'role' => (string) $row->role, 'state' => (string) $row->state])->values();
    }

    /** Whether every school taking part has accepted (the record is open between them). */
    public function open(string $type, int $id): bool
    {
        $all = $this->participants($type, $id);

        return $all->isNotEmpty() && $all->every(fn ($row) => $row['state'] === SharedParticipant::ACCEPTED);
    }

    /**
     * The published columns of another school's rows, for a feature that has
     * already checked the schools concerned accepted. Only models and columns
     * declared in config/shared_records.php 'published' can be read.
     *
     * @param  class-string<Model>  $model
     * @param  list<string>  $columns
     * @param  callable(Builder): void  $where
     * @return Collection<int, Model>
     */
    public function published(string $model, int $schoolId, array $columns, callable $where): Collection
    {
        $this->ownStaff();
        $allowed = (array) (config('shared_records.published')[$model] ?? []);
        if ($allowed === [] || array_diff($columns, $allowed) !== []) {
            throw new LogicException("{$model} does not publish ".implode(', ', array_diff($columns, $allowed)).'.');
        }
        if ($schoolId === $this->school->id()) {
            $query = $model::query();
        } else {
            $query = $model::acrossSchools()->where('school_id', $schoolId);
        }
        $where($query);

        return $query->get($columns);
    }

    /**
     * Run work inside a participating school (its own scope, settings and
     * roles), for example notifying that school's families of a confirmed
     * result. Never used to read that school's private rows for this one.
     */
    public function inSchool(int $schoolId, callable $callback): mixed
    {
        return $schoolId === $this->school->id() ? $callback() : $this->school->run($schoolId, $callback);
    }

    /**
     * Active schools whose name matches, other than this one: for choosing
     * whom to invite. Names only.
     *
     * @return list<array{id: int, name: string}>
     */
    public function directory(string $search): array
    {
        $this->ownStaff();
        $term = '%'.addcslashes(trim($search), '%_\\').'%';

        return School::query()->active()->whereKeyNot($this->school->id())->where('name', 'like', $term)->orderBy('name')->limit(10)->get(['id', 'name'])
            ->map(fn ($school) => ['id' => (int) $school->id, 'name' => (string) $school->name])->all();
    }

    public function schoolName(int $schoolId): string
    {
        return (string) School::query()->whereKey($schoolId)->value('name');
    }

    /**
     * One write with an operation id: the school is locked, authority is
     * checked fresh, a replay of the same operation returns its receipt and a
     * different request under the same id is refused.
     */
    public function once(User $actor, string $kind, array $data, callable $authority, callable $write): array
    {
        $this->ownStaff();
        $uuid = Validator::make($data, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $authority, $write, $uuid, $hash) {
            School::query()->whereKey($this->school->id())->lockForUpdate()->firstOrFail();
            $authority();
            $old = SharedRecordOperation::query()->where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This operation already contains a different change.');

                return $old->receipt;
            }
            $result = $write();
            SharedRecordOperation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }
}
