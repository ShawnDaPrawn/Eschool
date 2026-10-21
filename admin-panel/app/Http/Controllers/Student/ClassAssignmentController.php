<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Students;
use App\Models\StudentSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: assign students to a class section for a session year.
 */
class ClassAssignmentController extends Controller
{
    public function assignClass()
    {
        if (! Auth::user()->can('assign-class-to-new-student')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $class_section = ClassSection::with('class', 'section')->get();
        $class = ClassSchool::with('medium')->get();
        $category = Category::where('status', 1)->get();

        return view('students.assign-class', compact('class_section', 'class', 'category'));
    }

    public function newStudentList(Request $request)
    {
        if (! Auth::user()->can('assign-class-to-new-student')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $sort = 'id';
        $order = 'DESC';
        $class_id = $request->class_id;

        // Return empty result if no class is selected
        if (empty($class_id)) {
            return response()->json(['total' => 0, 'rows' => []]);
        }

        $get_class_section_id = ClassSection::where('class_id', $class_id)->pluck('id');

        // Return empty result if no class sections found for the selected class
        if ($get_class_section_id->isEmpty()) {
            return response()->json(['total' => 0, 'rows' => []]);
        }

        $currentSessionId = getSettings('session_year')['session_year'];

        $sql = StudentSessions::where('session_year_id', $currentSessionId)
            ->whereIn('class_section_id', $get_class_section_id)
            ->with([
                'student.user:id,first_name,last_name,image',
                'class_section.class',
                'class_section.section',
                'class_section.class.medium',
            ]);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($q) use ($search) {
                $q->whereHas('student', function ($query) use ($search) {
                    $query->where('admission_no', 'LIKE', "%$search%")
                        ->orWhere('roll_number', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%");
                        });
                });
            });
        }

        $total = $sql->count();
        $res = $sql->orderBy($sort, $order)->get();

        $rows = [];
        $no = 1;
        $data = getSettings('date_formate');
        foreach ($res as $sessionRow) {
            $student = $sessionRow->student;
            if (! $student) {
                continue;
            }
            $tempRow = [];
            $assign_student = '<input type="checkbox" class="assign_student"  name="assign_student" value='.$student->id.'>';
            $tempRow['chk'] = $assign_student;
            $tempRow['id'] = $student->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $student->user_id;
            $tempRow['first_name'] = $student->user->first_name;
            $tempRow['last_name'] = $student->user->last_name;
            $tempRow['image'] = $student->user->image;
            $tempRow['class_section_id'] = $sessionRow->class_section_id;
            $tempRow['class_section_name'] = $sessionRow->class_section->class->name.'-'.$sessionRow->class_section->section->name.' '.$sessionRow->class_section->class->medium->name;
            $tempRow['admission_no'] = $student->admission_no;
            $tempRow['roll_number'] = $student->roll_number;
            $tempRow['admission_date'] = date($data['date_formate'], strtotime(is_object($student->admission_date) ? $student->admission_date->toDateString() : $student->admission_date));
            $rows[] = $tempRow;
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function assignClass_store(Request $request)
    {
        if (! Auth::user()->can('assign-class-to-new-student')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'selected_id' => 'required',
        ], [
            'class_section_id.required' => trans('class_section_required'),
            'selected_id.required' => trans('please_select_at_least_one_student'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        DB::beginTransaction();

        try {
            $selected_students = explode(',', $request->selected_id);
            $class_section_id = $request->class_section_id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            foreach ($selected_students as $student_id) {

                $student = Students::find($student_id);

                if (! $student) {
                    continue; // skip invalid IDs safely
                }

                // Update student
                $student->update([
                    'class_section_id' => $class_section_id,
                    'is_new_admission' => 0,
                ]);

                // Update if exists, else create
                StudentSessions::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'session_year_id' => $session_year_id,
                    ],
                    [
                        'class_section_id' => $class_section_id,
                        'status' => 1,
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
            ]);
        } catch (\Exception $e) {

            DB::rollBack();
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function getClassSectionsBySessionYear(Request $request)
    {
        $session_year_id = $request->session_year_id;
        $user = Auth::user();

        if ($user->hasRole('Teacher')) {
            // Only show class sections where the teacher is assigned as class teacher for this session year
            $class_section_ids = ClassTeacher::where('class_teacher_id', $user->teacher->id)
                ->where('session_year_id', $session_year_id)
                ->pluck('class_section_id')
                ->toArray();

            $class_sections = ClassSection::with('class.medium', 'section', 'class.streams')
                ->whereIn('id', $class_section_ids)
                ->get();
        } else {
            $class_sections = ClassSection::with('class.medium', 'section', 'class.streams')->get();
        }

        $result = [];
        foreach ($class_sections as $section) {
            $result[] = [
                'id' => $section->id,
                'name' => $section->class->name.' '.$section->section->name.' '.$section->class->medium->name.' '.($section->class->streams->name ?? ''),
            ];
        }

        return response()->json($result);
    }

    public function getClassSectionByClass($class_id)
    {
        try {
            $classSection = ClassSection::where('class_id', $class_id)->with('class.medium', 'class.streams', 'section')->get();

            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $classSection,
            ];
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }
}
