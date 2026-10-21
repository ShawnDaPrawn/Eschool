<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Students;
use App\Models\StudentSessions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: give the students of a class section their roll numbers.
 */
class RollNumberController extends Controller
{
    public function indexStudentRollNumber()
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $user = Auth::user();
        $class_section = collect();

        if ($user->hasRole('Teacher') && $user->teacher) {
            $teacher = $user->teacher;
            // Get all class sections this teacher teaches
            $class_section_ids = $teacher->classSections()->pluck('class_sections.id');
            $class_section = ClassSection::with('class', 'section')->whereIn('id', $class_section_ids)->get();
        } else {
            $class_section = ClassSection::with('class', 'section')->get();
        }

        // Find the first class section that has students
        $defaultClassSection = null;
        foreach ($class_section as $cs) {
            if ($cs->students()->count() > 0) {
                $defaultClassSection = $cs;
                break;
            }
        }

        return view('students.assign_roll_no', compact('class_section', 'defaultClassSection'));
    }

    public function listStudentRollNumber(Request $request, $class_section_id = null)
    {
        if (! Auth::user()->can('student-create')) {
            return redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            if (! Auth::user()->can('student-list')) {
                return response()->json([
                    'message' => trans('no_permission_message'),
                ]);
            }

            $class_section_id = $class_section_id ?? $request->class_section_id;
            $session_year_id = getSettings('session_year')['session_year'];
            $sql = StudentSessions::with(['student.user'])
                ->where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id);

            /* -------------------- SEARCH -------------------- */
            if ($request->filled('search')) {

                $search = $request->search;

                $sql->where(function ($query) use ($search) {

                    $query->whereHas('student.user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%{$search}%")
                            ->orWhere('last_name', 'LIKE', "%{$search}%")
                            ->orWhere('email', 'LIKE', "%{$search}%")
                            ->orWhere('dob', 'LIKE', "%{$search}%");
                    })

                        ->orWhereHas('student', function ($q) use ($search) {
                            $q->where('admission_no', 'LIKE', "%{$search}%")
                                ->orWhere('roll_number', 'LIKE', "%{$search}%");
                        });
                });
            }

            $res = $sql->get();

            /* -------------------- SORTING -------------------- */
            if ($request->sort_by == 'first_name') {
                $res = $res->sortBy(fn ($item) => optional($item->student->user)->first_name);
            }

            if ($request->sort_by == 'last_name') {
                $res = $res->sortBy(fn ($item) => optional($item->student->user)->last_name);
            }

            $res = $res->values(); // reset indexes after sort

            $total = $res->count();

            /* -------------------- RESPONSE BUILD -------------------- */

            $bulkData = [];
            $bulkData['total'] = $total;

            $rows = [];
            $no = 1;
            $roll = 1;
            $index = 0;

            $data = getSettings('date_formate');

            foreach ($res as $row) {

                $student = $row->student;
                $user = $student->user;

                $tempRow = [];

                $tempRow['no'] = $no++;
                $tempRow['student_id'] = $student->id;
                $tempRow['old_roll_number'] = $student->roll_number;

                $tempRow['new_roll_number'] =
                    "<input type='hidden' name='roll_number_data[".$index."][student_id]' value='".$student->id."'>
                 <input type='hidden' name='roll_number_data[".$index."][roll_number]' value='".$roll."'>".$roll;

                $tempRow['user_id'] = $row->id;
                $tempRow['full_name'] = $user->full_name;
                $tempRow['first_name'] = $user->first_name;
                $tempRow['last_name'] = $user->last_name;

                $tempRow['dob'] = $user->dob
                    ? date($data['date_formate'], strtotime($user->dob))
                    : null;

                $tempRow['image'] = $user->image;
                $tempRow['admission_no'] = $student->admission_no;

                $tempRow['admission_date'] = $student->admission_date
                    ? $student->admission_date->format($data['date_formate'])
                    : null;

                $rows[] = $tempRow;

                $index++;
                $roll++;
            }

            $bulkData['rows'] = $rows;

            return response()->json($bulkData);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function storeStudentRollNumber(Request $request)
    {
        if (! Auth::user()->can('student-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make(
            $request->all(),
            [
                'roll_number_data.*.roll_number' => 'required',
            ],
            [
                'roll_number_data.*.roll_number.required' => trans('please_fill_all_roll_numbers_data'),
            ]
        );
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        $i = 1;
        if (! is_null($request->roll_number_data)) {
            foreach ($request->roll_number_data as $data) {
                $student = Students::find($data['student_id']);

                // validation required when the edit of roll number is enabled

                // $class_roll_number_data = Students::where(['class_section_id' => $student->class_section_id,'roll_number' => $data['roll_number']])->whereNot('id',$data['student_id'])->count();
                // if(isset($class_roll_number_data) && !empty($class_roll_number_data)){
                //     $response = array(
                //         'error' => true,
                //         'message' => trans('roll_number_already_exists_of_number').' - '.$i
                //     );
                //     return response()->json($response);
                // }

                $student->roll_number = $data['roll_number'];
                $student->save();
                $i++;
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } else {
            $response = [
                'error' => true,
                'message' => trans('no_data_found'),
            ];
        }

        return response()->json($response);
    }
}
