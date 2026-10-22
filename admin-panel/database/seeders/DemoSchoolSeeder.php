<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SessionYear;
use App\Models\User;
use App\Services\ParentPolicyService;
use App\Services\SessionYearService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

/**
 * A demo school with one user per profile the install has, for demos, the
 * self-test matrix and the Playwright checks:
 *
 *     php artisan db:seed --class=DemoSchoolSeeder
 *
 * Every account below signs in with the password DemoSchoolSeeder::PASSWORD
 * (NavigateDemo2026). These are public demo accounts: don't run this on a
 * production database that real people use.
 *
 * | Profile                                  | Signs in with              |
 * |------------------------------------------|----------------------------|
 * | Super Admin                              | admin.a@demo.navigateed.test   |
 * | Teacher (homeroom 5 A, subject teacher)  | teacher.a@demo.navigateed.test |
 * | Second teacher (homeroom 6 A)            | teacher2.a@demo.navigateed.test|
 * | Staff with the custom Finance role       | finance.a@demo.navigateed.test |
 * | Parent of two learners (5 A and 6 A)     | parent.a@demo.navigateed.test  |
 * | Approved learner (Grade 5 A), in the app | admission number DEMO-A-0001   |
 *
 * The school has this year and last year (four terms each), two grades with a
 * homeroom each, subjects, homeroom and subject teachers, a timetable, six
 * learners (two of them the parent's children), a pending online application,
 * fees, a grading scale, leave settings, notices, a holiday, an event,
 * today's registers, an exam (a Mathematics paper yesterday, with the demo
 * learner's mark) and an assignment, so Home's setup checklist is complete,
 * the year switcher has a past year with different learners, and marks
 * capture and the assignments list can be tried from the seed alone.
 *
 * Idempotent: running it again updates the same rows. It needs the roles and
 * permissions from InstallationSeeder and runs it first when they are missing.
 * It never depends on the tests/ namespace.
 *
 * Phase 1 adds a second school: add it to schools() and give every row its
 * school_id; each school's data is built by school().
 */
class DemoSchoolSeeder extends Seeder
{
    public const PASSWORD = 'NavigateDemo2026';

    public const EMAIL_DOMAIN = 'demo.navigateed.test';

    /** Amounts in cents (the money rule); written to the legacy float columns in rands. */
    private const TUITION_CENTS = ['Grade 5' => 1_850_000, 'Grade 6' => 1_950_000];

    private const TRANSPORT_CENTS = 450_000;

    /** @var array<string, int> */
    private array $ids = [];

    /** @var array<string, bool> */
    private array $timestamps = [];

    /**
     * The demo schools. Today's install is one school; Phase 1 adds School B.
     *
     * @return list<array{key: string, name: string, email: string, phone: string, address: string, tagline: string}>
     */
    public function schools(): array
    {
        return [
            [
                'key' => 'a',
                'name' => 'NavigateEd Demo School',
                'email' => 'office@'.self::EMAIL_DOMAIN,
                'phone' => '+27 12 345 6789',
                'address' => '12 Jacaranda Street, Pretoria, 0083',
                'tagline' => 'Every learner, their best',
            ],
        ];
    }

    public function run(): void
    {
        if (! Role::where('name', 'Super Admin')->exists()) {
            $this->call(InstallationSeeder::class);
        }
        $this->images();

        DB::transaction(function () {
            [$past, $current] = $this->years();
            foreach ($this->schools() as $school) {
                $this->school($school, $past, $current);
            }
        });

        $this->command?->info('Demo school ready. Every demo account uses the password '.self::PASSWORD.'.');
    }

    /**
     * This year and last year, South African dates with four terms. The demo
     * year becomes current only when the install's current year has ended or
     * is missing, so a running school year is never replaced.
     *
     * @return array{0: SessionYear, 1: SessionYear}
     */
    private function years(): array
    {
        $year = (int) now()->format('Y');
        $past = $this->year((string) ($year - 1), $year - 1);
        $current = $this->year((string) $year, $year);

        $currentId = DB::table('settings')->where('type', 'session_year')->value('message');
        $installed = $currentId ? SessionYear::find((int) $currentId) : null;
        if (! $installed || $installed->end_date < now()->toDateString()) {
            app(SessionYearService::class)->makeCurrent($current);
        } elseif ($installed->id !== $current->id) {
            $this->command?->warn("The install's current year ({$installed->name}) is still running; the demo data is in {$current->name}.");
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
            $this->row('semesters', ['session_year_id' => $sessionYear->id, 'name' => $term], ['start_date' => $start, 'end_date' => $end]);
        }

        return $sessionYear;
    }

