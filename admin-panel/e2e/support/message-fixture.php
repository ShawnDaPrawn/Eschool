<?php

declare(strict_types=1);

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\ParentPolicyService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

// Synthetic, dedicated author and inactive learner; no real deliveries or activation.
Model::unguarded(function () {
    app(CurrentSchool::class)->run(1, function () {
        $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
        $author = User::firstOrCreate(['email' => 'message.author@demo.navigateed.test'], ['first_name' => 'Message', 'last_name' => 'Rehearsal Teacher', 'password' => Hash::make('NavigateDemo2026'), 'status' => 1, 'email_verified_at' => now()]);
        $author->assignRole('Teacher');
        ParentPolicyService::acceptCurrent($author);
        $teacher = Teacher::firstOrCreate(['user_id' => $author->id], ['qualification' => 'Synthetic test teacher']);
        $year = SessionYear::where('default', 1)->firstOrFail();
        $grade = GradeLevel::where('code', '5')->firstOrFail();
        $room = ClassSection::whereHas('class', fn ($q) => $q->where('name', 'Grade 5'))->with('class')->firstOrFail();
        $room->class->forceFill(['grade_level_id' => $grade->id])->save();
        $subject = Subject::firstOrCreate(['name' => 'Message rehearsal subject'], ['type' => 'Theory', 'code' => 'COMMS-DEMO', 'medium_id' => $room->class->medium_id]);
        $group = TeachingGroup::where('name', 'Message rehearsal group')->where('session_year_id', $year->id)->first();
        $group ??= app(TeachingGroups::class)->save($admin, ['name' => 'Message rehearsal group', 'subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id, 'active' => true]);
        $dated = ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic communication browser rehearsal'];
        if (! TeachingAllocation::where('teaching_group_id', $group->id)->where('teacher_id', $teacher->id)->exists()) {
            app(TeachingGroups::class)->dated($admin, 'teachers', $dated + ['teacher_id' => $teacher->id], $group->id);
        }
        $user = User::firstOrCreate(['email' => 'message.learner@demo.navigateed.test'], ['first_name' => 'Message', 'last_name' => 'Inactive Learner', 'password' => Hash::make('NavigateDemo2026'), 'status' => 0]);
        $user->assignRole('Student');
        $learner = Students::firstOrCreate(['user_id' => $user->id], ['class_id' => $room->class_id, 'class_section_id' => $room->id, 'category_id' => Students::whereNotNull('category_id')->value('category_id'), 'admission_no' => 'MESSAGE-DEMO', 'roll_number' => 998, 'admission_date' => $year->start_date]);
        Enrolment::firstOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id], $dated);
        if (! TeachingGroupMembership::where('teaching_group_id', $group->id)->where('learner_id', $learner->id)->exists()) {
            app(TeachingGroups::class)->dated($admin, 'memberships', $dated + ['learner_id' => $learner->id, 'mark_rule' => 'catch_up'], $group->id);
        }
        $parent = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
        GuardianLink::firstOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $parent->id], ['status' => 'active', 'can_approve' => false]);
    });
});
