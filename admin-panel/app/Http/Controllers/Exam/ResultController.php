<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin panel: view exam results.
 */
class ResultController extends Controller
{
    public function viewExamResult()
    {
        if (! Auth::user()->can('view-exam-result')) {
            return redirect(route('home'))->withErrors(['message' => trans('no_permission_message')]);
        }

        $systemDefaultSessionYearId = getSettings('session_year')['session_year'] ?? null;
        $currentSessionYearId = session('session_year', $systemDefaultSessionYearId);
         $session_years = SessionYear::orderBy('id', 'ASC')->get();
        
        $allowedSemesterMap = [];

        if (Auth::user()->hasRole('Super Admin')) {
            $classes = ClassSection::with([
                'class',
                'section',
                'class.medium',
                'class.streams',
                'streams',
                'class.allSubjects' => fn ($q) => $q
                    ->select('id', 'class_id', 'semester_id', 'session_year_id')
                    ->whereNotNull('semester_id')
                    ->when($currentSessionYearId, fn ($q) => $q->where('session_year_id', $currentSessionYearId)),
                'class.allSubjects.semester:id,name',
            ])->get();

            $allowedSemesterMap = $classes->mapWithKeys(fn ($cs) => [
                $cs->id => $cs->class->allSubjects
                    ->pluck('semester')
                    ->filter()
                    ->unique('id')
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
                    ->values()
                    ->toArray(),
            ])->filter()->toArray();
        } else {
            $teacher_id = Auth::user()->teacher->id;

            $classTeacherRecords = ClassTeacher::with('semester:id,name')
                ->where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $currentSessionYearId)
                ->get();

            $classes = ClassSection::with(['class', 'section', 'class.medium', 'class.streams', 'streams'])
                ->whereIn('id', $classTeacherRecords->pluck('class_section_id')->unique())
                ->get();

            $allowedSemesterMap = $classTeacherRecords
                ->filter(fn ($r) => ! is_null($r->semester_id) && $r->semester)
                ->groupBy('class_section_id')
                ->map(
                    fn ($group) => $group
                        ->map(fn ($r) => ['id' => $r->semester_id, 'name' => $r->semester->name])
                        ->unique('id')
                        ->values()
                        ->toArray()
                )
                ->toArray();
        }

        $classOptions = $classes->map(fn ($d) => [
            'id' => $d->id,
            'class_id' => $d->class->id,
            'label' => trim(
                $d->class->name.' - '.$d->section->name.' '.
                    $d->class->medium->name.' '.
                    ($d->class->streams->name ?? '')
            ),
        ]);

