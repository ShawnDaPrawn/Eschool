<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Students;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Admin panel: add many students at once from a spreadsheet.
 */
class BulkImportController extends Controller
{
    public function createBulkData()
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $class_section = ClassSection::with('class', 'section')->get();
        // $category = Category::where('status', 1)->get();
        // $data = getSettings('session_year');
        // $session_year = SessionYear::select('name')->where('id', $data['session_year'])->pluck('name')->first();
        // $get_student = Students::select('id')->latest('id')->pluck('id')->first();
        // $admission_no = $session_year . ($get_student + 1);

        return view('students.add_bulk_data', compact('class_section'));
    }

    public function downloadSampleFile()
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // Built here rather than shipped as a file (which never existed on a fresh
        // install), so the columns always match what the importer reads. Phone
        // numbers start with ' so spreadsheets keep their leading zero as text;
        // the importer strips it.
        $examples = [
            [
                'first_name' => 'Asha', 'last_name' => 'Naidoo', 'gender' => 'Female', 'dob' => '2015-03-14',
                'mobile' => "'0821234567", 'category' => 'General', 'admission_date' => date('Y-m-d'),
                'blood_group' => 'O+', 'height' => '140', 'wieght' => '35', 'current_address' => '12 Main Road', 'permanent_address' => '12 Main Road',
                'parents' => 'yes',
                'father_first_name' => 'Ravi', 'father_last_name' => 'Naidoo', 'father_email' => 'ravi.naidoo@example.com',
                'father_mobile' => "'0831234567", 'father_dob' => '1985-06-01', 'father_occupation' => 'Engineer',
                'mother_first_name' => 'Priya', 'mother_last_name' => 'Naidoo', 'mother_email' => 'priya.naidoo@example.com',
                'mother_mobile' => "'0841234567", 'mother_dob' => '1987-09-20', 'mother_occupation' => 'Teacher',
                'guardian' => 'no',
            ],
            [
                'first_name' => 'Thabo', 'last_name' => 'Mokoena', 'gender' => 'Male', 'dob' => '2014-11-02',
                'mobile' => "'0721234567", 'category' => 'General', 'admission_date' => date('Y-m-d'),
                'blood_group' => 'A+', 'height' => '145', 'wieght' => '38', 'current_address' => '8 Oak Street', 'permanent_address' => '8 Oak Street',
                'parents' => 'no',
                'guardian' => 'yes',
                'guardian_first_name' => 'Lerato', 'guardian_last_name' => 'Mokoena', 'guardian_email' => 'lerato.mokoena@example.com',
                'guardian_mobile' => "'0731234567", 'guardian_dob' => '1980-02-15', 'guardian_gender' => 'Female',
                'guardian_occupation' => 'Nurse',
            ],
        ];

        return response()->streamDownload(function () use ($examples) {
            $out = fopen('php://output', 'w');
            fputcsv($out, StudentsImport::COLUMNS);
            foreach ($examples as $example) {
                fputcsv($out, array_map(fn ($column) => $example[$column] ?? '', StudentsImport::COLUMNS));
            }
            fclose($out);
        }, 'students_sample.csv', ['Content-Type' => 'text/csv']);
    }

    public function storeBulkData(Request $request)
    {
        if (! Auth::user()->can('student-create') || ! Auth::user()->can('student-edit')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'file' => 'required|mimes:csv,txt,xlsx,xls',
        ], [
            'file.mimes' => trans('bulk_student_file_mimes'),
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $class_section_id = $request->class_section_id;
            Excel::import(new StudentsImport($class_section_id), $request->file);
            ResponseService::successResponse(trans('data_store_successfully'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors from the import
            $errors = $e->errors();
            $firstError = collect($errors)->flatten()->first();
            ResponseService::errorResponse($firstError, null, 422);
        } catch (Throwable $e) {
            $message = 'Oops! Something went wrong.';
            $exceptionMessage = strtolower((string) $e->getMessage());
            if (str_contains($exceptionMessage, 'file') && (str_contains($exceptionMessage, 'xlsx') || str_contains($exceptionMessage, 'csv') || str_contains($exceptionMessage, 'xls'))) {
                $message = trans('please_select_valid_file');
            }
            ResponseService::errorResponse($message, null, 103, $e);
        }
    }
}
