<?php

declare(strict_types=1);

use App\Models\Platform\School;
use App\Models\User;
use App\Models\Wellbeing\Category;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\WellbeingSettings;
use Illuminate\Support\Str;

// Existing synthetic demo account is required; never provision a real school.
return app(CurrentSchool::class)->run(1, function () {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $admin->givePermissionTo('wellbeing-configure');
    app(ModuleGate::class)->change('wellbeing', true, $admin);
    if (! Category::where('name', 'Browser safety concern')->exists()) {
        app(WellbeingSettings::class)->saveCategory($admin, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'name' => 'Browser safety concern', 'emergency_guidance' => 'Speak to a trusted adult now if unsafe.', 'active' => true]);
    }

    return ['slug' => School::findOrFail(1)->slug];
});
