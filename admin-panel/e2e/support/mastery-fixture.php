<?php

declare(strict_types=1);

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\MarkRecording;
use App\Services\Academics\TeachingGroups;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

// Dedicated synthetic subject: no real learner data, official progression or provider calls.
Model::unguarded(function () {
    app(CurrentSchool::class)->run(1, function () {
        SchoolModule::updateOrCreate(['module' => 'mastery'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
        $actor = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
        $teacher = Teacher::where('user_id', $actor->id)->firstOrFail();
        $year = SessionYear::where('default', 1)->firstOrFail();
        $grade = GradeLevel::where('code', '5')->firstOrFail();
        $room = ClassSection::whereHas('class', fn ($q) => $q->where('name', 'Grade 5'))->with('class')->firstOrFail();
        $room->class->forceFill(['grade_level_id' => $grade->id])->save();
        $subject = Subject::firstOrCreate(['name' => 'Mastery browser rehearsal'], ['type' => 'Theory', 'code' => 'MASTERY-DEMO', 'medium_id' => $room->class->medium_id]);
        $group = TeachingGroup::where('name', 'Mastery browser group')->where('session_year_id', $year->id)->first();
        if ($group !== null) {
            return;
        }
        $term = Term::where('session_year_id', $year->id)->forGrade((int) $grade->sort_order)->whereDate('start_date', '<=', now())->whereDate('end_date', '>=', now())->first()
            ?? Term::where('session_year_id', $year->id)->forGrade((int) $grade->sort_order)->firstOrFail();
        $keys = [(string) Str::uuid(), (string) Str::uuid(), (string) Str::uuid()];
        $topics = [];
        foreach (['Mastery browser foundation', 'Mastery browser fractions', 'Mastery browser decimals'] as $i => $name) {
            $topics[] = ['key' => $keys[$i], 'name' => $name, 'term_id' => $term->id, 'sort_order' => $i];
        }
        $plan = app(CurriculumPlans::class)->save($admin, ['subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'session_year_id' => $year->id,
            'title' => 'Synthetic mastery browser source', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => $topics,
            'assessments' => [['term_id' => $term->id, 'components' => ['sba' => 10000], 'tasks' => [['name' => 'Synthetic browser questions', 'type' => 'test', 'component' => 'sba',
                'annual_component' => 'sba', 'annual_weight_bp' => 10000, 'weight_bp' => 10000, 'max_mark' => '20.00', 'due_on' => $term->end_date->toDateString(), 'topic_keys' => array_slice($keys, 1),
                'questions' => [['number' => '1', 'max_mark' => '10.00', 'topic_keys' => [$keys[1]]], ['number' => '2', 'max_mark' => '10.00', 'topic_keys' => [$keys[2]]]]]]]]]);
        $plan = app(CurriculumPlans::class)->publish($admin, $plan->id, $plan->assessments->sole()->id, 1);
        $group = app(TeachingGroups::class)->save($admin, ['name' => 'Mastery browser group', 'subject_id' => $subject->id, 'grade_level_id' => $grade->id,
            'session_year_id' => $year->id, 'class_section_id' => $room->id, 'active' => true]);
        $dated = ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic mastery browser rehearsal'];
        if (! TeachingAllocation::where('teaching_group_id', $group->id)->where('teacher_id', $teacher->id)->exists()) {
            app(TeachingGroups::class)->dated($admin, 'teachers', $dated + ['teacher_id' => $teacher->id], $group->id);
        }
        $learner = Students::where('class_section_id', $room->id)->firstOrFail();
        Enrolment::firstOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id], $dated);
        if (! TeachingGroupMembership::where('teaching_group_id', $group->id)->where('learner_id', $learner->id)->exists()) {
            app(TeachingGroups::class)->dated($admin, 'memberships', $dated + ['learner_id' => $learner->id, 'mark_rule' => 'catch_up'], $group->id);
        }
        $task = $plan->assessments->sole()->tasks->sole();
        $questions = $task->questions->pluck('id')->all();
        app(MarkRecording::class)->capture($actor, (int) $group->id, (int) $task->id, ['rows' => [[
            'learner_id' => $learner->id, 'expected_revision' => 0, 'disposition' => 'recorded', 'mark' => null,
            'question_marks' => [['question_id' => $questions[0], 'mark' => '0.00'], ['question_id' => $questions[1], 'mark' => '9.00']],
        ]]]);
        app(TopicGraph::class)->sync((int) $subject->id);
        app(MasteryRecompute::class)->group((int) $group->id);
    });
});
