<?php

declare(strict_types=1);

namespace App\Services\People;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Audit\Audit;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Bulk promotion by grade (Action Plan 1.18). Everyone in the year moves up
 * a grade into the homeroom with the same letter by default, writing dated
 * enrolments for next year (EnrolmentService::place) with the student_sessions
 * projection refreshed. Exceptions per learner: repeat the grade, leave, or a
 * chosen homeroom. Promoting is refused if anything changed since the page was shown.
 * Learners already placed for next year are left as they are.
 * engineering/specs/academics/grade-promotion.md.
 */
class GradePromotion
{
    public const PROMOTE = 'promote';

    public const REPEAT = 'repeat';

    public const LEAVE = 'leave';

    public const PLACE = 'place';

    public const OUTCOMES = [self::PROMOTE, self::REPEAT, self::LEAVE, self::PLACE];

    public function __construct(private readonly EnrolmentService $enrolments) {}

    /**
     * The promotion for every learner in $from, with $exceptions applied.
     * $moves sends a whole homeroom somewhere other than its default.
     *
     * @param  array<int, array{outcome?: string, homeroom_id?: int|string|null}>  $exceptions  learner id => choice
     * @param  array<int, int>  $moves  homeroom id => next year's homeroom id
     * @return array{homerooms: list<array<string, mixed>>, totals: array<string, int>, unresolved: int, fingerprint: string}
     */
    public function plan(SessionYear $from, SessionYear $to, array $exceptions = [], array $moves = []): array
    {
        $this->assertYears($from, $to);
        $rooms = $this->homerooms();
        $nextGrade = $this->nextGrades();
        $lastDay = CarbonImmutable::parse($from->end_date)->toDateString();
        $enrolments = Enrolment::query()->with('learner.user')->where('session_year_id', $from->id)->onDate($lastDay)->get();
        $placed = Enrolment::query()->where('session_year_id', $to->id)->pluck('class_section_id', 'learner_id');
        $leaving = StudentSessions::query()->where('session_year_id', $to->id)->where('status', 0)->pluck('student_id')->flip();

        $groups = [];
        $totals = array_fill_keys([...self::OUTCOMES, 'already'], 0);
        $unresolved = 0;
        foreach ($enrolments->sortBy(fn ($row) => $rooms[$row->class_section_id]['name'] ?? '') as $enrolment) {
            $source = (int) $enrolment->class_section_id;
            $default = $this->defaultTarget($source, $rooms, $nextGrade);
            $moved = isset($moves[$source], $rooms[$moves[$source]]) ? (int) $moves[$source] : null;
            if ($moved !== null) {
                $default = ['target' => $moved, 'last_grade' => false, 'grade_unset' => $default['grade_unset']];
            }
            $learnerId = (int) $enrolment->learner_id;
            $choice = $exceptions[$learnerId] ?? [];
            $outcome = in_array($choice['outcome'] ?? null, self::OUTCOMES, true) ? $choice['outcome'] : ($default['last_grade'] ? self::LEAVE : self::PROMOTE);
            $target = match ($outcome) {
                self::PROMOTE => $default['target'],
                self::REPEAT => $source,
                self::PLACE => isset($choice['homeroom_id'], $rooms[(int) $choice['homeroom_id']]) ? (int) $choice['homeroom_id'] : null,
                default => null,
            };
            $already = $placed->has($learnerId) ? 'placed' : ($leaving->has($learnerId) ? 'leaving' : null);
            $missing = $already === null && in_array($outcome, [self::PROMOTE, self::PLACE], true) && $target === null;
            $already !== null ? $totals['already']++ : $totals[$outcome]++;
            $unresolved += $missing ? 1 : 0;
            $groups[$source] ??= [
                'id' => $source, 'name' => $rooms[$source]['name'] ?? '#'.$source,
                'default_target' => $default['target'], 'default_target_name' => $default['target'] !== null ? $rooms[$default['target']]['name'] : null,
                'last_grade' => $default['last_grade'], 'grade_unset' => $default['grade_unset'],
                'moved' => $moved, 'learners' => [],
            ];
            $groups[$source]['learners'][] = [
                'id' => $learnerId,
                'name' => (string) ($enrolment->learner?->user?->full_name ?? '#'.$learnerId),
                'admission_no' => (string) ($enrolment->learner?->admission_no ?? ''),
                'outcome' => $outcome,
                'target' => $target,
                'target_name' => $target !== null ? ($rooms[$target]['name'] ?? '#'.$target) : null,
                'already' => $already,
                'already_name' => $already === 'placed' ? ($rooms[(int) $placed[$learnerId]]['name'] ?? null) : null,
                'missing' => $missing,
            ];
        }
        $homerooms = array_values($groups);

        return [
            'homerooms' => $homerooms,
            'totals' => $totals,
            'unresolved' => $unresolved,
            'fingerprint' => $this->fingerprint($from, $to, $homerooms, $placed, $leaving),
        ];
    }