    /** @param array{key: string, name: string, email: string, phone: string, address: string, tagline: string} $school */
    private function school(array $school, SessionYear $past, SessionYear $current): void
    {
        $k = $school['key'];
        $this->profile($school);
        $this->structure($k, [$past, $current]);
        $this->people($k);
        $this->allocation($k, $past, $current);
        $this->learners($k, $past, $current);
        $this->money($current);
        $this->calendar($past, $current);
        $this->schoolwork($k, $current);
    }

    /** The install's school settings (Phase 1 moves them to the school's own settings). */
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
            DB::table('settings')->updateOrInsert(['type' => $type], ['message' => $message]);
        }
    }

    /** @param list<SessionYear> $years */
    private function structure(string $k, array $years): void
    {
        $medium = $this->row('mediums', ['name' => 'English']);
        $section = $this->row('sections', ['name' => 'A']);
        $this->ids["$k.category"] = $this->row('categories', ['name' => 'General'], ['status' => 1]);

        foreach (['Grade 5', 'Grade 6'] as $grade) {
            $class = $this->row('classes', ['name' => $grade, 'medium_id' => $medium]);
            $this->ids["$k.class.$grade"] = $class;
            $this->ids["$k.homeroom.$grade"] = $this->row('class_sections', ['class_id' => $class, 'section_id' => $section]);
            foreach ($years as $year) {
                $this->row('class_session_configs', ['class_id' => $class, 'session_year_id' => $year->id], ['include_semesters' => 0]);
            }
        }

        foreach ([
            'Mathematics' => ['MATH', 'Theory'],
            'English Home Language' => ['ENG', 'Theory'],
            'Natural Sciences' => ['NS', 'Practical'],
            'Life Skills' => ['LS', 'Theory'],
        ] as $name => [$code, $type]) {
            $this->ids["$k.subject.$name"] = $this->row('subjects', ['name' => $name, 'medium_id' => $medium], [
                'code' => $code, 'type' => $type, 'bg_color' => '#22577A', 'image' => 'demo/subject.svg',
            ]);
        }

        foreach ($years as $year) {
            foreach (['Grade 5', 'Grade 6'] as $grade) {
                foreach (['Mathematics', 'English Home Language', 'Natural Sciences', 'Life Skills'] as $subject) {
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

    private function people(string $k): void
    {
        $admin = $this->user("admin.$k", 'Ayanda', 'Nkosi', 'Super Admin', ['gender' => 'Female']);
        $this->ids["$k.admin"] = $admin->id;

        foreach (['teacher' => ['Thandi', 'Mokoena', 'Female'], 'teacher2' => ['Pieter', 'van Wyk', 'Male']] as $login => [$first, $last, $gender]) {
            $user = $this->user("$login.$k", $first, $last, 'Teacher', ['gender' => $gender, 'dob' => '1988-03-15', 'current_address' => '4 School Lane, Pretoria']);
            ParentPolicyService::acceptCurrent($user);
            $this->ids["$k.$login"] = $this->row('teachers', ['user_id' => $user->id], ['qualification' => 'B.Ed (Intermediate Phase)']);
            $this->row('staffs', ['user_id' => $user->id]);
        }

        // Staff: the seed has no staff roles, so the demo adds a custom Finance
        // role, made the way a school makes one on the Roles page.
        $finance = Role::firstOrCreate(['name' => 'Finance', 'guard_name' => 'web']);
        $finance->forceFill(['custom_role' => 1])->save();
        $finance->syncPermissions(['fees-type', 'fees-classes', 'fees-paid', 'fees-config']);
        $staff = $this->user("finance.$k", 'Grace', 'Molefe', 'Finance', ['gender' => 'Female', 'dob' => '1985-09-30']);
        $this->row('staffs', ['user_id' => $staff->id]);

        foreach ([
            'parent' => ['Sipho', 'Dlamini', 'Male', 'Engineer'],
            'parent2' => ['Priya', 'Patel', 'Female', 'Pharmacist'],
        ] as $login => [$first, $last, $gender, $occupation]) {
            $user = $this->user("$login.$k", $first, $last, 'Parent', ['gender' => $gender]);
            ParentPolicyService::acceptCurrent($user);
            $this->ids["$k.$login"] = $this->row('parents', ['user_id' => $user->id], [
                'first_name' => $first, 'last_name' => $last, 'gender' => $gender, 'email' => $user->email,
                'mobile' => '0821234567', 'occupation' => $occupation, 'image' => 'demo/avatar.png',
            ]);
        }
    }

    private function allocation(string $k, SessionYear $past, SessionYear $current): void
    {
        $teacher = $this->ids["$k.teacher"];
        $teacher2 = $this->ids["$k.teacher2"];
        $g5 = $this->ids["$k.homeroom.Grade 5"];
        $g6 = $this->ids["$k.homeroom.Grade 6"];

        foreach ([$past, $current] as $year) {
            $this->row('class_teachers', ['class_section_id' => $g5, 'session_year_id' => $year->id], ['class_teacher_id' => $teacher]);
            $this->row('class_teachers', ['class_section_id' => $g6, 'session_year_id' => $year->id], ['class_teacher_id' => $teacher2]);
            foreach ([
                [$g5, 'Mathematics', $teacher], [$g5, 'English Home Language', $teacher], [$g5, 'Natural Sciences', $teacher2], [$g5, 'Life Skills', $teacher2],
                [$g6, 'Mathematics', $teacher], [$g6, 'English Home Language', $teacher2], [$g6, 'Natural Sciences', $teacher2], [$g6, 'Life Skills', $teacher2],
            ] as [$homeroom, $subject, $who]) {
                $this->ids["$k.st.{$year->id}.$homeroom.$subject"] = $this->row('subject_teachers', [
                    'class_section_id' => $homeroom, 'subject_id' => $this->ids["$k.subject.$subject"], 'session_year_id' => $year->id,
                ], ['teacher_id' => $who]);
            }
        }

        // This year's timetable for 5 A, Monday to Friday.
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $index => $day) {
            foreach ([
                ['Mathematics', '08:00:00', '08:45:00'], ['English Home Language', '08:45:00', '09:30:00'],
                ['Natural Sciences', '10:00:00', '10:45:00'], ['Life Skills', '10:45:00', '11:30:00'],
            ] as [$subject, $start, $end]) {
                $this->row('timetables', ['session_year_id' => $current->id, 'class_section_id' => $g5, 'day' => $index + 1, 'start_time' => $start], [
                    'subject_teacher_id' => $this->ids["$k.st.{$current->id}.$g5.$subject"], 'end_time' => $end, 'day_name' => $day,
                ]);
            }
        }

        $this->row('leave_masters', ['session_year_id' => $current->id], ['total_leave' => '12', 'holiday_days' => '0']);
    }

    private function learners(string $k, SessionYear $past, SessionYear $current): void
    {
        $g5 = $this->ids["$k.homeroom.Grade 5"];
        $g6 = $this->ids["$k.homeroom.Grade 6"];
        $up = strtoupper($k);

        // [first, last, gender, homeroom now, homeroom last year (null: new this year), father, mother]
        $learners = [
            '0001' => ['Lerato', 'Dlamini', 'Female', $g5, null, "$k.parent", null],
            '0002' => ['Kabelo', 'Dlamini', 'Male', $g6, $g5, "$k.parent", null],
            '0003' => ['Ayesha', 'Patel', 'Female', $g5, null, null, "$k.parent2"],
            '0004' => ['Johan', 'Botha', 'Male', $g5, null, null, null],
            '0005' => ['Zanele', 'Mthembu', 'Female', $g6, $g5, null, null],
            '0006' => ['Liam', 'Smith', 'Male', $g6, $g5, null, null],
        ];

        $today = now()->toDateString();
        foreach ($learners as $number => [$first, $last, $gender, $homeroom, $lastYear, $father, $mother]) {
            $admission = "DEMO-$up-$number";
            // Learners sign in to the app with their admission number, kept as their email.
            $user = $this->user($admission, $first, $last, 'Student', ['gender' => $gender, 'dob' => '2015-05-20', 'email' => $admission]);
            $student = $this->row('students', ['admission_no' => $admission], [
                'user_id' => $user->id,
                'class_id' => DB::table('class_sections')->where('id', $homeroom)->value('class_id'),
                'class_section_id' => $homeroom,
                'category_id' => $this->ids["$k.category"],
                'roll_number' => (int) $number,
                'admission_date' => $lastYear ? $past->start_date : $current->start_date,
                'application_type' => 'offline',
                'father_id' => $father ? $this->ids[$father] : null,
                'mother_id' => $mother ? $this->ids[$mother] : null,
            ]);
            $this->row('student_sessions', ['student_id' => $student, 'session_year_id' => $current->id], ['class_section_id' => $homeroom, 'status' => 1]);
            if ($lastYear) {
                $this->row('student_sessions', ['student_id' => $student, 'session_year_id' => $past->id], ['class_section_id' => $lastYear, 'status' => 1]);
            }
            if ($homeroom === $g5) {
                $this->row('attendances', ['student_id' => $student, 'date' => $today], [
                    'class_section_id' => $g5, 'session_year_id' => $current->id, 'type' => 1, 'remark' => '',
                ]);
            }
        }

        // Today's staff register.
        foreach (["$k.teacher", "$k.teacher2"] as $teacher) {
            $userId = (int) DB::table('teachers')->where('id', $this->ids[$teacher])->value('user_id');
            $this->row('staff_attendances', ['user_id' => $userId, 'date' => $today], ['session_year_id' => $current->id, 'type' => 'present']);
        }

        // A learner who applied online and is waiting for approval.
        $applicant = $this->user("DEMO-$up-0100", 'Naledi', 'Khumalo', 'Student', ['gender' => 'Female', 'dob' => '2015-08-02', 'email' => "DEMO-$up-0100", 'status' => 0]);
        $this->row('students', ['admission_no' => "DEMO-$up-0100"], [
            'user_id' => $applicant->id,
            'class_id' => $this->ids["$k.class.Grade 5"],
            'category_id' => $this->ids["$k.category"],
            'admission_date' => $today,
            'application_type' => 'online',
        ]);
    }

    private function money(SessionYear $current): void
    {
        $tuition = $this->row('fees_types', ['name' => 'Tuition'], ['description' => 'School fees for the year']);
        $transport = $this->row('fees_types', ['name' => 'Transport'], ['description' => 'School bus, optional']);

        foreach (self::TUITION_CENTS as $grade => $cents) {
            $class = (int) DB::table('classes')->where('name', $grade)->value('id');
            $this->row('fees_classes', ['session_year_id' => $current->id, 'class_id' => $class, 'fees_type_id' => $tuition], $this->amount($cents) + ['choiceable' => 0]);
            $this->row('fees_classes', ['session_year_id' => $current->id, 'class_id' => $class, 'fees_type_id' => $transport], $this->amount(self::TRANSPORT_CENTS) + ['choiceable' => 1]);
        }
    }

    private function calendar(SessionYear $past, SessionYear $current): void
    {
        $year = (int) Carbon::parse($current->start_date)->format('Y');
        foreach ([
            [$current, 'Welcome to the new term', 'Term 4 starts on Monday. Please check the updated timetable.'],
            [$current, 'Grade 5 outing to the science centre', 'Permission slips are due by Friday.'],
            [$past, 'Prize-giving '.($year - 1), 'Prize-giving takes place in the hall on the last Friday of the year.'],
        ] as [$sessionYear, $title, $description]) {
            $this->row('announcements', ['session_year_id' => $sessionYear->id, 'title' => $title], ['description' => $description, 'table_type' => '', 'table_id' => null]);
        }
        $this->row('holidays', ['session_year_id' => $current->id, 'title' => 'Heritage Day'], ['date' => "$year-09-24", 'description' => 'Public holiday']);
        $this->row('events', ['session_year_id' => $current->id, 'title' => 'Sports day'], [
            'type' => 'single', 'start_date' => "$year-11-14", 'end_date' => "$year-11-14",
            'start_time' => '08:00:00', 'end_time' => '13:00:00', 'description' => 'Athletics on the school field.',
        ]);
    }

    /**
     * An exam with a Mathematics paper for both grades dated yesterday (so the
     * teacher can capture marks; it stays unpublished) and the demo learner's
     * mark, and a Mathematics assignment for 5 A due in a week.
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

        $learner = (int) DB::table('students')->where('admission_no', "DEMO-$up-0001")->value('id');
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

    /** @return array<string, float|int> */
    private function amount(int $cents): array
    {
        $values = ['amount' => $cents / 100];
        if ($this->hasColumn('fees_classes', 'amount_cents')) {
            $values['amount_cents'] = $cents;
        }

        return $values;
    }

    /** @param array<string, mixed> $extra */
    private function user(string $login, string $first, string $last, string $role, array $extra = []): User
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
        $user->syncRoles([$role]);

        return $user;
    }

    /**
     * Find a row by its natural key and update it, or insert it.
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
        $existing = DB::table($table)->where($match)->value('id');
        if ($existing) {
            if ($values !== []) {
                DB::table($table)->where('id', $existing)->update($values + $now);
            }

            return (int) $existing;
        }

        return (int) DB::table($table)->insertGetId($match + $values + ($now ? $now + ['created_at' => now()] : []));
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
            foreach (DB::table('web_settings')->whereNotNull('image')->pluck('image') as $path) {
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
