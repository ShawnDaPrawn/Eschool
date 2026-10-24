<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\ClassSchool;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\CurriculumPlans;
use App\Services\Academics\SasamsExport;
use App\Services\Academics\TeachingGroups;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;
use ZipArchive;

class SasamsExportTest extends TestCase
{
    use UsesTwoSchools;

    private int $year;

    private int $grade;

    private int $learner;

    private string $number;

    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        $this->learner = $this->schoolA->ids['asha'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Students::whereKey($this->learner)->update(['student_number' => '000007']);
        $this->number = '000007';
        Enrolment::create(['learner_id' => $this->learner, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic export fixture']);
        $this->beforeApplicationDestroyed(function () {
            foreach ($this->files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        });
    }

    private function layout(string $kind = 'learners'): array
    {
        return ['schema_version' => 1, 'school_id' => 1, 'session_year_id' => $this->year, 'kind' => $kind, 'format' => 'csv',
            'version' => 'synthetic-fixture-v1', 'source' => 'Synthetic test only; not an official import layout', 'import_menu' => 'Fixture import',
            'on_date' => '2026-02-10', 'delimiter' => ';', 'learner_refs' => [$this->number => '000042'],
            'columns' => [['header' => 'LearnerRef', 'field' => 'learner_ref'], ['header' => 'Surname', 'field' => 'last_name']]];
    }

    private function export(array $p): array
    {
        return app(SasamsExport::class)->generate($this->superAdmin(), $p);
    }

    public function test_dated_learners_use_explicit_leading_zero_references_and_signed_private_provenance(): void
    {
        $result = $this->export($this->layout());
        $this->assertStringStartsWith("LearnerRef;Surname\r\n000042;", $result['content']);
        $this->assertStringNotContainsString('Baobab', $result['content']);
        $this->assertSame(1, $result['manifest']['records']);
        $this->assertFalse($result['manifest']['target_import_verified']);
        $m = $result['manifest'];
        $hmac = $m['hmac_sha256'];
        unset($m['hmac_sha256']);
        $this->assertSame(hash_hmac('sha256', json_encode($m, JSON_THROW_ON_ERROR), config('app.key')), $hmac);
        $this->assertSame(1, Enrolment::count());
    }

    public function test_wrong_roles_and_school_contexts_cannot_export(): void
    {
        foreach (['tara_user', 'farid_user', 'asha_user'] as $key) {
            try {
                app(SasamsExport::class)->generate(User::findOrFail($this->schoolA->ids[$key]), $this->layout());
                $this->fail('Wrong role exported');
            } catch (HttpExceptionInterface $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $p = $this->layout();
        $p['school_id'] = 2;
        try {
            $this->export($p);
            $this->fail('Wrong school exported');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }

    public function test_foreign_year_id_is_not_resolved_in_the_current_school(): void
    {
        $p = $this->layout();
        $p['session_year_id'] = $this->schoolB->ids['session_year'];
        $this->expectException(ModelNotFoundException::class);
        $this->export($p);
    }

    public function test_missing_external_identity_never_falls_back_to_an_internal_id(): void
    {
        $p = $this->layout();
        $p['learner_refs'] = ['someone-else' => '0042'];
        $this->expectException(ValidationException::class);
        $this->export($p);
    }

    public function test_demographic_code_mapping_is_explicit_and_complete(): void
    {
        User::whereKey($this->schoolA->ids['asha_user'])->update(['gender' => 'Female']);
        $p = $this->layout();
        $p['columns'][] = ['header' => 'Gender code', 'field' => 'gender'];
        $p['value_maps'] = ['gender' => ['Female' => 'F-fixture']];
        $this->assertStringContainsString(';F-fixture', $this->export($p)['content']);
        $p['value_maps'] = ['gender' => ['Male' => 'M-fixture']];
        $this->expectException(ValidationException::class);
        $this->export($p);
    }

    private function register(string $state = 'late'): void
    {
        $r = AttendanceRegister::create(['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'grade_level_id' => $this->grade,
            'on_date' => '2026-02-10', 'scope_key' => 'homeroom:'.$this->schoolA->ids['section_a'], 'label' => 'Synthetic daily register']);
        AttendanceEntry::create(['attendance_register_id' => $r->id, 'learner_id' => $this->learner, 'state' => $state, 'source' => 'tap', 'captured_by' => $this->superAdmin()->id]);
    }

    private function attendance(): array
    {
        $p = $this->layout('attendance');
        $p['columns'] = [['header' => 'Ref', 'field' => 'learner_ref'], ['header' => 'Day', 'field' => 'on_date'], ['header' => 'Code', 'field' => 'attendance_code']];
        $p['attendance_codes'] = ['late' => 'L-fixture'];

        return $p;
    }

    public function test_daily_attendance_uses_explicit_state_mapping(): void
    {
        $this->register();
        $this->assertStringContainsString('000042;2026-02-10;L-fixture', $this->export($this->attendance())['content']);
    }

    public function test_unmarked_daily_attendance_is_not_assumed_present(): void
    {
        $this->expectException(ValidationException::class);
        $this->export($this->attendance());
    }

    public function test_unmapped_late_or_half_day_codes_fail_visibly(): void
    {
        $this->register('first_half');
        $this->expectException(ValidationException::class);
        $this->export($this->attendance());
    }

    private function task(): array
    {
        $term = $this->schoolA->ids['term'];
        $plan = app(CurriculumPlans::class)->save($this->superAdmin(), ['subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year,
            'title' => 'Synthetic plan', 'annual_components' => ['sba' => 10000], 'rounding' => 'half_up', 'absent_policy' => 'incomplete', 'topics' => [],
            'assessments' => [['term_id' => $term, 'components' => ['sba' => 10000], 'tasks' => [['name' => 'Fixture task', 'type' => 'test', 'weight_bp' => 10000,
                'annual_weight_bp' => 10000, 'component' => 'sba', 'annual_component' => 'sba', 'max_mark' => '20.00', 'due_on' => '2026-02-10', 'topic_keys' => [], 'questions' => []]]]]]);
        $plan = app(CurriculumPlans::class)->publish($this->superAdmin(), $plan->id, $plan->assessments->sole()->id, 1);
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Export group', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        TeachingGroupMembership::create(['teaching_group_id' => $group->id, 'learner_id' => $this->learner, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'mark_rule' => 'catch_up', 'reason' => 'Fixture']);
        $task = $plan->assessments->sole()->tasks->sole();
        AssessmentMark::create(['assessment_task_id' => $task->id, 'teaching_group_id' => $group->id, 'learner_id' => $this->learner, 'mark_units' => 1575, 'disposition' => 'recorded', 'captured_by' => $this->superAdmin()->id]);
        $p = $this->layout('marks');
        $p += ['task_id' => $task->id, 'group_id' => $group->id, 'task_ref' => '001', 'subject_code' => 'M-fixture'];
        $p['columns'] = [['header' => 'Ref', 'field' => 'learner_ref'], ['header' => 'Score', 'field' => 'score'], ['header' => 'Max', 'field' => 'maximum']];

        return $p;
    }

    public function test_task_scores_export_exact_decimals_and_absence_requires_mapping(): void
    {
        $p = $this->task();
        $this->assertStringContainsString('000042;15.75;20.00', $this->export($p)['content']);
        AssessmentMark::query()->update(['mark_units' => null, 'disposition' => 'absent']);
        try {
            $this->export($p);
            $this->fail('Absent became zero');
        } catch (ValidationException) {
            $p['mark_codes'] = ['absent' => '-1'];
            $this->assertStringContainsString('000042;-1;20.00', $this->export($p)['content']);
        }
    }

    public function test_missing_marks_never_export_as_zero(): void
    {
        $p = $this->task();
        AssessmentMark::query()->update(['mark_units' => null, 'disposition' => 'missing']);
        $this->expectException(ValidationException::class);
        $this->export($p);
    }

    private function template(array &$p): string
    {
        $p['format'] = 'xlsx';
        $p['sheet'] = 'Learners';
        $p['header_row'] = 2;
        $book = new Spreadsheet;
        $book->getActiveSheet()->setTitle('Learners')->fromArray(['Target fixture'], null, 'A1')->fromArray(array_column($p['columns'], 'header'), null, 'A2');
        $path = tempnam(sys_get_temp_dir(), 'sasams-test-');
        $this->files[] = $path;
        (new Xlsx($book))->save($path);
        $book->disconnectWorksheets();
        $p['template_sha256'] = hash_file('sha256', $path);

        return $path;
    }

    public function test_excel_preserves_template_metadata_and_text_references(): void
    {
        $p = $this->layout();
        $template = $this->template($p);
        $result = app(SasamsExport::class)->generate($this->superAdmin(), $p, $template);
        $output = tempnam(sys_get_temp_dir(), 'sasams-test-result-');
        $this->files[] = $output;
        file_put_contents($output, $result['content']);
        $book = IOFactory::load($output);
        $this->assertSame('Target fixture', $book->getActiveSheet()->getCell('A1')->getValue());
        $this->assertSame('000042', $book->getActiveSheet()->getCell('A3')->getValue());
        $book->disconnectWorksheets();
    }

    public function test_changed_template_hash_and_active_spreadsheet_values_are_refused(): void
    {
        $p = $this->layout();
        $template = $this->template($p);
        $p['template_sha256'] = str_repeat('a', 64);
        try {
            app(SasamsExport::class)->generate($this->superAdmin(), $p, $template);
            $this->fail('Template mismatch accepted');
        } catch (ValidationException) {
            $p = $this->layout();
            $p['learner_refs'][$this->number] = '=HYPERLINK("https://invalid.test")';
            $this->expectException(ValidationException::class);
            $this->export($p);
        }
    }

    public function test_template_formulas_are_rejected_before_loading(): void
    {
        $p = $this->layout();
        $template = $this->template($p);
        $book = IOFactory::load($template);
        $book->getActiveSheet()->setCellValue('A1', '=1+1');
        (new Xlsx($book))->save($template);
        $book->disconnectWorksheets();
        $p['template_sha256'] = hash_file('sha256', $template);
        $this->expectException(ValidationException::class);
        app(SasamsExport::class)->generate($this->superAdmin(), $p, $template);
    }

    public function test_template_macros_and_excessive_expansion_are_rejected(): void
    {
        foreach (['xl/vbaProject.bin' => 'fixture macro', 'xl/padding.xml' => str_repeat('x', 9 * 1024 * 1024)] as $name => $value) {
            $p = $this->layout();
            $template = $this->template($p);
            $zip = new ZipArchive;
            $zip->open($template);
            $zip->addFromString($name, $value);
            $zip->close();
            $p['template_sha256'] = hash_file('sha256', $template);
            try {
                app(SasamsExport::class)->generate($this->superAdmin(), $p, $template);
                $this->fail('Active or oversized workbook accepted');
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_csv_formula_headers_and_missing_selected_demographics_are_not_substituted(): void
    {
        $p = $this->layout();
        $p['columns'][0]['header'] = '=1+1';
        try {
            $this->export($p);
            $this->fail('Formula header accepted');
        } catch (ValidationException) {
            $p = $this->layout();
            $p['columns'][] = ['header' => 'Unrelated field', 'field' => 'score'];
            $this->expectException(ValidationException::class);
            $this->export($p);
        }
    }

    public function test_native_export_routes_reject_family_users_before_file_processing(): void
    {
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']));
        $this->get(route('academic-sasams.index'))->assertRedirect()->assertSessionHasErrors();
        $this->actingAs(User::findOrFail($this->schoolA->ids['farid_user']));
        $this->post(route('academic-sasams.download'))->assertRedirect()->assertSessionHasErrors();
    }

    public function test_native_panel_download_is_private_and_has_no_source_rows_in_manifest(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get(route('academic-sasams.index'))->assertOk()->assertSee('SA-SAMS exports');
        $response = $this->post(route('academic-sasams.download'), ['layout' => UploadedFile::fake()->createWithContent('layout.json', json_encode($this->layout()))]);
        $response->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $path = tempnam(sys_get_temp_dir(), 'sasams-zip-test-');
        $this->files[] = $path;
        file_put_contents($path, $response->getContent());
        $zip = new ZipArchive;
        $zip->open($path);
        $this->assertSame(2, $zip->numFiles);
        $this->assertStringNotContainsString('000042', $zip->getFromName('manifest.json'));
        $zip->close();
    }
}
