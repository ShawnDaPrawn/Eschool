<?php

declare(strict_types=1);
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\ReportTemplate;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\Reports\ReportDesigns;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Synthetic only: loaded by Playwright's disposable demo database, never production.
Model::unguarded(function (): void {
    app(CurrentSchool::class)->run(1, function (): void {
        DB::transaction(function (): void {
            School::whereKey(1)->lockForUpdate()->firstOrFail();
            $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
            $teacherUser = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
            $teacher = Teacher::where('user_id', $teacherUser->id)->firstOrFail();
            $year = SessionYear::where('name', '2026')->firstOrFail();
            $grade = GradeLevel::where('code', '5')->firstOrFail();
            $room = ClassSection::whereHas('class', fn ($q) => $q->where('name', 'Grade 5'))->with('class')->firstOrFail();
            $room->class->forceFill(['grade_level_id' => $grade->id])->save();
            $term = Term::where('session_year_id', $year->id)->forGrade($grade->sort_order)->orderBy('start_date')->firstOrFail();
            $subject = Subject::firstOrCreate(['name' => 'Report rehearsal subject'], ['type' => 'Theory', 'bg_color' => '#5c6bc0', 'code' => 'REPORT-DEMO', 'medium_id' => $room->class->medium_id]);
            $plan = CurriculumPlan::where('subject_id', $subject->id)->where('grade_level_id', $grade->id)->where('session_year_id', $year->id)->first();
            if ($plan === null) {
                $plan = app(CurriculumPlans::class)->save($admin, ['subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'session_year_id' => $year->id,
                    'title' => 'Report rehearsal plan', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => [],
                    'assessments' => [['term_id' => $term->id, 'components' => ['sba' => 10000], 'tasks' => [['name' => 'Report rehearsal task', 'type' => 'test', 'component' => 'sba', 'annual_component' => 'sba',
                        'annual_weight_bp' => 10000, 'weight_bp' => 10000, 'max_mark' => '20.00', 'due_on' => $term->end_date->toDateString(), 'topic_keys' => [], 'questions' => []]]]]]);
                app(CurriculumPlans::class)->publish($admin, $plan->id, $plan->assessments->sole()->id, 1);
            }
            $group = TeachingGroup::where('name', 'Report rehearsal group')->first();
            if ($group === null) {
                $group = app(TeachingGroups::class)->save($admin, ['name' => 'Report rehearsal group', 'subject_id' => $subject->id, 'grade_level_id' => $grade->id,
                    'session_year_id' => $year->id, 'class_section_id' => $room->id, 'active' => true]);
            }
            $dated = ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic report browser rehearsal'];
            if (! TeachingAllocation::where('teaching_group_id', $group->id)->where('teacher_id', $teacher->id)->exists()) {
                app(TeachingGroups::class)->dated($admin, 'teachers', $dated + ['teacher_id' => $teacher->id], $group->id);
            }
            $learnerUser = User::firstOrCreate(['email' => 'report.rehearsal@demo.navigateed.test'], ['first_name' => 'Report', 'last_name' => 'Rehearsal Learner',
                'password' => Hash::make('NavigateDemo2026'), 'status' => 0]);
            $learnerUser->assignRole('Student');
            // Physical reporting never activates this synthetic, unapproved account.
            $learner = Students::firstOrCreate(['user_id' => $learnerUser->id], ['class_id' => $room->class_id, 'class_section_id' => $room->id,
                'category_id' => Students::whereNotNull('category_id')->value('category_id'), 'admission_no' => 'REPORT-DEMO', 'roll_number' => 999, 'admission_date' => $year->start_date]);
            foreach ([$learner] as $learner) {
                Enrolment::firstOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id], $dated);
                if (! TeachingGroupMembership::where('teaching_group_id', $group->id)->where('learner_id', $learner->id)->exists()) {
                    app(TeachingGroups::class)->dated($admin, 'memberships', $dated + ['learner_id' => $learner->id, 'mark_rule' => 'catch_up'], $group->id);
                }
            }
            foreach ([['grade_head', $grade->id], ['principal', null]] as [$kind, $gradeId]) {
                if (! AcademicLeadership::where('user_id', $admin->id)->where('kind', $kind)->where('grade_level_id', $gradeId)->where('session_year_id', $year->id)->exists()) {
                    app(TeachingGroups::class)->dated($admin, 'leadership', $dated + ['user_id' => $admin->id, 'session_year_id' => $year->id, 'kind' => $kind, 'grade_level_id' => $gradeId]);
                }
            }
            if (! ReportTemplate::where('name', 'Browser report design')->exists()) {
                app(ReportDesigns::class)->save($admin, ['name' => 'Browser report design', 'design' => ['heading' => 'Browser school report', 'introduction' => null, 'footer' => null,
                    'sections' => ['subjects', 'attendance', 'subject_comments', 'homeroom_comment'], 'show_crest' => false]]);
            }
        });
    });

});
