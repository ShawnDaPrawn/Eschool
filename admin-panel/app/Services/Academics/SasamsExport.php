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
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Collection;

/** Read-only source extraction with fresh school management authority. */
class SasamsExport
{
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
        $content = $layout->render($p, $rows, $template);
        app(AcademicAuthority::class)->manage($actor);
        $manifest = ['schema_version' => 1, 'school_id' => $p['school_id'], 'session_year_id' => $p['session_year_id'], 'kind' => $p['kind'],
            'target_version' => $p['version'], 'source' => $p['source'], 'import_menu' => $p['import_menu'], 'actor_id' => (int) $actor->id,
            'generated_at' => now()->toISOString(), 'records' => count($rows), 'layout_sha256' => hash('sha256', json_encode($p, JSON_THROW_ON_ERROR)),
            'template_sha256' => $p['template_sha256'] ?? null, 'file_sha256' => hash('sha256', $content), 'target_import_verified' => false];
        $manifest['hmac_sha256'] = hash_hmac('sha256', json_encode($manifest, JSON_THROW_ON_ERROR), (string) config('app.key'));
        Audit::record('academics.sasams_exported', null, [], $manifest, $p['school_id']);

        return ['filename' => 'sasams-'.$p['kind'].'.'.$p['format'], 'content' => $content, 'manifest' => $manifest];
    }

    private function enrolments(array $p): Collection
    {
        $rows = Enrolment::with('learner.user', 'homeroom.class.gradeLevel', 'homeroom.section')->where('session_year_id', $p['session_year_id'])->onDate($p['on_date'])->orderBy('learner_id')->limit(10001)->get();
        if ($rows->count() > 10000 || $rows->pluck('learner_id')->duplicates()->isNotEmpty()) {
            app(SasamsLayout::class)->fail('Resolve duplicate enrolments or narrow the export before continuing.');
        }

        return $rows;
    }

    private function identity(array $p, Students $learner): array
    {
        $number = (string) $learner->student_number;
        $ref = $p['learner_refs'][$number] ?? null;
        if ($number === '' || $ref === null) {
            app(SasamsLayout::class)->fail('Map every exported student number to its confirmed target learner reference.');
        }

        return ['learner_ref' => $ref, 'student_number' => $number, 'admission_no' => $learner->admission_no,
            'first_name' => $learner->user?->first_name, 'last_name' => $learner->user?->last_name,
            'dob' => $learner->user?->dob, 'gender' => $learner->user?->gender];
    }

    private function learners(array $p): array
    {
        return $this->enrolments($p)->map(function ($entry) use ($p) {
            return $this->identity($p, $entry->learner) + ['grade' => $entry->homeroom?->class?->gradeLevel?->code,
                'homeroom' => trim(($entry->homeroom?->class?->name ?? '').' '.($entry->homeroom?->section?->name ?? '')),
                'starts_on' => $entry->starts_on->toDateString(), 'ends_on' => $entry->ends_on?->toDateString() ?? ''];
        })->all();
    }

    private function attendance(array $p): array
    {
        $enrolments = $this->enrolments($p)->keyBy('learner_id');
        $entries = AttendanceEntry::with('register')->whereIn('learner_id', $enrolments->keys())->whereHas('register', fn ($q) => $q->where('session_year_id', $p['session_year_id'])->whereDate('on_date', $p['on_date'])->whereNull('timetable_lesson_id'))->limit(10001)->get()->groupBy('learner_id');
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
            $rows[] = $this->identity($p, $entry->learner) + ['on_date' => $p['on_date'], 'attendance_code' => $code];
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

        return $learners->map(function ($learner) use ($p, $marks, $task) {
            $mark = $marks->get($learner->id);
            if ($mark === null || $mark->disposition === 'missing' || ($mark->disposition === 'recorded' && $mark->mark_units === null)) {
                app(SasamsLayout::class)->fail('Finish or explicitly resolve every task mark before exporting; missing marks are never zeroed.');
            }
            $score = $mark->disposition === 'recorded' ? AcademicNumbers::decimal($mark->mark_units) : ($p['mark_codes'][$mark->disposition] ?? null);
            if ($score === null) {
                app(SasamsLayout::class)->fail('Map every non-recorded mark disposition explicitly for the target import.');
            }

            return $this->identity($p, $learner) + ['subject_code' => $p['subject_code'], 'task_ref' => $p['task_ref'], 'score' => $score,
                'maximum' => AcademicNumbers::decimal($task->max_mark_units), 'mark_code' => $p['mark_codes'][$mark->disposition] ?? null];
        })->all();
    }
}
