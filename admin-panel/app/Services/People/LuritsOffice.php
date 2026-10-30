<?php

declare(strict_types=1);

namespace App\Services\People;

use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The school office's LURITS numbers (Action Plan 1.0e and follow-up 3 of
 * engineering/specs/platform/lurits.md): how many learners have one, the
 * learners still without one, recording or correcting a number, and the
 * school's own claims that wait for Navigate Ed support. A number another
 * learner holds is never written; the office is told only that it is already
 * registered, never to whom.
 */
class LuritsOffice
{
    public const PERMISSION = 'student-edit';

    public function __construct(private readonly LuritsNumbers $numbers) {}

    /** @return array{learners: int, with: int, without: int, open_claims: int} */
    public function summary(User $actor): array
    {
        $this->authorise($actor);
        $learners = Students::query()->count();
        $with = DB::table('learner_identifiers')->whereNotNull('lurits_hash')->whereIn('user_id', Students::query()->select('user_id'))->count();

        return ['learners' => $learners, 'with' => $with, 'without' => $learners - $with,
            'open_claims' => SchoolTable::query('lurits_conflicts')->where('status', 'open')->count()];
    }

    /** Learners without a LURITS number, by surname. */
    public function missing(User $actor, int $perPage = 50): LengthAwarePaginator
    {
        $this->authorise($actor);
        $with = DB::table('learner_identifiers')->whereNotNull('lurits_hash')->select('user_id');

        return Students::query()->with(['user:id,first_name,last_name', 'class_section.class', 'class_section.section'])
            ->whereNotIn('user_id', $with)
            ->join('users', 'users.id', '=', 'students.user_id')->orderBy('users.last_name')->orderBy('users.first_name')
            ->select('students.*')->paginate($perPage, ['*'], 'missing_page');
    }

    /**
     * The school's open claims: numbers it tried to record that another
     * learner holds. The holder is never shown.
     *
     * @return list<array{id: int, learner: string, admission_no: string, source: string, raised_at: string}>
     */
    public function claims(User $actor): array
    {
        $this->authorise($actor);
        $rows = SchoolTable::query('lurits_conflicts')->where('status', 'open')->orderByDesc('created_at')->limit(200)->get();
        $learners = Students::query()->with('user:id,first_name,last_name')->whereIn('id', $rows->pluck('learner_id'))->get()->keyBy('id');

        return $rows->map(fn (object $row) => [
            'id' => (int) $row->id,
            'learner' => trim(($learners[$row->learner_id]?->user?->first_name ?? '').' '.($learners[$row->learner_id]?->user?->last_name ?? '')),
            'admission_no' => (string) ($learners[$row->learner_id]?->admission_no ?? ''),
            'source' => (string) $row->source,
            'raised_at' => substr((string) $row->created_at, 0, 10),
        ])->values()->all();
    }

    /**
     * Records or corrects a learner's number, found by admission number.
     *
     * @return string LuritsNumbers::RECORDED, UNCHANGED or CONFLICT
     */
    public function record(User $actor, string $admissionNo, string $number): string
    {
        $this->authorise($actor);
        $learner = Students::query()->where('admission_no', trim($admissionNo))->first();
        if ($learner === null) {
            throw ValidationException::withMessages(['admission_no' => __('No learner with admission number ":number".', ['number' => $admissionNo])]);
        }
        if (! LuritsNumbers::valid(LuritsNumbers::normalise($number))) {
            throw ValidationException::withMessages(['lurits' => __('":number" is not a valid LURITS number.', ['number' => $number])]);
        }

        return $this->numbers->record($learner, $number, 'school_entry', $actor);
    }

    private function authorise(User $actor): void
    {
        abort_unless($actor->can(self::PERMISSION), 403);
    }
}
