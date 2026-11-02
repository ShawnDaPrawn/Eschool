<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only source extraction with fresh school management authority.
 * Learners are keyed on their LURITS number when one is recorded (Action Plan
 * 1.0d); the layout's learner_refs map stays the fallback for the others.
 * engineering/specs/academics/sasams-layouts.md.
 */
class SasamsExport
{
    /** The learner key of an exported row: its LURITS number, or the layout's map. */
    private const KEY = '_learner_key';

    public function generate(User $actor, array $input, ?string $template = null): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $layout = app(SasamsLayout::class);
        $p = $layout->validate($input);
        abort_unless($p['school_id'] === app(CurrentSchool::class)->id(), 404);
        $year = SessionYear::findOrFail($p['session_year_id']);
        if ($p['kind'] !== 'marks' && ($p['on_date'] < $year->start_date || $p['on_date'] > $year->end_date || $p['on_date'] > app(AcademicAuthority::class)->today())) {
            $layout->fail('Choose a non-future date inside the selected school year.');
        }
        $rows = match ($p['kind']) {
            'learners' => $this->learners($p), 'attendance' => $this->attendance($p), 'marks' => $this->marks($p),
        };
        if ($rows === [] || count($rows) > 10000) {
            $layout->fail('The export must contain between one and 10,000 records.');
        }
        if (collect($rows)->pluck('learner_ref')->duplicates()->isNotEmpty()) {
            $layout->fail('Two exported learners would share one learner reference. A mapped reference may not repeat a learner\'s LURITS number.');
        }
        $keys = array_count_values(array_column($rows, self::KEY));
        $content = $layout->render($p, $rows, $template);
        app(AcademicAuthority::class)->manage($actor);
        $manifest = ['schema_version' => 1, 'school_id' => $p['school_id'], 'session_year_id' => $p['session_year_id'], 'kind' => $p['kind'],
            'target_version' => $p['version'], 'source' => $p['source'], 'import_menu' => $p['import_menu'], 'actor_id' => (int) $actor->id,
            'generated_at' => now()->toISOString(), 'records' => count($rows),
            'learners_without_lurits' => $keys['learner_refs'] ?? 0, 'layout_sha256' => hash('sha256', json_encode($p, JSON_THROW_ON_ERROR)),
            'template_sha256' => $p['template_sha256'] ?? null, 'file_sha256' => hash('sha256', $content), 'target_import_verified' => false];
        $manifest['hmac_sha256'] = hash_hmac('sha256', json_encode($manifest, JSON_THROW_ON_ERROR), (string) config('app.key'));
        Audit::record('academics.sasams_exported', null, [], $manifest, $p['school_id']);

