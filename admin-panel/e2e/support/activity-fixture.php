<?php

declare(strict_types=1);
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

// Synthetic demo fixture only: the normal module default stays off.
app(CurrentSchool::class)->run(1, function () {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $admin->givePermissionTo('activity-groups-manage');
    app(ModuleGate::class)->change('groups', true, $admin);
    $year = SessionYear::where('default', 1)->firstOrFail();
    foreach (Students::whereHas('user', fn ($q) => $q->where('status', 1))->get() as $learner) {
        Enrolment::firstOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id, 'class_section_id' => $learner->class_section_id], ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic activity browser enrolment']);
    }

});
