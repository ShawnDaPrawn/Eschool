<?php

declare(strict_types=1);

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\Mediums;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;

Model::unguarded(function () {
    app(CurrentSchool::class)->run(1, function () {
        $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
        $teacher = Teacher::where('user_id', User::where('email', 'teacher.a@demo.navigateed.test')->value('id'))->firstOrFail();
        $year = SessionYear::where('default', 1)->firstOrFail();
        $grade = GradeLevel::where('code', '5')->firstOrFail();
        $subject = Subject::firstOrCreate(['name' => 'Content rehearsal subject'], ['code' => 'CONTENT', 'type' => 'Theory', 'image' => 'demo/subject.svg', 'medium_id' => Mediums::query()->firstOrFail()->id]);
        if ($subject->medium_id === null) {
            $subject->update(['medium_id' => Mediums::query()->firstOrFail()->id]);
        }
        $group = TeachingGroup::where('name', 'Content rehearsal group')->where('session_year_id', $year->id)->first();
        if ($group === null) {
            $group = app(TeachingGroups::class)->save($admin, ['name' => 'Content rehearsal group', 'subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'session_year_id' => $year->id, 'class_section_id' => null, 'active' => true]);
            app(TeachingGroups::class)->dated($admin, 'teachers', ['teacher_id' => $teacher->id, 'starts_on' => (string) $year->start_date, 'ends_on' => (string) $year->end_date, 'reason' => 'Synthetic content browser allocation'], $group->id);
        }
    });

});
