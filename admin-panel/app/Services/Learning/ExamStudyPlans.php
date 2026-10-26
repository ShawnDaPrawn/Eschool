<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\ExamStudyPlan;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryService;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ExamStudyPlans
{
    public const VERSION = 'exam-study-1';

    public function create(User $actor, int $groupId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'exam_timetable_id' => ['required', 'integer', 'min:1'],
            'schedule_signature' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/'], 'paper_version_ids' => ['required', 'array', 'min:1', 'max:5'],
            'paper_version_ids.*' => ['required', 'uuid', 'distinct'], 'study_minutes' => ['required', 'integer', 'between:10,60']])->validate();
        sort($data['paper_version_ids']);
        $authorize = function () use ($actor, $groupId, $data) {
            app(ExamCoachAccess::class)->learnerGroup($actor, $groupId, true);
            $exam = app(ExamCoachDates::class)->row($actor, $groupId, (int) $data['exam_timetable_id']);
            abort_unless(hash_equals($data['schedule_signature'], app(ExamCoachDates::class)->signature($exam)), 409, 'The exam schedule changed. Refresh the study plan.');
            foreach ($data['paper_version_ids'] as $version) {
                app(PastPaperLibrary::class)->version($actor, $groupId, $version);
            }
        };

        return app(ExamCoachOperations::class)->run($actor, 'study.create', ['group_id' => $groupId] + $data, $authorize, function () use ($actor, $groupId, $data) {
            $access = app(ExamCoachAccess::class);
            $group = $access->learnerGroup($actor, $groupId, true);
            $learner = $access->learner($actor);
            $exam = app(ExamCoachDates::class)->row($actor, $groupId, (int) $data['exam_timetable_id']);
            $weights = [];
            foreach ($data['paper_version_ids'] as $version) {
                foreach (app(PastPaperLibrary::class)->version($actor, $groupId, $version)->definition['questions'] as $question) {
                    $ids = $question['topic_ids'];
                    foreach ($ids as $id) {
                        $share = intdiv($question['max_units'] * 2520, count($ids));
                        $weights[$id] = ($weights[$id] ?? 0) + $share;
                    }
                }
            }
            $levels = [];
            $mode = $this->masteryMode();
            if ($mode !== 'none') {
                $levels = array_column(app(MasteryService::class)->view($actor, (int) $learner->id, (int) $group->subject_id)['topics'], 'level', 'id');
            }
            foreach ($weights as $topic => &$weight) {
                $weight *= 5 - min(4, max(0, (int) ($levels[$topic] ?? 0)));
            }
            unset($weight);
            $today = app(AcademicAuthority::class)->today();
            $days = $this->schedule($today, substr((string) $exam->date, 0, 10), $weights, (int) $data['study_minutes']);
            abort_unless($mode === 'none' || $this->masteryMode() === $mode, 409, 'Mastery visibility changed. Refresh the study plan.');
            $row = ExamStudyPlan::create(['id' => (string) Str::uuid(), 'user_id' => $actor->id, 'learner_id' => $learner->id,
                'teaching_group_id' => $group->id, 'subject_id' => $group->subject_id, 'session_year_id' => $group->session_year_id,
                'exam_timetable_id' => $exam->id, 'schedule_signature' => $data['schedule_signature'],
                'definition' => ['version' => self::VERSION, 'mastery_mode' => $mode, 'paper_version_ids' => $data['paper_version_ids'], 'weights' => $weights, 'days' => $days]]);

            return ['id' => $row->id, 'kind' => 'study_plan', 'state' => 'created'];
        });
    }

    private function masteryMode(): string
    {
        $gate = app(ModuleGate::class);
        $gate->forget();

        return ! $gate->allows('mastery') ? 'none' : (app(TopicGraph::class)->settings()['family_marks_enabled'] ? 'family_marks' : 'practice');
    }

    /** Integer weighted fair scheduling; no learner rankings or mark writes. */
    public function schedule(string $today, string $examOn, array $weights, int $minutes): array
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $today, 'UTC');
        $exam = CarbonImmutable::createFromFormat('!Y-m-d', $examOn, 'UTC');
        abort_unless($start !== false && $exam !== false && $exam->isAfter($start) && $start->diffInDays($exam) <= 366
            && $minutes >= 10 && $minutes <= 60 && $weights !== [] && count($weights) <= 200, 422);
        foreach ($weights as $weight) {
            abort_unless(is_int($weight) && $weight > 0 && $weight <= 1000000000000, 422);
        }
        ksort($weights);
        $dates = [];
        for ($day = $exam->subDay(); ! $day->isBefore($start); $day = $day->subDay()) {
            if ($day->isWeekday()) {
                $dates[] = $day->toDateString();
            }
        }
        abort_if($dates === [], 422, 'No weekday remains before this exam. Open a paper for practice instead.');
        $debt = array_fill_keys(array_keys($weights), 0);
        $sum = array_sum($weights);
        $result = [];
        foreach (array_reverse($dates) as $date) {
            foreach ($weights as $topic => $weight) {
                $debt[$topic] += $weight;
            }
            arsort($debt, SORT_NUMERIC);
            $chosen = array_key_first($debt);
            $debt[$chosen] -= $sum;
            $result[] = ['date' => $date, 'topic_id' => (int) $chosen, 'minutes' => $minutes];
        }

        return $result;
    }

    public function read(User $actor, string $id): array
    {
        $school = app(CurrentSchool::class)->id();
        $access = app(ExamCoachAccess::class);
        $learner = $access->learner($actor);
        $meta = ExamStudyPlan::select('id', 'school_id', 'user_id', 'learner_id', 'teaching_group_id')->where('user_id', $actor->id)->where('learner_id', $learner->id)->findOrFail($id);
        $group = $access->learnerGroup($actor, (int) $meta->teaching_group_id);
        $row = ExamStudyPlan::findOrFail($id);
        $exam = app(ExamCoachDates::class)->row($actor, (int) $group->id, (int) $row->exam_timetable_id);
        abort_unless(hash_equals($row->schedule_signature, app(ExamCoachDates::class)->signature($exam)), 409, 'The exam schedule changed. Refresh the study plan.');
        $mode = $row->definition['mastery_mode'];
        abort_unless($mode === 'none' || $mode === $this->masteryMode(), 409, 'Mastery visibility changed. Refresh the study plan.');
        $papers = [];
        foreach ($row->definition['paper_version_ids'] as $version) {
            $paper = app(PastPaperLibrary::class)->data($actor, (int) $group->id, $version);
            $papers[] = array_intersect_key($paper, array_flip(['id', 'version_id', 'title', 'paper_year', 'paper_code']));
        }
        $topics = $access->topics($group);
        $days = array_map(function ($day) use ($topics) {
            abort_unless(isset($topics[$day['topic_id']]), 409, 'Published study topics changed.');

            return $day + ['topic_name' => $topics[$day['topic_id']]['name']];
        }, $row->definition['days']);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        $access->learnerGroup($actor, (int) $group->id);
        abort_unless($mode === 'none' || $mode === $this->masteryMode(), 409);

        return ['id' => $row->id, 'learner_id' => (int) $learner->id, 'group_id' => (int) $group->id,
            'subject_id' => (int) $group->subject_id, 'exam_on' => substr((string) $exam->date, 0, 10), 'papers' => $papers, 'days' => $days];
    }
}
