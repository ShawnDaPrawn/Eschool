<?php

declare(strict_types=1);

use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

Model::unguarded(function () use ($transitionRun) {
    app(CurrentSchool::class)->run(1, function () use ($transitionRun) {
        $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
        $year = SessionYear::where('default', 1)->firstOrFail();
        $room = ClassSection::with('class', 'section')->whereHas('class', fn ($q) => $q->where('name', 'Grade 5'))->firstOrFail();
        $grade = GradeLevel::where('code', '5')->firstOrFail();
        ClassSchool::whereKey($room->class_id)->update(['grade_level_id' => $grade->id]);
        $subject = Subject::firstOrCreate(['name' => 'Transition rehearsal subject'], ['bg_color' => '#245A7A', 'type' => 'Theory', 'code' => 'TRANSITION-REHEARSAL']);
        foreach (['Transition group A', 'Transition group B'] as $name) {
            if (! TeachingGroup::where('name', $name)->where('session_year_id', $year->id)->exists()) {
                app(TeachingGroups::class)->save($admin, ['name' => $name, 'subject_id' => $subject->id, 'grade_level_id' => $grade->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id, 'active' => true]);
            }
        }
        foreach ([1024, 1280] as $width) {
            $user = User::firstOrCreate(['email' => 'transition'.$width.'.'.$transitionRun.'.rehearsal@demo.navigateed.test'], ['first_name' => 'Transition'.$width.$transitionRun, 'last_name' => 'Rehearsal', 'password' => Hash::make('NavigateDemo2026'), 'status' => 0]);
            $user->assignRole('Student');
            Students::firstOrCreate(['user_id' => $user->id], ['class_id' => $room->class_id, 'class_section_id' => $room->id, 'category_id' => Students::whereNotNull('category_id')->value('category_id'), 'admission_no' => 'TRANSITION-'.$width.'-'.$transitionRun, 'roll_number' => $width, 'admission_date' => $year->start_date]);
        }
    });
});
