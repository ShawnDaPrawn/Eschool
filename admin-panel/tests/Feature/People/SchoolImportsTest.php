<?php

declare(strict_types=1);

namespace Tests\Feature\People;

use App\Imports\SchoolOnboarding\CsvSheet;
use App\Imports\SchoolOnboarding\LearnerImport;
use App\Imports\SchoolOnboarding\Values;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianInvitation;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\People\SchoolImports;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/** School onboarding imports (Action Plan 0.11). */
class SchoolImportsTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $grade = fn (int $order) => (int) DB::table('grade_levels')->where('sort_order', $order)->value('id');
        ClassSchool::query()->whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade(5)]);
        ClassSchool::query()->whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $grade(6)]);
    }

    public function test_homerooms_are_created_by_grade_and_letter_and_reused_when_they_exist(): void
    {
        $imports = app(SchoolImports::class);
        $csv = "Grade,Letter\n8,A\nGr 8,b\nR,A\n8,A\n13,A\n5,A\n";
        $check = $imports->check($this->superAdmin(), 'homerooms', $csv, null, []);

        $this->assertSame(['grade' => 'Grade', 'letter' => 'Letter'], $check['mapping']);
        $this->assertSame(['create', 'create', 'create', 'error', 'error', 'exists'], array_column($check['rows'], 'status'));
        $this->assertSame(['Grade 8 A', 'Grade 8 B', 'Grade R A'], array_slice(array_column($check['rows'], 'label'), 0, 3));
        $this->assertSame(['create' => 3, 'exists' => 1, 'transfer' => 0, 'error' => 2], $check['counts']);

        // Refused while rows have problems.
        try {
            $imports->commit($this->superAdmin(), 'homerooms', $csv, $check['mapping'], [], $check['fingerprint']);
            $this->fail('Imported a file with problems.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rows', $e->errors());
        }

        $csv = "Grade,Letter\n8,A\nGr 8,b\nR,A\n5,A\n";
        $check = $imports->check($this->superAdmin(), 'homerooms', $csv, null, []);
        $this->assertSame(['created' => 3], $imports->commit($this->superAdmin(), 'homerooms', $csv, $check['mapping'], [], $check['fingerprint']));
        $grade8 = ClassSchool::query()->where('name', 'Grade 8')->get();
        $this->assertCount(1, $grade8);
        $this->assertSame(8, (int) DB::table('grade_levels')->where('id', $grade8[0]->grade_level_id)->value('sort_order'));
        $this->assertSame(2, ClassSection::query()->where('class_id', $grade8[0]->id)->count());
        $this->assertSame(1, DB::table('class_session_configs')->where('class_id', $grade8[0]->id)->count());
        $this->assertTrue(DB::table('audit_log')->where('action', 'people.onboarding_import_committed')->exists());

        $again = $imports->check($this->superAdmin(), 'homerooms', $csv, null, []);
        $this->assertSame(['exists', 'exists', 'exists', 'exists'], array_column($again['rows'], 'status'));
    }

    public function test_sa_sams_style_headers_are_recognised(): void
    {
        $import = new LearnerImport;
        $mapping = app(SchoolImports::class)->suggest($import, ['Admission No', 'Surname', 'First Names', 'Date of Birth', 'Gender', 'Grade', 'Class', 'LURITS Number', 'Parent Email', 'Parent Surname', 'Parent First Name', 'Father Email']);

        $this->assertSame('Admission No', $mapping['admission_no']);
        $this->assertSame('First Names', $mapping['first_name']);
        $this->assertSame('Surname', $mapping['last_name']);
        $this->assertSame('Date of Birth', $mapping['dob']);
        $this->assertSame('Class', $mapping['homeroom']);
        $this->assertSame('Grade', $mapping['grade']);
        $this->assertSame('LURITS Number', $mapping['lurits']);
        $this->assertSame('Parent Email', $mapping['guardian_email']);
        $this->assertSame('Father Email', $mapping['guardian2_email']);
    }

    public function test_learners_are_checked_then_imported_with_guardians_enrolments_and_lurits(): void
    {
        $ids = $this->schoolA->ids;
        $holder = app(CurrentSchool::class)->run(2, function () {
            $learner = Students::query()->findOrFail($this->schoolB->ids['ben']);
            app(LuritsNumbers::class)->record($learner, '7700000001', 'sasams_import');

            return $learner;
        });
        $this->assertNotNull($holder);
        $csv = implode("\n", [
            'Admission No,First Names,Surname,Date of Birth,Gender,Class,LURITS Number,Parent First Name,Parent Surname,Parent Email,Parent Relationship,Guardian 2 First Name,Guardian 2 Surname,Guardian 2 Email',
            'A100,Thabo,Mokoena,09/03/2015,M,Grade 5 A,7700000002,Lindiwe,Mokoena,lindiwe@example.com,Mother,Farid,Demo,farid@example.com',
            'A101,Zanele,Dube,2015-07-21,F,6C,,Sipho,Dube,,,,,',
            'GR-ASHA,Asha,Demo,2015-06-01,F,Grade 5 A,,,,,,,,',
            'A102,Kagiso,Molefe,31/02/2015,M,Grade 5 A,,,,,,,,',
            'A103,Naledi,Khumalo,2015-01-01,F,Grade 9 Z,,,,,,,,',
            'A104,Pieter,Baobab-Twin,2015-01-01,M,Grade 5 A,7700000001,,,,,,,',
            'A100,Copy,Again,2015-01-01,M,Grade 5 A,,,,,,,,',
        ])."\n";
        $imports = app(SchoolImports::class);
        $schoolB = Students::acrossSchools()->where('school_id', self::SCHOOL_B_ID)->count();
        $check = $imports->check($this->superAdmin(), 'learners', $csv, null, ['starts_on' => now()->toDateString(), 'invite' => true]);
        $byLine = collect($check['rows'])->keyBy('line');

        $this->assertSame('create', $byLine[2]['status']);
        $this->assertSame(['create', 'Grade 6 C'], [$byLine[3]['status'], $byLine[3]['homeroom']]);
        $this->assertStringContainsString('Guardian 1 has no email address', implode(' ', $byLine[3]['notes']));
        $this->assertSame('exists', $byLine[4]['status']);
        $this->assertSame('error', $byLine[5]['status']);
        $this->assertStringContainsString('No homeroom "Grade 9 Z"', implode(' ', $byLine[6]['notes']));
        // Already a NavigateEd learner at another school: not imported, and not told where.
        $this->assertSame('transfer', $byLine[7]['status']);
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, json_encode($byLine[7]['notes']));
        $this->assertStringContainsString('already on line 2', implode(' ', $byLine[8]['notes']));

        // Only the good rows.
        $csv = implode("\n", array_slice(explode("\n", $csv), 0, 4))."\n";
        $check = $imports->check($this->superAdmin(), 'learners', $csv, null, ['starts_on' => now()->toDateString(), 'invite' => true]);
        $this->assertSame(['create' => 2, 'exists' => 1, 'transfer' => 0, 'error' => 0], $check['counts']);
        $counts = $imports->commit($this->superAdmin(), 'learners', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
        $this->assertSame(['created' => 2, 'guardians_linked' => 2, 'lurits_recorded' => 1, 'guardians_invited' => 2], $counts);

        $thabo = Students::query()->where('admission_no', 'A100')->firstOrFail();
        $this->assertSame(['Thabo', 'Mokoena', '2015-03-09', 'male'], [$thabo->user->first_name, $thabo->user->last_name, substr((string) $thabo->user->dob, 0, 10), $thabo->user->gender]);
        $this->assertTrue($thabo->user->hasRole('Student'));
        $this->assertSame($ids['section_a'], (int) Enrolment::query()->where('learner_id', $thabo->id)->value('class_section_id'));
        $this->assertSame('7700000002', app(LuritsNumbers::class)->forLearner($thabo));
        $lindiwe = User::query()->where('email', 'lindiwe@example.com')->firstOrFail();
        $this->assertTrue($lindiwe->hasRole('Parent'));
        $this->assertSame('mother', GuardianLink::query()->where('learner_id', $thabo->id)->where('guardian_user_id', $lindiwe->id)->value('relationship'));
        // Farid already had an account: linked, not duplicated.
        $this->assertSame(1, User::acrossSchools()->where('email', 'farid@example.com')->count());
        $this->assertTrue(GuardianLink::query()->where('learner_id', $thabo->id)->where('guardian_user_id', $ids['farid_user'])->exists());
        $this->assertSame(2, GuardianInvitation::query()->whereIn('guardian_user_id', [$lindiwe->id, $ids['farid_user']])->count());
        $zanele = Students::query()->where('admission_no', 'A101')->firstOrFail();
        $this->assertSame(0, GuardianLink::query()->where('learner_id', $zanele->id)->count());
        $this->assertSame($ids['section_c'], (int) Enrolment::query()->where('learner_id', $zanele->id)->value('class_section_id'));

        $this->assertSame($schoolB, Students::acrossSchools()->where('school_id', self::SCHOOL_B_ID)->count());

        // The same file again changes nothing.
        $again = $imports->check($this->superAdmin(), 'learners', $csv, null, []);
        $this->assertSame(['exists', 'exists', 'exists'], array_column($again['rows'], 'status'));
    }

    public function test_a_check_that_no_longer_matches_is_refused(): void
    {
        $imports = app(SchoolImports::class);
        $csv = "admission_no,first_name,last_name,homeroom\nA200,Lerato,Nkosi,Grade 5 A\n";
        $check = $imports->check($this->superAdmin(), 'learners', $csv, null, []);
        // Someone adds the learner by hand meanwhile.
        Students::query()->whereKey($this->schoolA->ids['ben'])->update(['admission_no' => 'A200']);

        $this->expectException(ValidationException::class);
        $imports->commit($this->superAdmin(), 'learners', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
    }

    public function test_reading_files_from_spreadsheet_programs(): void
    {
        $sheet = CsvSheet::read("\xEF\xBB\xBFGrade;Letter\r\n8;A\r\n\r\n9;B;\r\n");
        $this->assertSame(['Grade', 'Letter'], $sheet->headers);
        $this->assertCount(2, $sheet->rows);
        $this->assertSame('4', CsvSheet::read("Name\nA\n\nB\n")->rows[1][CsvSheet::LINE]);
        $this->assertSame('Zoë', CsvSheet::read("Name\n".mb_convert_encoding('Zoë', 'Windows-1252', 'UTF-8')."\n")->rows[0]['Name']);
        $this->assertSame('2015-03-09', Values::date('9.3.2015'));
        $this->assertSame('2015-03-09', Values::date('42072'));
        $this->assertNull(Values::date('2015-02-30'));

        $this->expectException(ValidationException::class);
        CsvSheet::read("Name\n".str_repeat("x\n", CsvSheet::MAX_ROWS + 1));
    }

    public function test_panel_checks_a_file_then_imports_it(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/school-imports?kind=learners')->assertOk()->assertSee('Import school data')->assertSee('Download a template');
        $this->actingAs($admin)->get('/school-imports/templates/learners')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertSee('Admission number');

        $file = UploadedFile::fake()->createWithContent('learners.csv', "Admission No,First Name,Surname,Homeroom\nA300,Ayanda,Zulu,Grade 5 A\n");
        $check = $this->actingAs($admin)->post('/school-imports/check', ['kind' => 'learners', 'file' => $file])
            ->assertOk()->assertSee('Will be imported')->assertSee('Ayanda Zulu (A300)');
        preg_match('/name="fingerprint" value="([0-9a-f]{64})"/', $check->getContent(), $fingerprint);
        preg_match('/name="starts_on"[^>]*value="([0-9-]{10})"/', $check->getContent(), $starts);

        $form = [
            'kind' => 'learners', 'mapped' => 1, 'csv' => "Admission No,First Name,Surname,Homeroom\nA300,Ayanda,Zulu,Grade 5 A\n",
            'mapping' => ['admission_no' => 'Admission No', 'first_name' => 'First Name', 'last_name' => 'Surname', 'homeroom' => 'Homeroom'],
            'session_year_id' => $this->schoolA->ids['session_year'], 'category_id' => $this->schoolA->ids['category'], 'starts_on' => $starts[1] ?? now()->toDateString(),
            'fingerprint' => $fingerprint[1],
        ];
        // As a browser sends the page back: CRLF line ends.
        $form['csv'] = str_replace("\n", "\r\n", $form['csv']);
        $this->actingAs($admin)->post('/school-imports', $form)->assertRedirect()->assertSessionHas('success');
        $this->assertTrue(Students::query()->where('admission_no', 'A300')->exists());

        // A wrong column choice is reported, not guessed.
        $this->actingAs($admin)->post('/school-imports/check', ['mapping' => ['admission_no' => 'Nope']] + $form)
            ->assertStatus(422)->assertSee('The file has no column &quot;Nope&quot;.', false);
    }

    public function test_staff_without_the_permissions_cannot_import(): void
    {
        $teacher = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $this->assertFalse($teacher->can('student-create'));
        $this->actingAs($teacher)->get('/school-imports')->assertForbidden();
        $this->actingAs($teacher)->post('/school-imports/check', ['kind' => 'learners', 'csv' => "a\nb\n"])->assertForbidden();
        $this->expectException(HttpException::class);
        app(SchoolImports::class)->check($teacher, 'homerooms', "Grade,Letter\n8,A\n", null, []);
    }
}
