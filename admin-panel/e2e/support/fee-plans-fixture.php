<?php

declare(strict_types=1);

use App\Models\Ai\PlatformAiCostGrant;
use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Fee-plan browser fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $names = ['Synthetic fee-plan browser year', 'Synthetic fee-plan browser next year'];
    if (getenv('FEE_PLAN_FIXTURE_ACTION') === 'cleanup') {
        // Hide only these synthetic years from later wizard choices. Keep their
        // plans, receipts and enrolments for evidence rather than deleting money.
        SessionYear::whereIn('name', $names)->where('default', 0)->delete();

        return ['active_fixture_years' => SessionYear::whereIn('name', $names)->count()];
    }
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    // The earlier AI-controls fixture borrows Finance as a platform operator.
    // Restore its school-only identity; money must continue to refuse support.
    $finance = User::where('email', 'finance.a@demo.navigateed.test')->firstOrFail();
    PlatformAiCostGrant::where('user_id', $finance->id)->update(['active' => false]);
    PlatformStaffIdentity::where('user_id', $finance->id)->update(['active' => false]);
    app(ModuleGate::class)->change('fees', true, $admin);
    $y = (int) now()->format('Y') + 2;
    $year = SessionYear::withTrashed()->firstOrCreate(['name' => $names[0]], ['start_date' => "$y-01-01", 'end_date' => "$y-12-31"]);
    $next = SessionYear::withTrashed()->firstOrCreate(['name' => $names[1]], ['start_date' => ($y + 1).'-01-01', 'end_date' => ($y + 1).'-12-31']);
    $year->restore();
    $next->restore();
    FeePlan::whereIn('session_year_id', [$year->id, $next->id])->update(['state' => 'archived']);
    $learner = Students::whereNotNull('class_section_id')->firstOrFail();
    Enrolment::updateOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id], ['class_section_id' => $learner->class_section_id,
        'starts_on' => "$y-01-01", 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic browser preview only']);
    $person = User::findOrFail($learner->user_id);

    return ['year' => $year->id, 'next' => $next->id, 'date' => "$y-06-15", 'learner' => trim($person->first_name.' '.$person->last_name)];
});
