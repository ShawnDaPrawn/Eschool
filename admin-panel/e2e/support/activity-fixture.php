<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

// Own the complete synthetic learner and verified guardian consent, rather
// than selecting a legacy pupil another workflow may move or deactivate.
$recognition = require __DIR__.'/recognition-fixture.php';
app(CurrentSchool::class)->run(1, function (): void {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $admin->givePermissionTo('activity-groups-manage');
    app(ModuleGate::class)->change('groups', true, $admin);
});

return $recognition;
