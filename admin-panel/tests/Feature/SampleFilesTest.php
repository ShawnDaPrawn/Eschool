<?php

namespace Tests\Feature;

use App\Imports\OnlineExamQuestionsImport;
use App\Imports\StudentsImport;
use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

/**
 * The "download sample file" buttons on the bulk import pages pointed at files
 * that were never shipped, so they failed on every install.
 */
class SampleFilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_online_exam_sample_can_be_imported_as_downloaded(): void
    {
        $this->seedLikeInstaller();

        $response = $this->actingAs($this->teacher())->get(route('online-exam-question.download-sample'));
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertSame(implode(',', OnlineExamQuestionsImport::COLUMNS), strtok($csv, "\n"));

        $path = tempnam(sys_get_temp_dir(), 'sample');
        file_put_contents($path, $csv);
        // Only the file format is under test, not a full class/subject setup.
        Schema::disableForeignKeyConstraints();
        Excel::import(new OnlineExamQuestionsImport(1, 1), new UploadedFile($path, 'sample.csv', 'text/csv', null, true));
        Schema::enableForeignKeyConstraints();
        unlink($path);

        $this->assertSame(3, OnlineExamQuestion::count());
        $this->assertSame(4, OnlineExamQuestionAnswer::count());
    }

    public function test_student_sample_passes_the_importers_validation(): void
    {
        $this->seedLikeInstaller();

        $response = $this->actingAs($this->superAdmin())->get(route('students.download-sample'));
        $response->assertOk();
        $lines = array_values(array_filter(explode("\n", $response->streamedContent())));
        $this->assertSame(StudentsImport::COLUMNS, str_getcsv($lines[0]));

        $import = new StudentsImport(1);
        $rows = [];
        foreach (array_slice($lines, 1) as $index => $line) {
            $rows[] = $import->prepareForValidation(array_combine(StudentsImport::COLUMNS, str_getcsv($line)), $index);
        }
        $this->assertCount(2, $rows);
        $validator = Validator::make($rows, $import->rules());
        $this->assertSame([], $validator->errors()->all());
    }

    private function teacher(): User
    {
        $teacher = User::create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher.test@example.com',
            'password' => Hash::make('secret123'),
            'gender' => 'Male',
            'mobile' => '0000000000',
        ]);
        $teacher->assignRole('Teacher');
        Staff::forceCreate(['user_id' => $teacher->id]);

        return $teacher;
    }
}
