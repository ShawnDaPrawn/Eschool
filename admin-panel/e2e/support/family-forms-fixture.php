<?php

declare(strict_types=1);

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Family form fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $parent = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
    $learner = Students::where('admission_no', 'DEMO-A-0001')->firstOrFail();
    app(ModuleGate::class)->change('groups', true, $admin);
    app(ModuleGate::class)->change('family_forms', true, $admin);
    $parent->forceFill(['email_verified_at' => now()])->save();
    Enrolment::updateOrCreate(['learner_id' => $learner->id, 'session_year_id' => schoolSetting('session_year')], ['class_section_id' => $learner->class_section_id, 'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString()]);
    GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
    app(ApprovalService::class)->decide($parent, $learner, 'approve', config('identity.consent_version'));
    $group = ActivityGroup::create(['session_year_id' => schoolSetting('session_year'), 'name' => 'Synthetic consent '.bin2hex(random_bytes(4)), 'discipline' => 'Culture', 'season' => now()->year, 'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString(), 'active' => true]);
    ActivityMembership::create(['activity_group_id' => $group->id, 'learner_id' => $learner->id, 'role' => 'member', 'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString(), 'active' => true, 'reason' => 'Synthetic browser fixture', 'revision' => 1]);

    $parent->tokens()->where('name', 'synthetic-family-forms-browser')->delete();
    $token = $parent->createToken('synthetic-family-forms-browser', ['portal:parent'])->plainTextToken;

    return ['token' => $token, 'guardian_id' => (int) $parent->id, 'group' => (int) $group->id, 'learner' => (int) $learner->id, 'name' => $learner->user->full_name, 'guardian' => $parent->full_name,
        'starts' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i'), 'ends' => now()->addDays(2)->setTime(12, 0)->format('Y-m-d\TH:i'), 'deadline' => now()->addDay()->setTime(12, 0)->format('Y-m-d\TH:i')];
});
