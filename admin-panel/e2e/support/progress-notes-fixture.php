<?php

declare(strict_types=1);

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\LearnerNote;
use App\Models\Growth\LearnerNoteAssignment;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Growth\LearnerNotes;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Carbon\Carbon;

require base_path('e2e/support/mastery-fixture.php');

app(CurrentSchool::class)->run(1, function () {
    $principal = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $professional = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
    $year = SessionYear::where('default', 1)->firstOrFail();
    AcademicLeadership::firstOrCreate(['user_id' => $principal->id, 'kind' => 'principal', 'session_year_id' => $year->id],
        ['grade_level_id' => null, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic browser principal']);
    $professional->assignRole(SchoolRoles::COUNSELLOR);
    $group = TeachingGroup::where('name', 'Mastery browser group')->firstOrFail();
    $learner = TeachingGroupMembership::where('teaching_group_id', $group->id)->value('learner_id');
    $assignment = LearnerNoteAssignment::where('learner_id', $learner)->where('staff_user_id', $professional->id)->first();
    app(LearnerNotes::class)->assign($principal, (int) $learner, (int) $professional->id,
        ['starts_on' => Carbon::parse($year->start_date)->toDateString(), 'ends_on' => Carbon::parse($year->end_date)->toDateString(), 'expected_revision' => $assignment?->revision ?? 0]);
    if (! LearnerNote::where('learner_id', $learner)->where('visibility', 'counselling')->exists()) {
        app(LearnerNotes::class)->create($professional, (int) $learner, null, (int) $year->id, 'counselling', 'Synthetic confidential counselling observation');
    }
});
