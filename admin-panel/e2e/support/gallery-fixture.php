<?php

declare(strict_types=1);

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Gallery browser fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    foreach (['gallery', 'groups'] as $module) {
        app(ModuleGate::class)->change($module, true, $admin);
    }
    $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
    $year->update(['start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString()]);
    $group = ActivityGroup::firstOrCreate(['name' => 'Synthetic gallery group', 'session_year_id' => $year->id], ['discipline' => 'Culture', 'season' => 'Synthetic browser', 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'active' => true]);
    $teacher = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
    ActivityStaff::firstOrCreate(['activity_group_id' => $group->id, 'user_id' => $teacher->id], ['role' => 'coach', 'cascade' => false, 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'active' => true, 'reason' => 'Synthetic browser source', 'revision' => 1]);
    $learner = Students::whereHas('user', fn ($q) => $q->where('status', 1))->firstOrFail();
    User::whereKey($learner->user_id)->update(['handle' => 'gallery.browser']);
    ActivityMembership::firstOrCreate(['activity_group_id' => $group->id, 'learner_id' => $learner->id], ['role' => 'member', 'starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'active' => true, 'reason' => 'Synthetic browser source', 'revision' => 1]);

    return ['group' => (int) $group->id];
});