    /**
     * Writes the promotion: next year's enrolments, starting on its first day,
     * and leavers' rows. Refused if anything changed since the preview.
     *
     * @param  array<int, array{outcome?: string, homeroom_id?: int|string|null}>  $exceptions
     * @param  array<int, int>  $moves
     * @return array<string, int>
     */
    public function commit(User $actor, SessionYear $from, SessionYear $to, array $exceptions, string $fingerprint, array $moves = []): array
    {
        return DB::transaction(function () use ($actor, $from, $to, $exceptions, $fingerprint, $moves): array {
            // Serialise promotions of the same year.
            SessionYear::query()->whereKey($to->id)->lockForUpdate()->first();
            $plan = $this->plan($from, $to, $exceptions, $moves);
            if (! hash_equals($plan['fingerprint'], $fingerprint)) {
                throw ValidationException::withMessages(['fingerprint' => __('Something changed since this page was shown. Check the promotion below, then promote again.')]);
            }
            if ($plan['unresolved'] > 0) {
                throw ValidationException::withMessages(['outcome' => __('Choose a homeroom for every learner who has none (:count).', ['count' => $plan['unresolved']])]);
            }
            $startsOn = CarbonImmutable::parse($to->start_date)->toDateString();
            $done = array_fill_keys(self::OUTCOMES, 0);
            $leavers = [];
            $moved = [];
            foreach ($plan['homerooms'] as $room) {
                foreach ($room['learners'] as $row) {
                    if ($row['already'] !== null) {
                        continue;
                    }
                    if ($row['outcome'] === self::LEAVE) {
                        // No enrolment next year; the legacy row says "leaving".
                        StudentSessions::withoutEvents(fn () => StudentSessions::query()->updateOrCreate(
                            ['student_id' => $row['id'], 'session_year_id' => $to->id],
                            ['class_section_id' => null, 'previous_session_year_id' => $from->id],
                        ));
                        StudentSessions::query()->where('student_id', $row['id'])->where('session_year_id', $to->id)->update(['status' => 0, 'result' => 1]);
                        $leavers[] = $row['id'];
                    } else {
                        $reason = match ($row['outcome']) {
                            self::REPEAT => 'Repeating the grade (bulk promotion)',
                            self::PLACE => 'Placed by the school (bulk promotion)',
                            default => 'Promoted to the next grade (bulk promotion)',
                        };
                        $this->enrolments->place($row['id'], (int) $row['target'], (int) $to->id, $startsOn, $reason);
                        StudentSessions::query()->where('student_id', $row['id'])->where('session_year_id', $to->id)
                            ->update(['status' => 1, 'result' => $row['outcome'] === self::REPEAT ? 0 : 1, 'previous_session_year_id' => $from->id]);
                        $moved[(int) $row['target']][] = $row['id'];
                    }
                    Students::query()->whereKey($row['id'])->update(['is_new_admission' => 0]);
                    $done[$row['outcome']]++;
                }
            }
            // Into the year already in use: apply it now, as making a year
            // current does (SessionYearService::applyPromotions).
            if ((int) (getSettings('session_year')['session_year'] ?? 0) === (int) $to->id) {
                foreach ($moved as $room => $learners) {
                    Students::query()->whereIn('id', $learners)->update(['class_section_id' => $room]);
                }
                if ($leavers !== []) {
                    app(LeaverState::class)->begin($leavers, $to);
                }
            }
            Audit::record('people.grade_promotion_committed', $to, [], ['from_year' => $from->id, 'to_year' => $to->id, 'counts' => $done, 'by' => $actor->id]);

            return $done;
        });
    }

