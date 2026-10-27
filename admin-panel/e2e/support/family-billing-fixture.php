<?php

declare(strict_types=1);

use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\Money\FeePlans;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Database\Seeders\DemoSchoolSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Family billing fixtures require an isolated test database.');
}
if (getenv('BILLING_FIXTURE_ACTION') === 'stats') {
    $schoolId = (string) getenv('BILLING_FIXTURE_SCHOOL');
    abort_unless(ctype_digit($schoolId), 422);
    $school = School::where('slug', 'like', 'synthetic-billing-%')->findOrFail((int) $schoolId);

    return app(CurrentSchool::class)->run($school, function () use ($school): array {
        return ['invoices' => Invoice::whereNotNull('sealed_at')->count(), 'lines' => InvoiceLine::count(),
            'entries' => DB::table('ledger_entries')->where('school_id', $school->id)->count(),
            'receivable_cents' => (int) DB::table('ledger_accounts')->where('school_id', $school->id)->where('type', 'fee_receivable')->sum('balance_cents'),
            'income_cents' => (int) DB::table('ledger_accounts')->where('school_id', $school->id)->where('type', 'fee_income')->sum('balance_cents'),
            'inactive_learners' => DB::table('students as s')->join('users as u', 'u.id', '=', 's.user_id')->where('s.school_id', $school->id)->where('u.status', 0)->count()];
    });
}

// Fresh school per case; retain immutable money and leave School A/B untouched.
return DB::transaction(function (): array {
    $tag = substr(str_replace('-', '', (string) Str::uuid()), 0, 12);
    $school = School::create(['name' => 'Synthetic billing '.$tag, 'slug' => 'synthetic-billing-'.$tag, 'status' => 'active', 'timezone' => 'Africa/Johannesburg']);

    return app(CurrentSchool::class)->run($school, function () use ($tag, $school): array {
        app(SchoolRoles::class)->seed((int) $school->id);
        $financeRole = Role::firstOrCreate(['name' => 'Finance', 'guard_name' => 'web']);
        $financeRole->forceFill(['custom_role' => 1])->save();
        $financeRole->syncPermissions(['fee-plans-manage', 'fee-accounts-manage', 'fee-billing-manage']);
        $today = now('Africa/Johannesburg')->toDateString();
        $y = substr($today, 0, 4);
        $at = now('UTC')->format('Y-m-d H:i:s');
        $makeUser = function (string $kind, string $role, bool $active = true) use ($tag, $school, $y, $at): User {
            $user = new User;
            $user->forceFill(['first_name' => 'Synthetic', 'last_name' => $kind.' '.$tag, 'email' => 'billing-'.$kind.'-'.$tag.'@'.DemoSchoolSeeder::EMAIL_DOMAIN,
                'password' => Hash::make(DemoSchoolSeeder::PASSWORD), 'gender' => 'Female', 'mobile' => '0821234567', 'image' => null,
                'status' => $active ? 1 : 0, 'dob' => '1990-01-01', 'email_verified_at' => $at, 'password_change_required' => false])->save();
            $user->assignRole($role);
            SchoolMembership::updateOrCreate(['school_id' => $school->id, 'user_id' => $user->id, 'role' => $role], ['status' => 'active', 'starts_on' => "$y-01-01"]);

            return $user;
        };
        $finance = $makeUser('finance', 'Finance');
        $teacher = $makeUser('teacher', 'Teacher');
        DB::table('teachers')->insert(['school_id' => $school->id, 'user_id' => $teacher->id, 'qualification' => 'Synthetic fixture', 'created_at' => $at, 'updated_at' => $at]);
        $parents = [$makeUser('parent-one', 'Parent'), $makeUser('parent-two', 'Parent')];
        foreach ($parents as $parent) {
            DB::table('parents')->insert(['school_id' => $school->id, 'user_id' => $parent->id, 'created_at' => $at, 'updated_at' => $at]);
        }
        $year = SessionYear::create(['name' => $y, 'start_date' => "$y-01-01", 'end_date' => "$y-12-31"]);
        $year->forceFill(['default' => 1])->save();
        Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $year->id]);
        Settings::updateOrCreate(['type' => 'school_name'], ['message' => $school->name]);
        SchoolModule::updateOrCreate(['module' => 'fees'], ['in_package' => true, 'enabled' => true, 'read_only' => false]);
        app(ModuleGate::class)->forget();
        $medium = DB::table('mediums')->insertGetId(['school_id' => $school->id, 'name' => 'Synthetic medium', 'created_at' => $at, 'updated_at' => $at]);
        $class = DB::table('classes')->insertGetId(['school_id' => $school->id, 'name' => 'Synthetic class', 'medium_id' => $medium, 'created_at' => $at, 'updated_at' => $at]);
        $section = DB::table('sections')->insertGetId(['school_id' => $school->id, 'name' => 'Synthetic section', 'created_at' => $at, 'updated_at' => $at]);
        $homeroom = DB::table('class_sections')->insertGetId(['school_id' => $school->id, 'class_id' => $class, 'section_id' => $section, 'created_at' => $at, 'updated_at' => $at]);
        $category = DB::table('categories')->insertGetId(['school_id' => $school->id, 'name' => 'Synthetic category', 'status' => 1, 'created_at' => $at, 'updated_at' => $at]);
        $learners = [];
        for ($i = 1; $i <= 2; $i++) {
            // Billing must not activate a learner awaiting guardian approval.
            $person = $makeUser('child-'.$i, 'Student', false);
            $learner = DB::table('students')->insertGetId(['school_id' => $school->id, 'user_id' => $person->id, 'class_id' => $class, 'class_section_id' => $homeroom,
                'category_id' => $category, 'admission_no' => 'BILL-'.$tag.'-'.$i, 'roll_number' => $i, 'admission_date' => "$y-01-01", 'is_new_admission' => 1,
                'application_type' => 'offline', 'created_at' => $at, 'updated_at' => $at]);
            DB::table('enrolments')->insert(['school_id' => $school->id, 'learner_id' => $learner, 'class_section_id' => $homeroom, 'session_year_id' => $year->id,
                'starts_on' => "$y-01-01", 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic annual billing browser']);
            foreach ($parents as $parent) {
                GuardianLink::create(['learner_id' => $learner, 'guardian_user_id' => $parent->id, 'relationship' => 'guardian', 'status' => 'active', 'can_see_fees' => true, 'can_approve' => true]);
            }
            $learners[] = ['id' => $learner, 'name' => trim($person->first_name.' '.$person->last_name)];
        }
        app(FeePlans::class)->save($finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $year->id, 'name' => 'Synthetic annual tuition',
            'scope' => 'all', 'target_id' => null, 'amount' => '10.01', 'currency' => 'ZAR', 'state' => 'active']);

        return ['school' => (int) $school->id, 'year' => (int) $year->id, 'date' => $today, 'due' => now('Africa/Johannesburg')->addDays(30)->toDateString(),
            'finance' => $finance->email, 'teacher' => $teacher->email, 'parents' => array_map(fn ($p) => (int) $p->id, $parents), 'learners' => $learners];
    });
});
