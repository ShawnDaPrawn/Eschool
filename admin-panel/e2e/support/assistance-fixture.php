<?php

declare(strict_types=1);

use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
$permitted = require __DIR__.'/admissions-environment.php';
if (! $permitted(app()->environment(), $database, filter_var(env('CI', false), FILTER_VALIDATE_BOOLEAN))) {
    throw new RuntimeException('School assistance fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $parent = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
    $learner = Students::where('admission_no', 'DEMO-A-0001')->firstOrFail();
    app(ModuleGate::class)->change('ask_school', true, $admin);
    $parent->forceFill(['email_verified_at' => now(), 'password_change_required' => false])->save();
    Enrolment::updateOrCreate(['learner_id' => $learner->id, 'session_year_id' => schoolSetting('session_year')], [
        'class_section_id' => $learner->class_section_id, 'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->endOfYear()->toDateString(),
    ]);
    GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
    app(ApprovalService::class)->decide($parent, $learner, 'approve', config('identity.consent_version'));
    $text = 'Synthetic private school-office question '.Str::random(8);
    $thread = app(SchoolSupportThreads::class)->open($parent, (int) $learner->id, ['operation_id' => (string) Str::uuid(), 'text' => $text, 'language' => 'en']);

    return ['thread' => $thread['id'], 'text' => $text];
});
