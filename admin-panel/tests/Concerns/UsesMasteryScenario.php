<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Growth\MasteryTopic;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\MarkReleases;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\MasteryService;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Str;

trait UsesMasteryScenario
{
    use UsesTwoSchools;

    protected int $group;

    protected int $task;

    protected int $subject;

    protected int $year;

    protected int $term;

    protected array $questions;

    protected array $keys;

    protected array $nodes;

    protected function setUpMasteryScenario(): void
    {
        $this->setUpTwoSchools();
        SchoolModule::updateOrCreate(['module' => 'mastery'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->subject = $this->schoolA->ids['maths'];
        $this->year = $this->schoolA->ids['session_year'];
        $this->term = $this->schoolA->ids['term'];
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->term)->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic mastery fixture']);
        $this->keys = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
        $topics = [];
        foreach (['Number foundation', 'Fractions', 'Decimals'] as $i => $name) {
            $topics[] = ['key' => $this->keys[$i], 'name' => $name, 'term_id' => $this->term, 'sort_order' => $i];
        }
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), ['subject_id' => $this->subject, 'grade_level_id' => $grade, 'session_year_id' => $this->year,
            'title' => 'Synthetic maths', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => $topics,
            'assessments' => [['term_id' => $this->term, 'components' => ['sba' => 10000], 'tasks' => [['name' => 'Synthetic task', 'type' => 'test', 'component' => 'sba',
                'annual_component' => 'sba', 'annual_weight_bp' => 10000, 'weight_bp' => 10000, 'max_mark' => '20.00', 'due_on' => '2026-02-10', 'topic_keys' => array_slice($this->keys, 1),
                'questions' => [['number' => '1', 'max_mark' => '10.00', 'topic_keys' => [$this->keys[1]]], ['number' => '2', 'max_mark' => '10.00', 'topic_keys' => [$this->keys[2]]]]]]]]]);
        $plan = app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->sole()->id, 1);
        $this->task = (int) $plan->assessments->sole()->tasks->sole()->id;
        $this->questions = $plan->assessments->sole()->tasks->sole()->questions->pluck('id')->all();
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Maths learning group', 'subject_id' => $this->subject, 'grade_level_id' => $grade,
            'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $this->group = (int) $group->id;
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id'),
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Allocated subject'], $this->group);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Enrolled subject'], $this->group);
        app(TopicGraph::class)->sync($this->subject);
        $this->nodes = MasteryTopic::query()->where('subject_id', $this->subject)->orderBy('id')->pluck('id', 'topic_key')->all();
    }

    protected function actor(string $name = 'tara'): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    protected function capture(string $first = '4.00', ?string $second = '9.00', int $revision = 0): void
    {
        app(MarkRecording::class)->capture($this->actor(), $this->group, $this->task, ['rows' => [[
            'learner_id' => $this->schoolA->ids['asha'], 'expected_revision' => $revision, 'disposition' => 'recorded', 'mark' => null,
            'question_marks' => [['question_id' => $this->questions[0], 'mark' => $first], ['question_id' => $this->questions[1], 'mark' => $second]],
        ]]]);
    }

    protected function graph(bool $enabled = true, int $revision = 0, ?array $links = null): array
    {
        return app(TopicGraph::class)->save($this->superAdmin(), $this->subject, ['expected_revision' => $revision, 'family_marks_enabled' => $enabled,
            'links' => $links ?? [['topic_id' => $this->nodes[$this->keys[1]], 'prerequisite_id' => $this->nodes[$this->keys[0]], 'source' => 'Synthetic published curriculum prerequisite']]]);
    }

    protected function masteryView(string $name = 'tara', ?int $learner = null, bool $staff = true): array
    {
        return app(MasteryService::class)->view($this->actor($name), $learner ?? $this->schoolA->ids['asha'], $this->subject, $staff ? $this->group : null);
    }

    protected function release(): void
    {
        $input = ['term_id' => $this->term, 'mode' => 'official', 'learner_ids' => [$this->schoolA->ids['asha']]];
        $preview = app(MarkReleases::class)->preview($this->superAdmin(), $this->group, $input);
        app(MarkReleases::class)->release($this->superAdmin(), $this->group, $input + ['fingerprint' => $preview['fingerprint']]);
    }
}