    /**
     * Placements from a pasted sheet: one row per learner, "admission number,
     * outcome, homeroom" (outcome promote, repeat, leave or place; the homeroom
     * named as on the page, e.g. "Grade 8 B", for place).
     *
     * @return array{exceptions: array<int, array{outcome: string, homeroom_id: int|null}>, errors: list<string>}
     */
    public function parseSheet(SessionYear $from, string $sheet): array
    {
        $byNumber = Students::query()->whereIn('id', Enrolment::query()->where('session_year_id', $from->id)->select('learner_id'))
            ->pluck('id', 'admission_no')->mapWithKeys(fn ($id, $number) => [mb_strtolower(trim((string) $number)) => (int) $id]);
        $rooms = collect($this->homerooms())->mapWithKeys(fn (array $room, int $id) => [mb_strtolower($room['name']) => $id]);
        $exceptions = [];
        $errors = [];
        foreach (preg_split('/\R/', trim($sheet)) ?: [] as $index => $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = array_map('trim', str_getcsv($line, ',', '"', ''));
            if ($index === 0 && in_array(mb_strtolower($cells[1] ?? ''), ['outcome', 'result'], true)) {
                continue;
            }
            $learner = $byNumber[mb_strtolower($cells[0] ?? '')] ?? null;
            $outcome = mb_strtolower($cells[1] ?? '');
            $room = ($cells[2] ?? '') !== '' ? ($rooms[mb_strtolower(preg_replace('/\s+/', ' ', $cells[2]))] ?? false) : null;
            if ($learner === null) {
                $errors[] = __('Row :row: no learner with admission number ":number" this year.', ['row' => $index + 1, 'number' => $cells[0] ?? '']);
            } elseif (! in_array($outcome, self::OUTCOMES, true)) {
                $errors[] = __('Row :row: the outcome must be promote, repeat, leave or place.', ['row' => $index + 1]);
            } elseif ($room === false || ($outcome === self::PLACE && $room === null)) {
                $errors[] = __('Row :row: no homeroom called ":room".', ['row' => $index + 1, 'room' => $cells[2] ?? '']);
            } else {
                $exceptions[$learner] = ['outcome' => $outcome, 'homeroom_id' => $room];
            }
        }

        return ['exceptions' => $exceptions, 'errors' => $errors];
    }

    /** @return array<int, string> homeroom id => name, for the "place" choice */
    public function homeroomChoices(): array
    {
        return collect($this->homerooms())->map(fn (array $room) => $room['name'])->sort()->all();
    }

    private function assertYears(SessionYear $from, SessionYear $to): void
    {
        if ((int) $from->id === (int) $to->id || CarbonImmutable::parse($to->start_date) <= CarbonImmutable::parse($from->end_date)) {
            throw ValidationException::withMessages(['to' => __('Promote into a school year that starts after this one ends.')]);
        }
    }

    /**
     * The homeroom with the same letter in the next grade (same medium, and
     * the same stream and shift where one exists), or none; last_grade when
     * there is no next grade.
     *
     * @param  array<int, array<string, mixed>>  $rooms
     * @param  array<int, int|null>  $nextGrade
     * @return array{target: int|null, last_grade: bool, grade_unset: bool}
     */
    private function defaultTarget(int $source, array $rooms, array $nextGrade): array
    {
        $room = $rooms[$source] ?? null;
        if ($room === null || $room['grade_level_id'] === null) {
            return ['target' => null, 'last_grade' => false, 'grade_unset' => true];
        }
        $next = $nextGrade[$room['grade_level_id']] ?? null;
        if ($next === null) {
            return ['target' => null, 'last_grade' => true, 'grade_unset' => false];
        }
        $candidates = collect($rooms)->filter(fn (array $other) => $other['grade_level_id'] === $next && $other['section_id'] === $room['section_id'] && $other['medium_id'] === $room['medium_id']);
        $best = $candidates->sortBy(fn (array $other) => [($other['stream_id'] === $room['stream_id'] ? 0 : 1) + ($other['shift_id'] === $room['shift_id'] ? 0 : 1), $other['id']])->first();

        return ['target' => $best['id'] ?? null, 'last_grade' => false, 'grade_unset' => false];
    }

