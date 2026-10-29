<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeItem;
use App\Models\Money\FeePlan;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolOwnedTables;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Platform\Tenancy\SchoolTable;
use App\Services\SessionYearService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Two demo schools with one user per profile each, for demos, the self-test
 * matrix, the Playwright checks and the cross-school suite:
 *
 *     php artisan db:seed --class=DemoSchoolSeeder
 *
 * Every account below signs in with the password DemoSchoolSeeder::PASSWORD
 * (NavigateDemo2026). These are public demo accounts: don't run this on a
 * production database that real people use.
 *
 * | Profile                                  | School A (school 1)            | School B (Baobab)              |
 * |------------------------------------------|--------------------------------|--------------------------------|
 * | Super Admin                              | admin.a@demo.navigateed.test   | admin.b@demo.navigateed.test   |
 * | Teacher (homeroom and subject teacher)   | teacher.a@demo.navigateed.test | teacher.b@demo.navigateed.test |
 * | Second teacher (second homeroom)         | teacher2.a@...                 | teacher2.b@...                 |
 * | Staff with the custom Finance role       | finance.a@...                  | finance.b@...                  |
 * | Parent of two learners                   | parent.a@...                   | parent.b@...                   |
 * | Approved learner, in the app             | admission number DEMO-A-0001   | admission number DEMO-B-0001   |
 * | Parent with a child at each school       | parent.both@... (Johan Botha at A, Refilwe Molefe at B)         |
 * | Super Admin at both schools              | admin.both@...                                                  |
 *
 * Each school has this year and last year (four terms each), two grades with
 * a homeroom each, subjects, homeroom and subject teachers, a timetable, six
 * learners (two of them the parent's children), a pending online application,
 * fees, a grading scale, leave settings, notices, a holiday, an event and
 * today's registers, so Home's setup checklist is complete and the year
 * switcher has a past year with different learners. School A also has an exam
 * (a Mathematics paper yesterday, with the demo learner's mark) and an
 * assignment, so marks capture and the assignments list can be tried from the
 * seed alone. School B's names (its grades, subjects, people, fees and
 * notices) appear nowhere in School A, so the cross-school suite
 * (tests/Feature/Tenancy) can tell a leak from a match.
 *
 * Idempotent: running it again updates the same rows. It needs the roles and
 * permissions from InstallationSeeder and runs it first when they are missing.
 * It never depends on the tests/ namespace.
 */
class DemoSchoolSeeder extends Seeder
{
    public const PASSWORD = 'NavigateDemo2026';

    public const EMAIL_DOMAIN = 'demo.navigateed.test';

    /** School B's slug: the second school is found by it. */
    public const SCHOOL_B_SLUG = 'baobab-demo-high';

    /** @var array<string, int> */
    private array $ids = [];

    /** @var array<string, bool> */
    private array $timestamps = [];

    /**
     * The demo schools and everything that differs between them. Amounts are
     * in cents (the money rule) and written to the legacy float columns in rands.
     *
     * @return list<array<string, mixed>>
     */
    public function schools(): array
    {
        return [
            [
                'key' => 'a',
                'slug' => null, // the install's own school, school 1
                'name' => 'NavigateEd Demo School',
                'email' => 'office@'.self::EMAIL_DOMAIN,
                'phone' => '+27 12 345 6789',
                'address' => '12 Jacaranda Street, Pretoria, 0083',
                'tagline' => 'Every learner, their best',
                'grades' => ['Grade 5', 'Grade 6'],
                'section' => 'A',
                'subjects' => [
                    'Mathematics' => ['MATH', 'Theory'],
                    'English Home Language' => ['ENG', 'Theory'],
                    'Natural Sciences' => ['NS', 'Practical'],
                    'Life Skills' => ['LS', 'Theory'],
                ],
                'admin' => ['Ayanda', 'Nkosi', 'Female'],
                'teachers' => ['teacher' => ['Thandi', 'Mokoena', 'Female'], 'teacher2' => ['Pieter', 'van Wyk', 'Male']],
                'finance' => ['Grace', 'Molefe', 'Female'],
                'parents' => ['parent' => ['Sipho', 'Dlamini', 'Male', 'Engineer'], 'parent2' => ['Priya', 'Patel', 'Female', 'Pharmacist']],
                // [first, last, gender, grade now (0 or 1), grade last year (null: new this year), father, mother]
                'learners' => [
                    '0001' => ['Lerato', 'Dlamini', 'Female', 0, null, 'parent', null],
                    '0002' => ['Kabelo', 'Dlamini', 'Male', 1, 0, 'parent', null],
                    '0003' => ['Ayesha', 'Patel', 'Female', 0, null, null, 'parent2'],
                    '0004' => ['Johan', 'Botha', 'Male', 0, null, 'both', null],
                    '0005' => ['Zanele', 'Mthembu', 'Female', 1, 0, null, null],
                    '0006' => ['Liam', 'Smith', 'Male', 1, 0, null, null],
                ],
                'applicant' => ['Naledi', 'Khumalo', 'Female'],
                'fees' => [
                    'compulsory' => ['Tuition', 'School fees for the year', [1_850_000, 1_950_000]],
                    'optional' => ['Transport', 'School bus, optional', 450_000],
                ],
                'notices' => [
                    ['current', 'Welcome to the new term', 'Term 4 starts on Monday. Please check the updated timetable.'],
                    ['current', 'Grade 5 outing to the science centre', 'Permission slips are due by Friday.'],
                    ['past', 'Prize-giving', 'Prize-giving takes place in the hall on the last Friday of the year.'],
                ],
                'holiday' => ['Heritage Day', '09-24'],
                'event' => ['Sports day', 'Athletics on the school field.'],
            ],
            [
                'key' => 'b',
                'slug' => self::SCHOOL_B_SLUG,
                'name' => 'Baobab Demo High',
                'email' => 'office.b@'.self::EMAIL_DOMAIN,
                'phone' => '+27 15 987 6543',
                'address' => '3 Baobab Avenue, Polokwane, 0699',
                'tagline' => 'Deep roots, tall dreams',
                'grades' => ['Grade 8', 'Grade 9'],
                'section' => 'East',
                'subjects' => [
                    'Geography' => ['GEO', 'Theory'],
                    'History' => ['HIS', 'Theory'],
                    'Technology' => ['TECH', 'Practical'],
                    'Economic and Management Sciences' => ['EMS', 'Theory'],
                ],
                'admin' => ['Busisiwe', 'Baloyi', 'Female'],
                'teachers' => ['teacher' => ['Themba', 'Ngwenya', 'Male'], 'teacher2' => ['Marike', 'Steyn', 'Female']],
                'finance' => ['Nomsa', 'Zungu', 'Female'],
                'parents' => ['parent' => ['Lindiwe', 'Mahlangu', 'Female', 'Nurse'], 'parent2' => ['Ravi', 'Naidoo', 'Male', 'Accountant']],
                'learners' => [
                    '0001' => ['Khanyisile', 'Mahlangu', 'Female', 0, null, null, 'parent'],
                    '0002' => ['Lwazi', 'Mahlangu', 'Male', 1, 0, null, 'parent'],
                    '0003' => ['Anjali', 'Naidoo', 'Female', 0, null, 'parent2', null],
                    '0004' => ['Refilwe', 'Molefe', 'Female', 0, null, 'both', null],
                    '0005' => ['Nandi', 'Shabalala', 'Female', 1, 0, null, null],
                    '0006' => ['Ruan', 'Pretorius', 'Male', 1, 0, null, null],
                ],
                'applicant' => ['Bonolo', 'Sithole', 'Female'],
                'fees' => [
                    'compulsory' => ['Boarding fees', 'Hostel fees for the year', [2_400_000, 2_550_000]],
                    'optional' => ['Sports levy', 'Inter-school sport, optional', 120_000],
                ],
                'notices' => [
                    ['current', 'Baobab gala evening', 'The gala evening is in the Baobab hall on Friday.'],
                    ['current', 'Grade 8 camp in the Magaliesberg', 'Indemnity forms are due by Wednesday.'],
                    ['past', 'Baobab awards evening', 'The awards evening closes the year.'],
                ],
                'holiday' => ['Freedom Day', '04-27'],
                'event' => ['Baobab inter-house gala', 'Swimming at the town pool.'],
            ],
        ];
    }

    /** Every demo school, in its own school (the console has none). */
    public function run(): void
    {
        $currentSchool = app(CurrentSchool::class);
        $currentSchool->runInSchoolOrDefault(function () {
            if (! Role::query()->where('name', 'Super Admin')->exists()) {
                $this->call(InstallationSeeder::class);
            }
            $this->images();
        });

        DB::transaction(function () use ($currentSchool) {
            // The parent with a child at each school, and the admin at both.
            $both = $this->user('parent.both', 'Kagiso', 'Molefe', null, ['gender' => 'Male']);
            $adminBoth = $this->user('admin.both', 'Sarah', 'Daniels', null, ['gender' => 'Female']);

            foreach ($this->schools() as $school) {
                $model = $this->schoolRow($school);
                app(SchoolRoles::class)->seed($model);
                $currentSchool->run($model, function () use ($school, $both, $adminBoth) {
                    [$past, $current] = $this->years();
                    $this->school($school, $past, $current, $both);
                    $adminBoth->syncRoles(['Super Admin']);
                });
            }
        });

        $this->command?->info('Demo schools ready. Every demo account uses the password '.self::PASSWORD.'.');
    }

    /** @param array<string, mixed> $school */
    private function schoolRow(array $school): School
    {
        if ($school['slug'] === null) {
            return School::query()->findOrFail(CurrentSchool::defaultId());
        }

        $row = School::query()->firstOrNew(['slug' => $school['slug']]);
        $row->fill(['name' => $school['name'], 'status' => School::ACTIVE, 'timezone' => 'Africa/Johannesburg'])->save();

        return $row;
    }

    /**
     * This year and last year, South African dates with four terms. The demo
     * year becomes current only when the school's current year has ended or
     * is missing, so a running school year is never replaced.
     *
     * @return array{0: SessionYear, 1: SessionYear}
     */
    private function years(): array
    {
        $year = (int) now()->format('Y');
        $past = $this->year((string) ($year - 1), $year - 1);
        $current = $this->year((string) $year, $year);

        $currentId = getSettings('session_year')['session_year'] ?? null;
        $installed = $currentId ? SessionYear::find((int) $currentId) : null;
        if (! $installed || $installed->end_date < now()->toDateString()) {
            app(SessionYearService::class)->makeCurrent($current);
        } elseif ($installed->id !== $current->id) {
            $this->command?->warn("The school's current year ({$installed->name}) is still running; the demo data is in {$current->name}.");
        }

        return [$past, $current];
    }

    private function year(string $name, int $year): SessionYear
    {
        $sessionYear = SessionYear::withTrashed()->firstOrNew(['name' => $name]);
        $sessionYear->fill([
            'start_date' => "$year-01-14",
            'end_date' => "$year-12-09",
            'fee_due_date' => "$year-03-31",
            'fee_due_charges' => 5,
            'include_fee_installments' => 0,
        ]);
        $sessionYear->deleted_at = null;
        $sessionYear->save();

        foreach ([
            'Term 1' => ["$year-01-14", "$year-03-27"],
            'Term 2' => ["$year-04-08", "$year-06-26"],
            'Term 3' => ["$year-07-21", "$year-10-02"],
            'Term 4' => ["$year-10-13", "$year-12-09"],
        ] as $term => [$start, $end]) {
            $this->row('terms', ['session_year_id' => $sessionYear->id, 'name' => $term], ['start_date' => $start, 'end_date' => $end]);
        }

        return $sessionYear;
    }

    /** @param array<string, mixed> $school */
    private function school(array $school, SessionYear $past, SessionYear $current, User $both): void
    {
        $this->profile($school);
        $this->structure($school, [$past, $current]);
        $this->people($school, $both);
        $this->allocation($school, $past, $current);
        $this->learners($school, $past, $current);
        $this->money($school, $current);
        $this->calendar($school, $past, $current);
        if ($school['key'] === 'a') {
            $this->schoolwork($school['key'], $current);
        }
    }

    /**
     * The school's own settings rows (Phase 1: per school), which its schools
     * row follows (SchoolBranding).
     *
     * @param  array<string, mixed>  $school
     */
    private function profile(array $school): void
    {
        foreach ([
            'school_name' => $school['name'],
            'school_email' => $school['email'],
            'school_phone' => $school['phone'],
            'school_address' => $school['address'],
            'school_tagline' => $school['tagline'],
            'currency_code' => 'ZAR',
            'currency_symbol' => 'R',
        ] as $type => $message) {
            Settings::query()->updateOrCreate(['type' => $type], ['message' => $message]);
        }
    }

    /**
     * @param  array<string, mixed>  $school
     * @param  list<SessionYear>  $years
     */
    private function structure(array $school, array $years): void
    {
        $k = $school['key'];
        $medium = $this->row('mediums', ['name' => 'English']);
        $section = $this->row('sections', ['name' => $school['section']]);
        $this->ids["$k.category"] = $this->row('categories', ['name' => 'General'], ['status' => 1]);

        foreach ($school['grades'] as $grade) {
            $class = $this->row('classes', ['name' => $grade, 'medium_id' => $medium]);
            $this->ids["$k.class.$grade"] = $class;
            $this->ids["$k.homeroom.$grade"] = $this->row('class_sections', ['class_id' => $class, 'section_id' => $section]);
            foreach ($years as $year) {
                $this->row('class_session_configs', ['class_id' => $class, 'session_year_id' => $year->id], ['include_terms' => 0]);
            }
        }

        foreach ($school['subjects'] as $name => [$code, $type]) {
            $this->ids["$k.subject.$name"] = $this->row('subjects', ['name' => $name, 'medium_id' => $medium], [
                'code' => $code, 'type' => $type, 'bg_color' => '#22577A', 'image' => 'demo/subject.svg',
            ]);
        }

        foreach ($years as $year) {
            foreach ($school['grades'] as $grade) {
                foreach (array_keys($school['subjects']) as $subject) {
                    $this->row('class_subjects', [
                        'class_id' => $this->ids["$k.class.$grade"], 'subject_id' => $this->ids["$k.subject.$subject"], 'session_year_id' => $year->id,
                    ], ['type' => 'Compulsory']);
                }
            }
            foreach ([[80, 100, '7'], [70, 79, '6'], [60, 69, '5'], [50, 59, '4'], [40, 49, '3'], [30, 39, '2'], [0, 29, '1']] as [$from, $to, $level]) {
                $this->row('grades', ['session_year_id' => $year->id, 'grade' => $level], ['starting_range' => $from, 'ending_range' => $to]);
            }
        }
    }

    /** @param array<string, mixed> $school */
    private function people(array $school, User $both): void
    {
        $k = $school['key'];
        [$first, $last, $gender] = $school['admin'];
        $admin = $this->user("admin.$k", $first, $last, 'Super Admin', ['gender' => $gender]);
        $this->ids["$k.admin"] = $admin->id;

        foreach ($school['teachers'] as $login => [$first, $last, $gender]) {
            $user = $this->user("$login.$k", $first, $last, 'Teacher', ['gender' => $gender, 'dob' => '1988-03-15', 'current_address' => '4 School Lane']);
            ParentPolicyService::acceptCurrent($user);
            $this->ids["$k.$login"] = $this->row('teachers', ['user_id' => $user->id], ['qualification' => 'B.Ed (Intermediate Phase)']);
            $this->row('staffs', ['user_id' => $user->id]);
        }

        // Staff: the seed has no staff roles, so the demo adds a custom Finance
        // role, made the way a school makes one on the Roles page.
        $finance = Role::query()->firstOrCreate(['name' => 'Finance', 'guard_name' => 'web']);
        $finance->forceFill(['custom_role' => 1])->save();
        $finance->syncPermissions(['fee-plans-manage', 'fee-accounts-manage', 'fee-billing-manage', 'fee-payments-manage', 'fee-credits-manage', 'fee-items-manage']);
        [$first, $last, $gender] = $school['finance'];
        $staff = $this->user("finance.$k", $first, $last, 'Finance', ['gender' => $gender, 'dob' => '1985-09-30']);
        $this->row('staffs', ['user_id' => $staff->id]);

        foreach ($school['parents'] as $login => [$first, $last, $gender, $occupation]) {
            $user = $this->user("$login.$k", $first, $last, 'Parent', ['gender' => $gender]);
            ParentPolicyService::acceptCurrent($user);
            $this->ids["$k.$login"] = $this->parentRow($user, $occupation);
        }

        // The parent with a child at each school is a parent at both.
        $both->syncRoles(['Parent']);
        ParentPolicyService::acceptCurrent($both);
        $this->ids["$k.both"] = $this->parentRow($both, 'Pilot');
    }

    private function parentRow(User $user, string $occupation): int
    {
        return $this->row('parents', ['user_id' => $user->id], [
            'first_name' => $user->first_name, 'last_name' => $user->last_name, 'gender' => $user->gender, 'email' => $user->email,
            'mobile' => '0821234567', 'occupation' => $occupation, 'image' => 'demo/avatar.png',
        ]);
    }

    /** @param array<string, mixed> $school */
    private function allocation(array $school, SessionYear $past, SessionYear $current): void
    {
        $k = $school['key'];
        $teacher = $this->ids["$k.teacher"];
        $teacher2 = $this->ids["$k.teacher2"];
        [$first, $second] = $school['grades'];
        $g1 = $this->ids["$k.homeroom.$first"];
        $g2 = $this->ids["$k.homeroom.$second"];
        [$s1, $s2, $s3, $s4] = array_keys($school['subjects']);

        foreach ([$past, $current] as $year) {
            $this->row('class_teachers', ['class_section_id' => $g1, 'session_year_id' => $year->id], ['class_teacher_id' => $teacher]);
            $this->row('class_teachers', ['class_section_id' => $g2, 'session_year_id' => $year->id], ['class_teacher_id' => $teacher2]);
            foreach ([
                [$g1, $s1, $teacher], [$g1, $s2, $teacher], [$g1, $s3, $teacher2], [$g1, $s4, $teacher2],
                [$g2, $s1, $teacher], [$g2, $s2, $teacher2], [$g2, $s3, $teacher2], [$g2, $s4, $teacher2],
            ] as [$homeroom, $subject, $who]) {
                $this->ids["$k.st.{$year->id}.$homeroom.$subject"] = $this->row('subject_teachers', [
                    'class_section_id' => $homeroom, 'subject_id' => $this->ids["$k.subject.$subject"], 'session_year_id' => $year->id,
                ], ['teacher_id' => $who]);
            }
        }

        // This year's timetable for the first homeroom, Monday to Friday.
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $index => $day) {
            foreach ([[$s1, '08:00:00', '08:45:00'], [$s2, '08:45:00', '09:30:00'], [$s3, '10:00:00', '10:45:00'], [$s4, '10:45:00', '11:30:00']] as [$subject, $start, $end]) {
                $this->row('timetables', ['session_year_id' => $current->id, 'class_section_id' => $g1, 'day' => $index + 1, 'start_time' => $start], [
                    'subject_teacher_id' => $this->ids["$k.st.{$current->id}.$g1.$subject"], 'end_time' => $end, 'day_name' => $day,
                ]);
            }
        }

        $this->row('leave_masters', ['session_year_id' => $current->id], ['total_leave' => '12', 'holiday_days' => '0']);
    }

    /** @param array<string, mixed> $school */
    private function learners(array $school, SessionYear $past, SessionYear $current): void
    {
        $k = $school['key'];
        $up = strtoupper($k);
        $homerooms = array_map(fn (string $grade) => $this->ids["$k.homeroom.$grade"], $school['grades']);

        $today = now()->toDateString();
        foreach ($school['learners'] as $number => [$first, $last, $gender, $now, $lastYear, $father, $mother]) {
            $homeroom = $homerooms[$now];
            $admission = "DEMO-$up-$number";
            // Learners sign in to the app with their admission number, kept as their email.
            $user = $this->user($admission, $first, $last, 'Student', ['gender' => $gender, 'dob' => '2015-05-20', 'email' => $admission]);
            $student = $this->row('students', ['admission_no' => $admission], [
                'user_id' => $user->id,
                'class_id' => SchoolTable::query('class_sections')->where('id', $homeroom)->value('class_id'),
                'class_section_id' => $homeroom,
                'category_id' => $this->ids["$k.category"],
                'roll_number' => (int) $number,
                'admission_date' => $lastYear !== null ? $past->start_date : $current->start_date,
                'application_type' => 'offline',
                'father_id' => $father ? $this->ids["$k.$father"] : null,
                'mother_id' => $mother ? $this->ids["$k.$mother"] : null,
            ]);
            $this->row('student_sessions', ['student_id' => $student, 'session_year_id' => $current->id], ['class_section_id' => $homeroom, 'status' => 1]);
            if ($lastYear !== null) {
                $this->row('student_sessions', ['student_id' => $student, 'session_year_id' => $past->id], ['class_section_id' => $homerooms[$lastYear], 'status' => 1]);
            }
            if ($now === 0) {
                $this->row('attendances', ['student_id' => $student, 'date' => $today], [
                    'class_section_id' => $homeroom, 'session_year_id' => $current->id, 'type' => 1, 'remark' => '',
                ]);
            }
        }

        // Today's staff register.
        foreach (["$k.teacher", "$k.teacher2"] as $teacher) {
            $userId = (int) SchoolTable::query('teachers')->where('id', $this->ids[$teacher])->value('user_id');
            $this->row('staff_attendances', ['user_id' => $userId, 'date' => $today], ['session_year_id' => $current->id, 'type' => 'present']);
        }

        // A learner who applied online and is waiting for approval.
        [$first, $last, $gender] = $school['applicant'];
        $applicant = $this->user("DEMO-$up-0100", $first, $last, 'Student', ['gender' => $gender, 'dob' => '2015-08-02', 'email' => "DEMO-$up-0100", 'status' => 0]);
        $this->row('students', ['admission_no' => "DEMO-$up-0100"], [
            'user_id' => $applicant->id,
            'class_id' => $this->ids["$k.class.{$school['grades'][0]}"],
            'category_id' => $this->ids["$k.category"],
            'admission_date' => $today,
            'application_type' => 'online',
        ]);
    }

    /** @param array<string, mixed> $school */
    private function money(array $school, SessionYear $current): void
    {
        $tuition = $school['fees']['compulsory'];
        foreach ($school['grades'] as $index => $grade) {
            $class = (int) SchoolTable::query('classes')->where('name', $grade)->value('id');
            FeePlan::firstOrCreate(['session_year_id' => $current->id, 'name' => $tuition[0], 'scope' => 'class', 'target_id' => $class], ['amount_cents' => $tuition[2][$index], 'currency' => 'ZAR', 'state' => 'active', 'revision' => 1]);
        }
        $optional = $school['fees']['optional'];
        FeeItem::firstOrCreate(['name' => $optional[0]], ['scope' => 'learner', 'amount_cents' => $optional[2],
            'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'vat_rate_bp' => 0, 'state' => 'active', 'revision' => 1]);
        // A setup account has no charges or assigned payer until Finance adds them.
        FeeAccount::firstOrCreate(['reference' => 'DEMO-'.strtoupper($school['key']).'-'.$current->id], ['name' => 'Demo family setup '.$current->name, 'currency' => 'ZAR', 'active' => true, 'revision' => 1]);
    }

    /** @param array<string, mixed> $school */
    private function calendar(array $school, SessionYear $past, SessionYear $current): void
    {
        $year = (int) Carbon::parse($current->start_date)->format('Y');
        foreach ($school['notices'] as [$when, $title, $description]) {
            $sessionYear = $when === 'past' ? $past : $current;
            $title = $when === 'past' ? $title.' '.($year - 1) : $title;
            $this->row('announcements', ['session_year_id' => $sessionYear->id, 'title' => $title], ['description' => $description, 'table_type' => '', 'table_id' => null]);
        }
        [$holiday, $day] = $school['holiday'];
        $this->row('holidays', ['session_year_id' => $current->id, 'title' => $holiday], ['date' => "$year-$day", 'description' => 'Public holiday']);
        [$event, $description] = $school['event'];
        $this->row('events', ['session_year_id' => $current->id, 'title' => $event], [
            'type' => 'single', 'start_date' => "$year-11-14", 'end_date' => "$year-11-14",
            'start_time' => '08:00:00', 'end_time' => '13:00:00', 'description' => $description,
        ]);
    }

    /**
     * School A's exam with a Mathematics paper for both grades dated yesterday
     * (so the teacher can capture marks; it stays unpublished) and the demo
     * learner's mark, and a Mathematics assignment for 5 A due in a week.
     */
    private function schoolwork(string $k, SessionYear $current): void
    {
        $up = strtoupper($k);
        $maths = $this->ids["$k.subject.Mathematics"];
        $exam = $this->row('exams', ['session_year_id' => $current->id, 'name' => 'Term 4 test'], [
            'description' => 'Mathematics, all of Term 4', 'publish' => 0,
        ]);
        $papers = [];
        foreach (['Grade 5', 'Grade 6'] as $grade) {
            $class = $this->ids["$k.class.$grade"];
            $this->row('exam_classes', ['exam_id' => $exam, 'class_id' => $class], ['session_year_id' => $current->id]);
            $papers[$grade] = $this->row('exam_timetables', ['exam_id' => $exam, 'class_id' => $class, 'subject_id' => $maths], [
                'total_marks' => 100, 'passing_marks' => 40, 'date' => now()->subDay()->toDateString(),
                'start_time' => '09:00:00', 'end_time' => '10:00:00', 'session_year_id' => $current->id,
            ]);
        }

        $learner = (int) SchoolTable::query('students')->where('admission_no', "DEMO-$up-0001")->value('id');
        $this->row('exam_marks', ['exam_timetable_id' => $papers['Grade 5'], 'student_id' => $learner], [
            'subject_id' => $maths, 'obtained_marks' => 72, 'passing_status' => 1, 'grade' => '6',
            'teacher_review' => 'Good work on fractions.', 'session_year_id' => $current->id,
        ]);

        $this->row('assignments', ['session_year_id' => $current->id, 'class_section_id' => $this->ids["$k.homeroom.Grade 5"], 'name' => 'Fractions worksheet'], [
            'subject_id' => $maths,
            'instructions' => 'Complete questions 1 to 10 and show your working.',
            'due_date' => now()->addWeek()->setTime(23, 59)->toDateTimeString(),
            'points' => 10,
            'resubmission' => 0,
        ]);
    }

    /**
     * A user by their sign-in name; $role (when given) becomes their only
     * role at the current school.
     *
     * @param  array<string, mixed>  $extra
     */
    private function user(string $login, string $first, string $last, ?string $role, array $extra = []): User
    {
        $email = $extra['email'] ?? "$login@".self::EMAIL_DOMAIN;
        $user = User::where('email', $email)->first() ?? new User;
        $user->forceFill([
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'password' => Hash::make(self::PASSWORD),
            'gender' => $extra['gender'] ?? 'Female',
            'mobile' => '0821234567',
            'image' => 'demo/avatar.png',
            'status' => $extra['status'] ?? 1,
            'dob' => $extra['dob'] ?? null,
            'current_address' => $extra['current_address'] ?? null,
        ])->save();
        if ($role !== null) {
            $user->syncRoles([$role]);
        }

        return $user;
    }

    /**
     * Find a row of the current school by its natural key and update it, or
     * insert it.
     *
     * @param  array<string, mixed>  $match
     * @param  array<string, mixed>  $values
     */
    private function row(string $table, array $match, array $values = []): int
    {
        $now = $this->hasTimestamps($table) ? ['updated_at' => now()] : [];
        if ($this->hasColumn($table, 'deleted_at')) {
            $values['deleted_at'] = null;
        }
        $query = fn () => SchoolOwnedTables::isSchoolOwned($table) ? SchoolTable::query($table) : DB::table($table);
        $existing = $query()->where($match)->value('id');
        if ($existing) {
            if ($values !== []) {
                $query()->where('id', $existing)->update($values + $now);
            }

            return (int) $existing;
        }

        return (int) $query()->insertGetId($match + $values + ($now ? $now + ['created_at' => now()] : []));
    }

    /**
     * Pictures for the demo people and subjects, and for the website sections
     * the installer points at pictures it does not ship, so no page shows a
     * broken image. A school's own website pictures are never replaced.
     */
    private function images(): void
    {
        $disk = Storage::disk('public');
        $disk->put('demo/avatar.png', (string) file_get_contents(public_path('assets/avatar.png')));
        $disk->put('demo/subject.svg', (string) file_get_contents(public_path('assets/no-image.svg')));
        if (Schema::hasTable('web_settings')) {
            foreach (SchoolTable::query('web_settings')->whereNotNull('image')->pluck('image') as $path) {
                if ($path !== '' && ! $disk->exists($path)) {
                    $disk->put($path, (string) file_get_contents(public_path('assets/eschool.jpg')));
                }
            }
        }
    }

    private function hasTimestamps(string $table): bool
    {
        return $this->timestamps[$table] ??= Schema::hasColumn($table, 'created_at');
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->timestamps["$table.$column"] ??= Schema::hasColumn($table, $column);
    }
}
