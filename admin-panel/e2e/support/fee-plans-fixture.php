<?php

declare(strict_types=1);

use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
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
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('fees', true, $admin);
    $y = (int) now()->format('Y') + 2;
    $year = SessionYear::firstOrCreate(['name' => 'Synthetic fee-plan browser year'], ['start_date' => "$y-01-01", 'end_date' => "$y-12-31"]);
    $next = SessionYear::firstOrCreate(['name' => 'Synthetic fee-plan browser next year'], ['start_date' => ($y + 1).'-01-01', 'end_date' => ($y + 1).'-12-31']);
    FeePlan::whereIn('session_year_id', [$year->id, $next->id])->update(['state' => 'archived']);
    $learner = Students::whereNotNull('class_section_id')->firstOrFail();
    Enrolment::updateOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id], ['class_section_id' => $learner->class_section_id,
        'starts_on' => "$y-01-01", 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic browser preview only']);
    $person = User::findOrFail($learner->user_id);

    return ['year' => $year->id, 'next' => $next->id, 'date' => "$y-06-15", 'learner' => trim($person->first_name.' '.$person->last_name)];
});