    /**
     * Every homeroom with a label that is unique in the school: "Grade 8 A",
     * or "Grade 8 A (Afrikaans, Science, Afternoon)" when several homerooms
     * share a class name and letter across mediums, streams or shifts.
     *
     * @return array<int, array{id: int, name: string, grade_level_id: int|null, section_id: int, medium_id: int|null, stream_id: int|null, shift_id: int|null}>
     */
    private function homerooms(): array
    {
        $classes = ClassSchool::query()->with(['medium', 'streams', 'shifts'])->get()->keyBy('id');
        $rooms = ClassSection::query()->with('section')->get()
            ->filter(fn (ClassSection $room) => $classes->has($room->class_id))
            ->mapWithKeys(function (ClassSection $room) use ($classes): array {
                $class = $classes[$room->class_id];

                return [(int) $room->id => [
                    'id' => (int) $room->id,
                    'name' => trim(preg_replace('/\s+/', ' ', $class->name.' '.($room->section?->name ?? '')) ?? ''),
                    'detail' => array_values(array_filter([$class->medium?->name, $class->streams?->name, $class->shifts?->title], fn ($part) => (string) $part !== '')),
                    'grade_level_id' => $class->grade_level_id !== null ? (int) $class->grade_level_id : null,
                    'section_id' => (int) $room->section_id,
                    'medium_id' => $class->medium_id !== null ? (int) $class->medium_id : null,
                    'stream_id' => $class->stream_id !== null ? (int) $class->stream_id : null,
                    'shift_id' => $class->shift_id !== null ? (int) $class->shift_id : null,
                ]];
            });
        $shared = $rooms->countBy(fn (array $room) => mb_strtolower($room['name']))->filter(fn (int $count) => $count > 1);
        $rooms = $rooms->map(function (array $room) use ($shared): array {
            if ($shared->has(mb_strtolower($room['name'])) && $room['detail'] !== []) {
                $room['name'] .= ' ('.implode(', ', $room['detail']).')';
            }
            unset($room['detail']);

            return $room;
        });
        // Still the same (identical classes): number them so no label is ambiguous.
        $still = $rooms->countBy(fn (array $room) => mb_strtolower($room['name']))->filter(fn (int $count) => $count > 1);

        return $rooms->map(function (array $room) use ($still): array {
            if ($still->has(mb_strtolower($room['name']))) {
                $room['name'] .= ' #'.$room['id'];
            }

            return $room;
        })->all();
    }

    /** @return array<int, int|null> grade level id => the next one up, or null for the last */
    private function nextGrades(): array
    {
        $grades = GradeLevel::query()->orderBy('sort_order')->pluck('id')->map(fn ($id) => (int) $id)->values();

        return $grades->mapWithKeys(fn (int $id, int $index) => [$id => $grades[$index + 1] ?? null])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $homerooms
     * @param  Collection<int, mixed>  $placed
     * @param  Collection<int, mixed>  $leaving
     */
    private function fingerprint(SessionYear $from, SessionYear $to, array $homerooms, Collection $placed, Collection $leaving): string
    {
        $rows = [];
        foreach ($homerooms as $room) {
            foreach ($room['learners'] as $row) {
                $rows[] = [$row['id'], $room['id'], $row['outcome'], $row['target'], $row['already']];
            }
        }

        return hash('sha256', json_encode([$from->id, $to->id, $rows, $placed->sortKeys()->all(), $leaving->keys()->sort()->values()->all()]));
    }
}