        return ['filename' => 'sasams-'.$p['kind'].'.'.$p['format'], 'content' => $content, 'manifest' => $manifest];
    }

    /**
     * For the export page: how many of the school's learners have no LURITS
     * number yet, so their rows need the layout's learner_refs map.
     *
     * @return array{learners: int, without_lurits: int}
     */
    public function readiness(User $actor): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $learners = Students::query()->count();
        $with = DB::table('learner_identifiers')->whereNotNull('lurits_hash')->whereIn('user_id', Students::query()->select('user_id'))->count();

        return ['learners' => $learners, 'without_lurits' => $learners - $with];
    }

    private function enrolments(array $p): Collection
    {
        $rows = Enrolment::with('learner.user', 'homeroom.class.gradeLevel', 'homeroom.section')->where('session_year_id', $p['session_year_id'])->onDate($p['on_date'])->orderBy('learner_id')->limit(10001)->get();
        if ($rows->count() > 10000 || $rows->pluck('learner_id')->duplicates()->isNotEmpty()) {
            app(SasamsLayout::class)->fail('Resolve duplicate enrolments or narrow the export before continuing.');
        }

        return $rows;
    }

    /** @param array<int, string|null> $lurits LURITS numbers by learner id, read for this school's learners only */
    private function identity(array $p, Students $learner, array $lurits): array
    {
        $number = (string) $learner->student_number;
        $ref = $lurits[(int) $learner->id] ?? null;
        $key = 'lurits';
        if ($ref === null) {
            // No LURITS number yet: the operator's confirmed map, as before 1.0d.
            $ref = $number === '' ? null : ($p['learner_refs'][$number] ?? null);
            $key = 'learner_refs';
        }
        if ($ref === null) {
            app(SasamsLayout::class)->fail('Record a LURITS number for every exported learner, or map the student number of each learner without one to its confirmed target learner reference.');
        }

        return [self::KEY => $key, 'learner_ref' => $ref, 'student_number' => $number, 'admission_no' => $learner->admission_no,
            'first_name' => $learner->user?->first_name, 'last_name' => $learner->user?->last_name,
            'dob' => $learner->user?->dob, 'gender' => $learner->user?->gender];
    }

    private function learners(array $p): array
    {
        $enrolments = $this->enrolments($p);
        $lurits = app(LuritsNumbers::class)->forLearners($enrolments->pluck('learner')->filter());

        return $enrolments->map(function ($entry) use ($p, $lurits) {
            return $this->identity($p, $entry->learner, $lurits) + ['grade' => $entry->homeroom?->class?->gradeLevel?->code,
                'homeroom' => trim(($entry->homeroom?->class?->name ?? '').' '.($entry->homeroom?->section?->name ?? '')),
                'starts_on' => $entry->starts_on->toDateString(), 'ends_on' => $entry->ends_on?->toDateString() ?? ''];
        })->all();
    }

    private function attendance(array $p): array
    {
        $enrolments = $this->enrolments($p)->keyBy('learner_id');
        $entries = AttendanceEntry::with('register')->whereIn('learner_id', $enrolments->keys())->whereHas('register', fn ($q) => $q->where('session_year_id', $p['session_year_id'])->whereDate('on_date', $p['on_date'])->whereNull('timetable_lesson_id'))->limit(10001)->get()->groupBy('learner_id');
        $lurits = app(LuritsNumbers::class)->forLearners($enrolments->pluck('learner')->filter());
        $rows = [];
        foreach ($enrolments as $entry) {
            $found = $entries->get($entry->learner_id, collect());
            if ($found->count() !== 1 || (int) $found->sole()->register->class_section_id !== (int) $entry->class_section_id) {
                app(SasamsLayout::class)->fail('Every enrolled learner needs exactly one daily homeroom entry for the dated enrolment. Period entries are excluded.');
            }
            $code = $p['attendance_codes'][$found->sole()->state] ?? null;
            if ($code === null) {
                app(SasamsLayout::class)->fail('Map every attendance state to an explicit target code.');
            }
            $rows[] = $this->identity($p, $entry->learner, $lurits) + ['on_date' => $p['on_date'], 'attendance_code' => $code];
        }

        return $rows;
    }

    private function marks(array $p): array
    {
        $group = TeachingGroup::where('session_year_id', $p['session_year_id'])->findOrFail($p['group_id']);
        $definitions = app(MarkDefinitions::class);
        $task = $definitions->task($group, $p['task_id']);
        $plan = $task->assessment->curriculum;
        abort_unless((int) $plan->session_year_id === $p['session_year_id'] && (int) $plan->subject_id === (int) $group->subject_id && (int) $plan->grade_level_id === (int) $group->grade_level_id && $task->assessment->state === 'published', 422);
        $canonical = $definitions->canonical($task);
        $date = $task->due_on?->toDateString() ?? $task->assessment->term->end_date->toDateString();
        $ids = TeachingGroupMembership::where('teaching_group_id', $group->id)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->pluck('learner_id');
        $ids = Enrolment::where('session_year_id', $p['session_year_id'])->onDate($date)->whereIn('learner_id', $ids)->pluck('learner_id');
        $learners = Students::with('user')->whereIn('id', $ids)->orderBy('id')->limit(10001)->get();
        $marks = AssessmentMark::where('assessment_task_id', $canonical->id)->whereIn('learner_id', $learners->pluck('id'))->get()->keyBy('learner_id');
        $lurits = app(LuritsNumbers::class)->forLearners($learners);

        return $learners->map(function ($learner) use ($p, $marks, $task, $lurits) {
            $mark = $marks->get($learner->id);
            if ($mark === null || $mark->disposition === 'missing' || ($mark->disposition === 'recorded' && $mark->mark_units === null)) {
                app(SasamsLayout::class)->fail('Finish or explicitly resolve every task mark before exporting; missing marks are never zeroed.');
            }
            $score = $mark->disposition === 'recorded' ? AcademicNumbers::decimal($mark->mark_units) : ($p['mark_codes'][$mark->disposition] ?? null);
            if ($score === null) {
                app(SasamsLayout::class)->fail('Map every non-recorded mark disposition explicitly for the target import.');
            }

            return $this->identity($p, $learner, $lurits) + ['subject_code' => $p['subject_code'], 'task_ref' => $p['task_ref'], 'score' => $score,
                'maximum' => AcademicNumbers::decimal($task->max_mark_units), 'mark_code' => $p['mark_codes'][$mark->disposition] ?? null];
        })->all();
    }
}
