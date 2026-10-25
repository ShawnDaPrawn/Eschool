<?php

declare(strict_types=1);

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\Intervention;
use App\Models\Growth\RiskCaseload;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Growth\Interventions;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;

require base_path('e2e/support/mastery-fixture.php');
app(CurrentSchool::class)->run(1, function () {
    SchoolModule::updateOrCreate(['module' => 'early_warning'], ['in_package' => true, 'enabled' => true]);
    app(ModuleGate::class)->forget();
    $principal = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $professional = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
    $year = SessionYear::where('default', 1)->firstOrFail();
    AcademicLeadership::firstOrCreate(['user_id' => $principal->id, 'kind' => 'principal', 'session_year_id' => $year->id],
        ['grade_level_id' => null, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic risk browser principal']);
    $professional->assignRole(SchoolRoles::COUNSELLOR);
    $group = TeachingGroup::where('name', 'Mastery browser group')->firstOrFail();
    $learner = TeachingGroupMembership::where('teaching_group_id', $group->id)->value('learner_id');
    $grant = RiskCaseload::where('learner_id', $learner)->where('professional_id', $professional->id)->where('session_year_id', $year->id)->first();
    app(Interventions::class)->assign($principal, (int) $learner, (int) $year->id, (int) $professional->id,
        ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'expected_revision' => $grant?->revision ?? 0]);
    $record = Intervention::where('learner_id', $learner)->where('owner_authority', 'professional')->first();
    if ($record === null) {
        app(Interventions::class)->create($professional, (int) $learner, (int) $year->id,
            ['owner_id' => $professional->id, 'owner_authority' => 'professional', 'action' => 'Synthetic private professional follow-up', 'started_on' => now()->toDateString(), 'review_on' => now()->addDays(2)->toDateString()]);
    } else {
        app(Interventions::class)->update($professional, (int) $record->id, ['expected_revision' => $record->revision, 'action' => 'Synthetic private professional follow-up', 'outcome' => null, 'review_on' => now()->addDays(2)->toDateString(), 'state' => 'active']);
    }
});
