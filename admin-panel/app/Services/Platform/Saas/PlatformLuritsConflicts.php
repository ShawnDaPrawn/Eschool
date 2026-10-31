<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * LURITS claims for Navigate Ed support (Action Plan 1.0f): a school tried to
 * record a number another learner already holds. Support sees the claiming
 * school, the school or schools of the current holder, the source and the
 * date, never a learner's name or the number, and records the outcome after
 * working it out with the schools. Nothing is ever merged automatically.
 * engineering/specs/platform/lurits.md.
 */
class PlatformLuritsConflicts
{
    public const OUTCOMES = ['corrected' => 'resolved', 'transfer' => 'resolved', 'rejected' => 'rejected'];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** @return list<array{id: int, school: string, holder_schools: string, source: string, raised_at: string, status: string, resolution: string|null}> */
    public function list(bool $open = true): array
    {
        return $this->currentSchool->acrossSchools(function () use ($open): array {
            $rows = DB::table('lurits_conflicts')->join('schools', 'schools.id', '=', 'lurits_conflicts.school_id')
                ->when($open, fn ($q) => $q->where('lurits_conflicts.status', 'open'), fn ($q) => $q->where('lurits_conflicts.status', '!=', 'open'))
                ->orderByDesc('lurits_conflicts.created_at')->limit(200)
                ->get(['lurits_conflicts.*', 'schools.name as school_name']);
            $holders = DB::table('students')->join('schools', 'schools.id', '=', 'students.school_id')
                ->whereIn('students.user_id', $rows->pluck('holder_user_id')->unique())->get(['students.user_id', 'schools.name'])
                ->groupBy('user_id')->map(fn ($schools) => $schools->pluck('name')->unique()->sort()->implode(', '));

            return $rows->map(fn (object $row) => [
                'id' => (int) $row->id, 'school' => (string) $row->school_name,
                'holder_schools' => (string) ($holders[$row->holder_user_id] ?? __('No school now')),
                'source' => (string) $row->source, 'raised_at' => substr((string) $row->created_at, 0, 10),
                'status' => (string) $row->status, 'resolution' => $row->resolution,
            ])->values()->all();
        });
    }

    public function resolve(int $id, array $input, User $actor): void
    {
        $data = Validator::make($input, ['outcome' => ['required', 'in:'.implode(',', array_keys(self::OUTCOMES))]])->validate();
        $this->currentSchool->acrossSchools(function () use ($id, $data, $actor) {
            DB::transaction(function () use ($id, $data, $actor) {
                $row = DB::table('lurits_conflicts')->where('id', $id)->lockForUpdate()->first();
                abort_if($row === null, 404);
                abort_unless($row->status === 'open', 409, __('This claim was already closed.'));
                DB::table('lurits_conflicts')->where('id', $id)->update([
                    'status' => self::OUTCOMES[$data['outcome']], 'resolution' => $data['outcome'],
                    'resolved_by' => $actor->id, 'resolved_at' => now(), 'updated_at' => now(),
                ]);
                Audit::record('platform_lurits_claim_closed', null, ['status' => 'open'], ['conflict_id' => $id, 'outcome' => $data['outcome']], (int) $row->school_id);
            });
        });
    }
}
