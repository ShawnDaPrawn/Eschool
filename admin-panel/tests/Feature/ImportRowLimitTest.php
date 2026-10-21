<?php

namespace Tests\Feature;

use App\Imports\AttendanceImport;
use App\Imports\OnlineExamQuestionsImport;
use App\Imports\RowLimit;
use App\Imports\StudentsImport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * One uploaded spreadsheet with hundreds of thousands of rows used to tie up a
 * worker and its memory. Imports stop reading past RowLimit::MAX_ROWS rows.
 */
class ImportRowLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_limit_counts_data_rows_across_sheets(): void
    {
        $filter = new RowLimit;
        // A heading row plus MAX_ROWS rows is fine.
        $this->assertTrue($filter->readCell('A', RowLimit::MAX_ROWS + 1, 'Sheet1'));
        $this->assertTrue($filter->readCell('B', RowLimit::MAX_ROWS + 1, 'Sheet1'));

        // Every sheet is imported, so a second sheet's rows count too.
        $this->expectException(ValidationException::class);
        $filter->readCell('A', 2, 'Sheet2');
    }

    public function test_every_import_refuses_a_file_over_the_limit_before_saving_anything(): void
    {
        foreach ([
            [new AttendanceImport(1), AttendanceImport::class],
            [new OnlineExamQuestionsImport(1, 1), OnlineExamQuestionsImport::class],
            [new StudentsImport(1), StudentsImport::class],
        ] as [$import, $name]) {
            try {
                Excel::import($import, $this->csv(RowLimit::MAX_ROWS + 1));
                $this->fail("$name imported a file over the row limit");
            } catch (ValidationException $e) {
                $this->assertStringContainsString('more than '.RowLimit::MAX_ROWS.' rows', $e->validator->errors()->first());
            }
        }

        $this->assertSame(0, DB::table('attendances')->count());
        $this->assertSame(0, DB::table('online_exam_questions')->count());
        $this->assertSame(0, DB::table('students')->count());
    }

    public function test_the_upload_page_shows_why_the_file_was_refused(): void
    {
        $this->seedLikeInstaller();
        $admin = $this->superAdmin();
        $admin->givePermissionTo(['attendance-create', 'attendance-edit']);

        $this->actingAs($admin)->post('/attendance/store_bulk', [
            'class_section_id' => 1,
            'file' => $this->csv(RowLimit::MAX_ROWS + 1),
        ])->assertOk()->assertJson([
            'error' => true,
            'message' => 'The file has more than '.RowLimit::MAX_ROWS.' rows. Split it into smaller files and import them one at a time.',
        ]);
    }

    /** A CSV with a heading row and $rows data rows. */
    private function csv(int $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'import');
        $file = fopen($path, 'w');
        fwrite($file, "student_id,date,type\n");
        for ($i = 1; $i <= $rows; $i++) {
            fwrite($file, "$i,2026-01-01,1\n");
        }
        fclose($file);

        return new UploadedFile($path, 'sheet.csv', 'text/csv', null, true);
    }
}
