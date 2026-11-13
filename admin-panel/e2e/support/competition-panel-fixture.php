<?php

declare(strict_types=1);

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

// Reuse the complete synthetic learner/guardian-consent fixture, not a pupil
// whose placement another parallel workflow can change.
$recognition = require __DIR__.'/recognition-fixture.php';

// The foreign school has legitimate module authority; a cross-school record
// refusal must therefore exercise ownership rather than a disabled module.
app(CurrentSchool::class)->run(2, function (): void {
    $admin = User::where('email', 'admin.b@demo.navigateed.test')->firstOrFail();
    $admin->givePermissionTo('activity-groups-manage');
    app(ModuleGate::class)->change('groups', true, $admin);
});

return app(CurrentSchool::class)->run(1, function () use ($recognition): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $admin->givePermissionTo('activity-groups-manage');
    app(ModuleGate::class)->change('groups', true, $admin);
    $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
    $start = substr((string) $year->start_date, 0, 10);
    $end = substr((string) $year->end_date, 0, 10);
    $group = ActivityGroup::create(['session_year_id' => $year->id, 'name' => 'Synthetic competition group '.bin2hex(random_bytes(4)),
        'discipline' => 'School competitions', 'season' => $year->name, 'starts_on' => $start, 'ends_on' => $end, 'active' => true, 'revision' => 1]);
    ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $recognition['learner'], 'role' => 'member',
        'starts_on' => $start, 'ends_on' => $end, 'active' => true, 'revision' => 1]);

    return ['group' => (int) $group->id, 'learner' => $recognition['learner'], 'learner_name' => $recognition['name'],
        'today' => now('Africa/Johannesburg')->toDateString(), 'starts_on' => $start, 'ends_on' => $end] + $recognition;
});