        return view('exams.view-result', compact(
            'classes',
            'session_years',
            'currentSessionYearId',
            'allowedSemesterMap',
            'classOptions'
        ));
    }

    public function getExamResultIndex()
    {
        if (! Auth::user()->can('exam-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
        $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        $class_ids = ClassSection::whereIn('id', $class_section_id)->pluck('class_id');
        $classes = ClassSection::with('class', 'section', 'class.medium', 'streams')->whereIn('id', $class_section_id)
            ->whereIn('class_id', $class_ids)->get();
         $session_years = SessionYear::orderBy('id', 'ASC')->get();
       

        // $exams = Exam::where('publish', 1)->get();

        return view('exams.show_exam_result', compact('classes', 'session_years', 'class_section_id'));
    }

    public function fetchExamResultList(Request $request)
    {
        if (! Auth::user()->can('view-exam-result')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
                'total' => 0,
                'rows' => [],
            ]);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'session_year_id' => 'required|numeric',
            'semester_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'total' => 0,
                'rows' => [],
            ]);
        }

        try {
            $offset = $request->get('offset', 0);
            $limit = $request->get('limit', 10);
            $sort = $request->get('sort', 'id');
            $order = $request->get('order', 'DESC');
            $search = $request->get('search');

            $examResults = ExamResult::with('exam')
                ->where([
                    'exam_id' => $request->exam_id,
                    'class_section_id' => $request->class_section_id,
                    'session_year_id' => $request->session_year_id,
                ])
                ->when($request->semester_id, function ($query) use ($request) {
                    $query->whereHas('exam', function ($q) use ($request) {
                        $q->where('semester_id', $request->semester_id);
                    });
                })
                ->get()
                ->keyBy('student_id');

            $sql = StudentSessions::with([
                'student.user',
                'class_section.class.medium',
                'class_section.class.streams',
                'class_section.section',
            ])
                ->where('session_year_id', $request->session_year_id)
                ->where('class_section_id', $request->class_section_id);

            /* ---------- SEARCH ---------- */
            if (! empty($search)) {
                $sql->whereHas('student', function ($q) use ($search) {
                    $q->where('admission_no', 'LIKE', "%$search%")
                        ->orWhere('roll_number', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($u) use ($search) {
                            $u->where('first_name', 'LIKE', "%$search%")
                                ->orWhere('last_name', 'LIKE', "%$search%")
                                ->orWhereRaw("concat(first_name,' ',last_name) LIKE ?", ["%$search%"])
                                ->orWhere('email', 'LIKE', "%$search%")
                                ->orWhere('dob', 'LIKE', "%$search%");
                        });
                });
            }

            $total = $sql->count();

            $results = $sql->orderBy($sort, $order)
                ->skip($offset)
                ->take($limit)
                ->get();

            /* ---------- RESPONSE ---------- */
            $rows = [];
            $no = 1;
            $dateFormat = getSettings('date_formate');

            foreach ($results as $session) {

                $student = $session->student;
                if (! $student) {
                    continue;
                }

                $exam = $examResults->get($student->id);

                $operate = '';
                if (Auth::user()->can('generate-result')) {
                    $operate = '<a href="'.route('generate.exam.result', [
                        'id' => $student->id,
                        'semester_id' => $request->semester_id,
                        'exam_id' => $request->exam_id,
                        'session_year_id' => $request->session_year_id,
                    ]).'" 
                        class="btn btn-xs btn-gradient-success btn-rounded btn-icon" 
                        data-id="'.$student->id.'" title="Generate Result">
                        <i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                }

                $rows[] = [
                    'id' => $student->id,
                    'no' => $no++,
                    'user_id' => $student->user_id,
                    'student_name' => $student->user->first_name.' '.$student->user->last_name,
                    'admission_no' => $student->admission_no,
                    'total_marks' => $exam->total_marks ?? null,
                    'obtained_marks' => $exam->obtained_marks ?? null,
                    'grade' => $exam->grade ?? null,
                    'percentage' => $exam->percentage ?? null,
                    'operate' => $operate,
                ];
            }

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'total' => 0,
                'rows' => [],
            ]);
        }
    }

    public function showExamResult(Request $request)
    {

        if (! Auth::user()->can('exam-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // Validate required parameters
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|exists:exams,id',
            'class_section_id' => 'required|exists:class_sections,id',
            'session_year_id' => 'required|numeric',
        ], [
            'exam_id.required' => 'Please select an exam.',
            'exam_id.exists' => 'Selected exam does not exist.',
            'class_section_id.required' => 'Please select a class section.',
            'class_section_id.exists' => 'Selected class section does not exist.',
            'session_year_id.required' => 'Please select a session year.',
        ]);

        if ($validator->fails()) {
            // Commented this code to prevent popup on Page load in teacher panel
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
                'total' => 0,
                'rows' => [],
            ]);
        }

        // Verify teacher has access to this class section
        $teacher_id = Auth::user()->teacher->id;
        $teacherClassSections = ClassTeacher::where('class_teacher_id', $teacher_id)
            ->pluck('class_section_id')
            ->toArray();

        if (! in_array($request->class_section_id, $teacherClassSections)) {
            return response()->json([
                'error' => true,
                'message' => 'You do not have access to this class section.',
                'total' => 0,
                'rows' => [],
            ]);
        }

        try {
            if ($request->exam_id) {
                $offset = 0;
                $limit = 10;
                $sort = 'id';
                $order = 'DESC';

                if (isset($_GET['offset'])) {
                    $offset = $_GET['offset'];
                }
                if (isset($_GET['limit'])) {
                    $limit = $_GET['limit'];
                }

                if (isset($_GET['sort'])) {
                    $sort = $_GET['sort'];
                }
                if (isset($_GET['order'])) {
                    $order = $_GET['order'];
                }

                $exam_timetable_id = ExamTimetable::where('session_year_id', $request->session_year_id)
                    ->where('exam_id', $request->exam_id)
                    ->pluck('id');

                if ($exam_timetable_id->isEmpty()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'No exam timetable found for the selected exam.',
                        'total' => 0,
                        'rows' => [],
                    ]);
                }

                $sql = ExamResult::where('session_year_id', $request->session_year_id)
                    ->with('student.user:id,first_name,last_name', 'session_year:id,name')
                    ->with(['student.exam_marks' => function ($q) use ($exam_timetable_id) {
                        $q->whereIn('exam_timetable_id', $exam_timetable_id)->with('timetable', 'subject:id,name');
                    }])
                    ->where(['exam_id' => $request->exam_id, 'class_section_id' => $request->class_section_id]);

                if (isset($_GET['search']) && ! empty($_GET['search'])) {
                    $search = $_GET['search'];
                    $sql = $sql->where(fn ($query) => $query->where('id', 'LIKE', "%$search%")
                        ->orwhere('total_marks', 'LIKE', "%$search%")
                        ->orwhere('grade', 'LIKE', "%$search%")
                        ->orwhere('obtained_marks', 'LIKE', "%$search%")
                        ->orwhere('percentage', 'LIKE', "%$search%")
                        ->orwhere('created_at', 'LIKE', '%'.convertDateFormat($search).'%')
                        ->orwhere('updated_at', 'LIKE', '%'.convertDateFormat($search).'%')
                        ->orWhereHas('student.user', function ($q) use ($search) {
                            $q->where('first_name', 'LIKE', "%$search%")->orWhere('last_name', 'LIKE', "%$search%");
                        })->where('exam_id', $request->exam_id)
                        ->orWhereHas('session_year', function ($q) use ($search) {
                            $q->where('name', 'LIKE', "%$search%");
                        }));
                }
                $total = $sql->count();

                $sql->orderBy($sort, $order)->skip($offset)->take($limit);
                $res = $sql->get();

                $bulkData = [];
                $bulkData['total'] = $total;
                $rows = [];
                $tempRow = [];
                $no = 1;
                foreach ($res as $row) {
                    $operate = '';
                    $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id="'.$row->id.'" data-student_id ="'.$row->student_id.'" title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';

                    $tempRow['id'] = $row->id;
                    $tempRow['no'] = $no++;
                    $tempRow['student_id'] = $row->student_id;
                    $tempRow['student_name'] = $row->student->user->first_name.' '.$row->student->user->last_name;
                    $tempRow['total_marks'] = $row->total_marks;
                    $tempRow['obtained_marks'] = $row->obtained_marks;
                    $tempRow['percentage'] = $row->percentage;
                    $tempRow['grade'] = $row->grade;
                    $tempRow['session_year_name'] = $row->session_year->name;
                    $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
                    $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
                    $tempRow['operate'] = $operate;
                    $tempRow['data'] = $row->student->exam_marks;
                    $rows[] = $tempRow;
                }

                $bulkData['rows'] = $rows;

                return response()->json($bulkData);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Exam ID is required.',
                    'total' => 0,
                    'rows' => [],
                ]);
            }
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'total' => 0,
                'rows' => [],
            ]);
        }
    }
}
