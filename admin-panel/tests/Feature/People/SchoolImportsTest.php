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
use App\Models\Platform\Role;
use App\Models\Platform\StaffInvitation;
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
        $this->assertSame(['create' => 3, 'exists' => 1, 'transfer' => 0, 'error' => 2, 'lurits' => 0], $check['counts']);

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

    public function test_one_class_cannot_get_two_grades_from_the_same_file(): void
    {
        $check = app(SchoolImports::class)->check($this->superAdmin(), 'homerooms', "Grade,Letter,Class name\n8,A,Junior\n9,B,Junior\n", null, []);

        $this->assertSame(['create', 'error'], array_column($check['rows'], 'status'));
        $this->assertStringContainsString('already set up as a different grade', implode(' ', $check['rows'][1]['notes']));
    }

    public function test_a_lurits_number_claimed_elsewhere_during_the_import_rolls_it_back(): void
    {
        $csv = "admission_no,first_name,last_name,homeroom,lurits\nA400,Sizwe,Ndlovu,Grade 5 A,7700000099\n";
        $imports = app(SchoolImports::class);
        $check = $imports->check($this->superAdmin(), 'learners', $csv, null, []);
        $this->assertSame('create', $check['rows'][0]['status']);
        // Another school records the number between the check and the write.
        $this->partialMock(LuritsNumbers::class, fn ($mock) => $mock->shouldReceive('record')->andReturn(LuritsNumbers::CONFLICT));

        try {
            $imports->commit($this->superAdmin(), 'learners', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
            $this->fail('Imported a learner whose LURITS number was claimed elsewhere.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fingerprint', $e->errors());
        }
        $this->assertFalse(Students::query()->where('admission_no', 'A400')->exists());
    }

    public function test_an_sa_sams_learner_export_records_lurits_numbers_for_learners_already_here(): void
    {
        $ids = $this->schoolA->ids;
        $numbers = app(LuritsNumbers::class);
        // Ben at School B holds 7700000001; Cara here already has 7700000020.
        app(CurrentSchool::class)->run(2, fn () => $numbers->record(Students::query()->findOrFail($this->schoolB->ids['ben']), '7700000001', 'sasams_import'));
        $numbers->record(Students::query()->findOrFail($ids['cara']), '7700000020', 'school_entry');
        $schoolB = [DB::table('lurits_conflicts')->where('school_id', self::SCHOOL_B_ID)->count(),
            app(CurrentSchool::class)->run(2, fn () => $numbers->forLearner(Students::query()->findOrFail($this->schoolB->ids['ben'])))];
        $csv = implode("\n", [
            'Admission No,First Names,Surname,Class,LURITS Number',
            'GR-ASHA,Asha,Demo,Grade 5 A,7700 000 010',
            'GR-BEN,Ben,Demo,Grade 5 A,7700000001',
            'GR-CARA,Cara,Demo,Grade 6 C,7700000021',
            'A500,Lwazi,Mthembu,Grade 5 A,7700000011',
        ])."\n";
        $imports = app(SchoolImports::class);
        $options = ['starts_on' => now()->toDateString(), 'from_sasams' => true];
        $check = $imports->check($this->superAdmin(), 'learners', $csv, null, $options);
        $byLine = collect($check['rows'])->keyBy('line');

        $this->assertSame(['create' => 1, 'exists' => 1, 'transfer' => 0, 'error' => 0, 'lurits' => 2], $check['counts']);
        $this->assertSame('lurits', $byLine[2]['status']);
        $this->assertStringContainsString('Their LURITS number will be recorded', implode(' ', $byLine[2]['notes']));
        // Held at School B: told only that another learner has it, never where.
        $this->assertSame('lurits', $byLine[3]['status']);
        $this->assertStringContainsString('already registered to another learner', implode(' ', $byLine[3]['notes']));
        $this->assertStringNotContainsString(self::SCHOOL_B_MARKER, json_encode($check['rows']));
        $this->assertSame('exists', $byLine[4]['status']);
        $this->assertStringContainsString('different LURITS number, which is kept', implode(' ', $byLine[4]['notes']));
        $this->assertSame('create', $byLine[5]['status']);

        // A teacher cannot commit it: nothing written.
        try {
            $imports->commit(User::query()->findOrFail($ids['tara_user']), 'learners', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
            $this->fail('A teacher imported LURITS numbers.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame(1, DB::table('learner_identifiers')->whereNotNull('lurits_hash')->whereIn('user_id', Students::query()->select('user_id'))->count());
        $this->assertSame(0, DB::table('lurits_conflicts')->where('school_id', 1)->count());

        $counts = $imports->commit($this->superAdmin(), 'learners', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
        $this->assertSame(['created' => 1, 'guardians_linked' => 0, 'lurits_recorded' => 2, 'lurits_claimed' => 1, 'lurits_kept' => 0, 'guardians_invited' => 0], $counts);

        $asha = Students::query()->findOrFail($ids['asha']);
        $this->assertSame('7700000010', $numbers->forLearner($asha));
        $this->assertSame('sasams_import', DB::table('learner_identifiers')->where('user_id', $asha->user_id)->value('lurits_source'));
        $lwazi = Students::query()->where('admission_no', 'A500')->firstOrFail();
        $this->assertSame(['7700000011', 'sasams_import'], [$numbers->forLearner($lwazi), DB::table('learner_identifiers')->where('user_id', $lwazi->user_id)->value('lurits_source')]);
        // Ben here: not written, one open claim owned by this school.
        $this->assertNull($numbers->forLearner(Students::query()->findOrFail($ids['ben'])));
        $this->assertSame(1, DB::table('lurits_conflicts')->where('school_id', 1)->where('learner_id', $ids['ben'])->where('status', 'open')->count());
        // Cara's recorded number is kept.
        $this->assertSame('7700000020', $numbers->forLearner(Students::query()->findOrFail($ids['cara'])));
        // School B unchanged.
        $this->assertSame($schoolB, [DB::table('lurits_conflicts')->where('school_id', self::SCHOOL_B_ID)->count(),
            app(CurrentSchool::class)->run(2, fn () => $numbers->forLearner(Students::query()->findOrFail($this->schoolB->ids['ben'])))]);
        // Numbers never enter the audit log.
        $audit = DB::table('audit_log')->get()->toJson();
        foreach (['7700000010', '7700000001', '7700000011', '7700000021'] as $number) {
            $this->assertStringNotContainsString($number, $audit);
        }

        // The same file again changes nothing, and the claim is not raised twice.
        $again = $imports->check($this->superAdmin(), 'learners', $csv, null, $options);
        $this->assertSame(['exists', 'exists', 'exists', 'exists'], array_column($again['rows'], 'status'));
        $this->assertStringContainsString('waiting for Navigate Ed support', implode(' ', $again['rows'][1]['notes']));
    }

    public function test_a_lurits_number_the_office_records_after_the_check_is_kept_by_the_import(): void
    {
        $asha = Students::query()->findOrFail($this->schoolA->ids['asha']);
        $numbers = app(LuritsNumbers::class);
        $csv = "Admission No,First Name,Surname,Homeroom,LURITS Number\nGR-ASHA,Asha,Demo,Grade 5 A,7700000040\n";
        $check = app(SchoolImports::class)->check($this->superAdmin(), 'learners', $csv, null, ['from_sasams' => true]);
        $this->assertSame('lurits', $check['rows'][0]['status']);

        // The office records another number after the import's recheck, just
        // before the write: the import's own writes run with what it checked.
        $numbers->record($asha, '7700000041', 'school_entry');
        $audit = DB::table('audit_log')->count();
        $counts = (new LearnerImport)->commit($this->superAdmin(), $check['rows'], $check['options']);

        $this->assertSame(['created' => 0, 'guardians_linked' => 0, 'lurits_recorded' => 0, 'lurits_claimed' => 0, 'lurits_kept' => 1, 'guardians_invited' => 0], $counts);
        $this->assertSame('7700000041', $numbers->forLearner($asha));
        $this->assertSame('school_entry', DB::table('learner_identifiers')->where('user_id', $asha->user_id)->value('lurits_source'));
        $this->assertSame($audit, DB::table('audit_log')->count());
        $this->assertSame(0, DB::table('lurits_conflicts')->count());

        // Directly: kept when different, unchanged when the same, recorded when unset.
        $this->assertSame(LuritsNumbers::KEPT, $numbers->recordIfUnset($asha, '7700000042', 'sasams_import'));
        $this->assertSame(LuritsNumbers::UNCHANGED, $numbers->recordIfUnset($asha, '7700000041', 'sasams_import'));
        $ben = Students::query()->findOrFail($this->schoolA->ids['ben']);
        $this->assertSame(LuritsNumbers::RECORDED, $numbers->recordIfUnset($ben, '7700000043', 'sasams_import'));
        $this->assertSame('7700000041', $numbers->forLearner($asha));
        // The office page still corrects a number.
        $this->assertSame(LuritsNumbers::RECORDED, $numbers->record($asha, '7700000044', 'school_entry'));
    }

    public function test_the_panel_offers_the_sa_sams_source_and_imports_lurits_for_learners_already_here(): void
    {
        $csv = "Admission No,First Name,Surname,Homeroom,LURITS Number\nGR-ASHA,Asha,Demo,Grade 5 A,7700000030\n";
        $page = $this->actingAs($this->superAdmin())->post('/school-imports/check', ['kind' => 'learners', 'csv' => $csv, 'from_sasams' => 1])
            ->assertOk()->assertSee('Already here, LURITS number added')->assertSee('This is a learner list exported from SA-SAMS')->assertSee('Import 1 row');
        preg_match('/name="fingerprint" value="([0-9a-f]{64})"/', $page->getContent(), $fingerprint);
        preg_match('/name="starts_on"[^>]*value="([0-9-]{10})"/', $page->getContent(), $starts);
        $this->post('/school-imports', ['kind' => 'learners', 'mapped' => 1, 'csv' => $csv, 'from_sasams' => 1, 'fingerprint' => $fingerprint[1],
            'mapping' => ['admission_no' => 'Admission No', 'first_name' => 'First Name', 'last_name' => 'Surname', 'homeroom' => 'Homeroom', 'lurits' => 'LURITS Number'],
            'session_year_id' => $this->schoolA->ids['session_year'], 'category_id' => $this->schoolA->ids['category'], 'starts_on' => $starts[1]])
            ->assertRedirect()->assertSessionHas('success', '0 learners imported, 0 guardian links made, 0 guardians invited and 1 LURITS numbers recorded.');
        $this->assertSame('7700000030', app(LuritsNumbers::class)->forLearner(Students::query()->findOrFail($this->schoolA->ids['asha'])));
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
        $this->assertSame(['create' => 2, 'exists' => 1, 'transfer' => 0, 'error' => 0, 'lurits' => 0], $check['counts']);
        $counts = $imports->commit($this->superAdmin(), 'learners', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
        $this->assertSame(['created' => 2, 'guardians_linked' => 2, 'lurits_recorded' => 1, 'lurits_claimed' => 0, 'lurits_kept' => 0, 'guardians_invited' => 2], $counts);

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

    public function test_staff_are_added_with_their_roles_and_invited_to_set_their_own_password(): void
    {
        Role::query()->create(['name' => 'Bursar', 'guard_name' => 'web', 'custom_role' => 1]);
        $csv = implode("\n", [
            'First Name,Surname,Email,Role,Mobile,Qualification',
            'Nomvula,Khoza,nomvula@example.com,teacher,082 123 4567,BEd',
            'Pieter,Smit,pieter@example.com,Bursar,,',
            'Tara,Demo,tara@example.com,Teacher,,',
            'Sam,Root,sam@example.com,Super Admin,,',
            'Copy,Again,NOMVULA@example.com,Teacher,,',
            'Farid,Demo,farid@example.com,Teacher,,',
        ])."\n";
        $imports = app(SchoolImports::class);
        $check = $imports->check($this->superAdmin(), 'staff', $csv, null, ['invite' => true]);

        $this->assertSame(['create', 'create', 'exists', 'error', 'error', 'create'], array_column($check['rows'], 'status'));
        $this->assertStringContainsString('No role "Super Admin"', implode(' ', $check['rows'][3]['notes']));

        $csv = implode("\n", array_merge(array_slice(explode("\n", $csv), 0, 4), ['Farid,Demo,farid@example.com,Teacher,,']))."\n";
        $check = $imports->check($this->superAdmin(), 'staff', $csv, null, ['invite' => true]);
        $counts = $imports->commit($this->superAdmin(), 'staff', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
        $this->assertSame(['created' => 3, 'invited' => 3], $counts);

        $nomvula = User::query()->where('email', 'nomvula@example.com')->firstOrFail();
        $this->assertTrue($nomvula->hasRole('Teacher'));
        $this->assertTrue((bool) $nomvula->password_change_required);
        $this->assertSame('0821234567', $nomvula->mobile);
        $this->assertSame('BEd', DB::table('teachers')->where('user_id', $nomvula->id)->value('qualification'));
        $pieter = User::query()->where('email', 'pieter@example.com')->firstOrFail();
        $this->assertTrue($pieter->hasRole('Bursar'));
        $this->assertTrue(DB::table('staffs')->where('user_id', $pieter->id)->exists());
        // Farid's existing account gets the role here; it is not duplicated.
        $this->assertSame(1, User::acrossSchools()->where('email', 'farid@example.com')->count());
        $this->assertTrue(User::query()->findOrFail($this->schoolA->ids['farid_user'])->hasRole('Teacher'));
        $this->assertSame(3, StaffInvitation::query()->whereIn('user_id', [$nomvula->id, $pieter->id, $this->schoolA->ids['farid_user']])->count());

        $again = $imports->check($this->superAdmin(), 'staff', $csv, null, []);
        $this->assertSame(['exists', 'exists', 'exists', 'exists'], array_column($again['rows'], 'status'));
    }

    private function staffWriteCounts(): array
    {
        return array_map(fn (string $table) => DB::table($table)->count(), ['users', 'school_memberships', 'staff_invitations', 'teachers', 'staffs']);
    }

    public function test_staff_create_alone_cannot_import_teachers(): void
    {
        $actor = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $actor->givePermissionTo('staff-create');
        $this->assertFalse($actor->can('teacher-create'));
        $csv = "First Name,Surname,Email,Role\nThandi,Test,restricted-teacher@example.com,Teacher\n";
        $before = $this->staffWriteCounts();
        $imports = app(SchoolImports::class);
        $check = $imports->check($actor, 'staff', $csv, null, ['invite' => true]);
        $this->assertSame('error', $check['rows'][0]['status']);
        try {
            $imports->commit($actor, 'staff', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
            $this->fail('Imported a teacher without teacher-create.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rows', $e->errors());
        }
        $this->assertSame($before, $this->staffWriteCounts());
    }

    public function test_a_foreign_schools_custom_role_cannot_be_imported(): void
    {
        app(CurrentSchool::class)->run(2, fn () => Role::query()->create(['name' => 'Foreign role', 'guard_name' => 'web', 'custom_role' => 1]));
        $before = $this->staffWriteCounts();
        $csv = "First Name,Surname,Email,Role\nThandi,Test,foreign-role@example.com,Foreign role\n";
        $imports = app(SchoolImports::class);
        $check = $imports->check($this->superAdmin(), 'staff', $csv, null, ['invite' => true]);
        $this->assertSame('error', $check['rows'][0]['status']);
        try {
            $imports->commit($this->superAdmin(), 'staff', $csv, $check['mapping'], $check['options'], $check['fingerprint']);
            $this->fail('Granted another school role.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('rows', $e->errors());
        }
        $this->assertSame($before, $this->staffWriteCounts());
    }

    public function test_a_staff_import_check_cannot_be_replayed_by_another_actor_or_school(): void
    {
        Role::query()->create(['name' => 'Import helper', 'guard_name' => 'web', 'custom_role' => 1]);
        app(CurrentSchool::class)->run(2, fn () => Role::query()->create(['name' => 'Import helper', 'guard_name' => 'web', 'custom_role' => 1]));
        $actor = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $actor->givePermissionTo('staff-create');
        $other = app(CurrentSchool::class)->run(2, function () {
            $user = User::query()->findOrFail($this->schoolB->ids['tara_user']);
            $user->givePermissionTo('staff-create');

            return $user;
        });
        $csv = "First Name,Surname,Email,Role\nThandi,Test,replayed-import@example.com,Import helper\n";
        $imports = app(SchoolImports::class);
        $check = $imports->check($this->superAdmin(), 'staff', $csv, null, ['invite' => true]);
        $this->assertSame('create', $check['rows'][0]['status']);
        $before = $this->staffWriteCounts();
        foreach ([$actor, $other] as $index => $caller) {
            try {
                app(CurrentSchool::class)->run($index === 0 ? 1 : 2, fn () => $imports->commit($caller, 'staff', $csv, $check['mapping'], $check['options'], $check['fingerprint']));
                $this->fail('Replayed another actor/school check.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('fingerprint', $e->errors());
            }
            $this->assertSame($before, $this->staffWriteCounts());
        }
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

        // A Windows-1252 file comes back from the page as UTF-8 and still matches its check.
        $latin = mb_convert_encoding("Admission No,First Name,Surname,Homeroom\nA301,Zoë,Smit,Grade 5 A\n", 'Windows-1252', 'UTF-8');
        $page = $this->actingAs($admin)->post('/school-imports/check', ['kind' => 'learners', 'file' => UploadedFile::fake()->createWithContent('latin.csv', $latin)])
            ->assertOk()->assertSee('Zoë Smit (A301)');
        preg_match('/<textarea name="csv"[^>]*>([^<]*)<\/textarea>/', $page->getContent(), $hidden);
        preg_match('/name="fingerprint" value="([0-9a-f]{64})"/', $page->getContent(), $latinFingerprint);
        $this->actingAs($admin)->post('/school-imports', ['csv' => str_replace("\n", "\r\n", html_entity_decode($hidden[1], ENT_QUOTES)), 'fingerprint' => $latinFingerprint[1]] + $form)
            ->assertRedirect()->assertSessionHas('success');
        $this->assertSame('Zoë', Students::query()->where('admission_no', 'A301')->firstOrFail()->user->first_name);

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
        app(SchoolImports::class)->check($teacher, 'staff', "First name,Surname,Email,Role\nA,B,a@example.com,Teacher\n", null, []);
    }
}
