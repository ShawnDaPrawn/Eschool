<?php

declare(strict_types=1);

use App\Models\Academics\AcademicLeadership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

require base_path('e2e/support/mastery-fixture.php');
app(CurrentSchool::class)->run(1, function () {
    SchoolModule::updateOrCreate(['module' => 'discipline'], ['in_package' => true, 'enabled' => true]);
    app(ModuleGate::class)->forget();
    $principal = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $year = SessionYear::where('default', 1)->firstOrFail();
    foreach (\App\Models\Discipline\DetentionSession::where('status', 'planned')->get() as $session) {
        if ($session->location === 'Synthetic supervised school room') {
            app(\App\Services\Discipline\DisciplineDetentions::class)->cancel($principal, (int) $session->id, (int) $session->revision, 'Retained cancellation of a previous synthetic browser rehearsal');
        }
    }
    AcademicLeadership::firstOrCreate(['user_id' => $principal->id, 'kind' => 'principal', 'session_year_id' => $year->id],
        ['grade_level_id' => null, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic discipline browser principal']);
});
