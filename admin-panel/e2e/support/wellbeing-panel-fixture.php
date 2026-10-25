<?php

declare(strict_types=1);

use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Models\Wellbeing\Appointment;
use App\Models\Wellbeing\CareCase;
use App\Models\Wellbeing\CaseAssignment;
use App\Models\Wellbeing\CaseEntry;
use App\Models\Wellbeing\Category;
use App\Models\Wellbeing\IncidentReport;
use App\Models\Wellbeing\TeamAssignment;
use App\Services\ParentPolicyService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\Casework;
use App\Services\Wellbeing\IncidentReporting;
use App\Services\Wellbeing\WellbeingSettings;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return app(CurrentSchool::class)->run(1, function () {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $admin->givePermissionTo('wellbeing-configure');
    app(ModuleGate::class)->change('wellbeing', true, $admin);
    DB::table('school_modules')->where('school_id', 1)->where('module', 'wellbeing')->update(['read_only' => false]);
    app(ModuleGate::class)->forget();
    $professionals = [];
    foreach (['assigned', 'unassigned'] as $name) {
        $user = User::firstOrCreate(['email' => 'clinic-'.$name.'@demo.navigateed.test'], ['first_name' => 'Clinic', 'last_name' => ucfirst($name), 'password' => Hash::make(DemoSchoolSeeder::PASSWORD), 'status' => 1, 'password_change_required' => false]);
        SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $user->id, 'role' => 'Counsellor'], ['status' => 'active', 'starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->addYear()->toDateString()]);
        $user->assignRole('Counsellor');
        ParentPolicyService::acceptCurrent($user);
        $professionals[$name] = $user;
    }
    $settings = app(WellbeingSettings::class);
    $category = Category::where('name', 'Clinical browser concerns')->first();
    if ($category === null) {
        $saved = $settings->saveCategory($admin, null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Clinical browser concerns', 'emergency_guidance' => 'Speak to a trusted adult immediately if unsafe.', 'active' => true]);
        $category = Category::findOrFail($saved['id']);
    }
    $grant = fn (User $user) => ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'user_id' => $user->id, 'starts_on' => now()->toDateString(), 'ends_on' => now()->addYear()->toDateString(), 'active' => true, 'reason' => 'Explicit synthetic browser grant'];
    if (! TeamAssignment::where('category_id', $category->id)->where('user_id', $professionals['assigned']->id)->where('active', true)->whereDate('starts_on', '<=', now()->toDateString())->whereDate('ends_on', '>=', now()->toDateString())->exists()) {
        $settings->assignTeam($admin, $category->id, null, $grant($professionals['assigned']));
    }
    $learner = Students::whereHas('user', fn ($q) => $q->where('status', 1))->orderBy('id')->firstOrFail();
    Enrolment::firstOrCreate(['learner_id' => $learner->id, 'session_year_id' => (int) schoolSetting('session_year'), 'class_section_id' => $learner->class_section_id], ['starts_on' => now()->startOfYear()->toDateString(), 'ends_on' => now()->addYear()->toDateString(), 'reason' => 'Synthetic clinical browser enrolment']);
    $case = CareCase::where('learner_id', $learner->id)->first();
    if ($case === null) {
        $created = $settings->openCase($admin, ['operation_id' => (string) Str::uuid(), 'learner_id' => $learner->id]);
        $case = CareCase::findOrFail($created['id']);
    }
    if (! CaseAssignment::where('support_case_id', $case->id)->where('user_id', $professionals['assigned']->id)->where('active', true)->whereDate('starts_on', '<=', now()->toDateString())->whereDate('ends_on', '>=', now()->toDateString())->exists()) {
        $settings->assignCase($admin, $case->id, null, $grant($professionals['assigned']));
    }
    foreach (Appointment::where('support_case_id', $case->id)->where('professional_user_id', $professionals['assigned']->id)->where('status', '!=', 'cancelled')->get() as $previous) {
        if (str_starts_with($previous->location, 'Private clinical room ')) {
            app(Casework::class)->appointment($professionals['assigned'], $case->id, $previous->id, ['operation_id' => (string) Str::uuid(), 'revision' => $previous->revision,
                'professional_user_id' => $professionals['assigned']->id, 'starts_at' => $previous->starts_at->toISOString(), 'ends_at' => $previous->ends_at->toISOString(),
                'location' => $previous->location, 'notes' => $previous->notes, 'status' => 'cancelled']);
        }
    }
    if (! CaseEntry::where('support_case_id', $case->id)->exists()) {
        app(Casework::class)->entry($professionals['assigned'], $case->id, ['operation_id' => (string) Str::uuid(), 'revision' => $case->revision, 'kind' => 'note', 'body' => 'Synthetic confidential clinical baseline', 'happened_at' => now()->subMinute()->toISOString()]);
    }
    $report = IncidentReport::where('category_id', $category->id)->first();
    if ($report === null) {
        app(IncidentReporting::class)->submit(['secret' => bin2hex(random_bytes(32)), 'category_id' => $category->id, 'body' => 'Synthetic confidential reported concern', 'urgency' => 'urgent', 'anonymous' => true]);
        $report = IncidentReport::where('category_id', $category->id)->firstOrFail();
    }

    return ['case' => (int) $case->id, 'report' => (int) $report->id, 'category' => (int) $category->id,
        'assigned' => $professionals['assigned']->email, 'unassigned' => $professionals['unassigned']->email];
});
