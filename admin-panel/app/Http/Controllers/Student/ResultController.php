<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Grade;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: student result cards and exam results.
 */
class ResultController extends Controller
{
    public function resultIndex()
    {
        if (! Auth::user()->can('generate-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $is_teacher = Auth::user()->hasRole('Teacher');
        $classes = [];

        if (! $is_teacher) {
            $classes = ClassSection::with('class', 'section', 'class.medium', 'streams')->get();
        }

        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return view('students.generate_result', compact('classes', 'session_years', 'is_teacher'));
    }

    public function generateResult(Request $request, $id)
    {
        if (! Auth::user()->can('generate-result')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $father_name = null;
        $mother_name = null;
        $guardian_name = null;
        $examarray = [];
        $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

        $settings = getSettings();
        $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();

        $student = StudentSessions::with(
            'student.user:id,first_name,last_name,dob',
            'class_section.class:id,name,medium_id,stream_id',
            'class_section.class.medium:id,name',
            'class_section.class.streams:id,name',
            'class_section.section:id,name',
            'student.father:id,first_name,last_name',
            'student.guardian:id,first_name,last_name'
        )
            ->where('student_id', $id)
            ->where('session_year_id', $settings['session_year'])
            ->first();

        if (! $student) {
            return redirect()->back()->withErrors(trans('no_data_found'));
        }

        $student_name = $student->student->user->first_name.' '.$student->student->user->last_name;

        if ($student->student->father) {
            $father_name = $student->student->father->first_name.' '.$student->student->father->last_name;
        }

        if ($student->student->guardian) {
            $guardian_name = $student->student->guardian->first_name.' '.$student->student->guardian->last_name;
        }
        $admission_date = $student->student->admission_date;
        $gr_no = $student->student->admission_no;
        $dob = date('d-m-Y', strtotime($student->student->user->dob));
        $roll_number = $student->student->roll_number;
        $class_section = $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name.' '.($student->class_section->class->streams->name ?? '');

        $class_id = $student->class_section->class->id;

        $student_subject = $student->student->subjects(true);
        $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
        $elective_subjects = $student_subject['elective_subject'] ?? [];
        if ($elective_subjects) {
            $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
        }
        $subject_id = array_merge($core_subjects, $elective_subjects);

        $subjects = Subject::whereIn('id', $subject_id)->get();

        $exams = Exam::with(['exam_classes' => function ($q) use ($class_id) {
            $q->where('class_id', $class_id);
        }])
            ->with(['timetable' => function ($q) use ($class_id, $subject_id) {
                $q->where('class_id', $class_id)->whereIn('subject_id', $subject_id);
            }])
            ->where('session_year_id', $settings['session_year'])
            ->where('publish', 1)
            ->whereHas('timetable', function ($q) use ($class_id, $subject_id) {
                $q->where('class_id', $class_id)->whereIn('subject_id', $subject_id);
            })->get();

        $examarray = [];

        foreach ($exams as $exam) {
            $timetable = $exam->timetable;

            $filtered_timetable = [];

            foreach ($timetable as $exam_timetable) {
                if (in_array($exam_timetable->subject_id, $subject_id)) {
                    $exam_marks = ExamMarks::where('exam_timetable_id', $exam_timetable->id)
                        ->where('student_id', $student->student_id)
                        ->where('session_year_id', $settings['session_year'])
                        ->first();

                    $filtered_timetable[] = [
                        'id' => $exam_timetable->id,
                        'exam_id' => $exam_timetable->exam_id,
                        'class_id' => $exam_timetable->class_id,
                        'subject_id' => $exam_timetable->subject_id,
                        'total_marks' => $exam_timetable->total_marks,
                        'passing_marks' => $exam_timetable->passing_marks,
                        'session_year' => $exam_timetable->session_year_id,
                        'exam_marks' => $exam_marks,
                    ];
                }
            }

            if (! empty($filtered_timetable)) {
                $examarray[] = [
                    'id' => $exam->id,
                    'name' => $exam->name,
                    'publish' => $exam->publish,
                    'timetable' => $filtered_timetable,
                ];
            }
        }

        $subjectMarks = [];
        $totalMarks = 0;
        $obtainmarks = 0;

        foreach ($subjects as $subject) {
            $examObtainedMarks = null;
            $examTotalMarks = null;
            $subjectGrade = null;
            $subjectType = $subject->type;

            foreach ($examarray as $exam_data) {
                if ($exam_data['timetable']) {
                    foreach ($exam_data['timetable'] as $timetable) {
                        if ($timetable['subject_id'] == $subject->id) {
                            $exam_marks = $timetable['exam_marks'];
                            if ($exam_marks) {
                                $ObtainedMarks = $exam_marks['obtained_marks'];
                                $totalMarks += $timetable['total_marks'];

                                $examObtainedMarks += $ObtainedMarks;
                                $examTotalMarks += $timetable['total_marks'];

                                $subjectMarks[$subject->name.' ('.$subjectType.')'][$exam_data['name']] = $ObtainedMarks.'/'.$timetable['total_marks'];

                                if ($examTotalMarks > 0) {
                                    $percent = round(($examObtainedMarks / $examTotalMarks) * 100, 2);
                                    $grade_percent = round($percent);
                                    $subjectGrade = Grade::where('starting_range', '<=', $grade_percent)
                                        ->where('ending_range', '>=', $grade_percent)
                                        ->pluck('grade')
                                        ->first();

                                } else {
                                    $subjectGrade = null;
                                }
                            }
                        }
                    }
                }
            }

            $subjectMarks[$subject->name.' ('.$subjectType.')']['total_obtained'] = $examObtainedMarks;
            $subjectMarks[$subject->name.' ('.$subjectType.')']['total_marks'] = $examTotalMarks;
            $subjectMarks[$subject->name.' ('.$subjectType.')']['grade'] = $subjectGrade;

            $obtainmarks += $examObtainedMarks;
        }

        if ($totalMarks > 0) {
            $percentage = round(($obtainmarks / $totalMarks) * 100, 2);
            $grade_percentage = round($percentage);
            $grade = Grade::where('starting_range', '<=', $grade_percentage)
                ->where('ending_range', '>=', $grade_percentage)
                ->pluck('grade')
                ->first();
            $result = ($grade_percentage >= 40) ? 'Passed' : 'Failed';
        } else {
            $percentage = null;
            $grade = null;
            $result = null;
        }

        $data = [
            'student_name' => $student_name,
            'guardian_name' => $father_name ?? $guardian_name,
            'gr_no' => $gr_no,
            'dob' => $dob,
            'roll_number' => $roll_number,
            'class_section' => $class_section,
            'sessionYear' => $sessionYear,
            'date' => $date,
            'father_name' => $father_name,
            'subjects' => $subjectMarks,
            'totalMarks' => $totalMarks,
            'obtainmarks' => $obtainmarks,
            'percentage' => $percentage,
            'grade' => $grade,
            'result' => $result,
        ];

        $pdf = Pdf::loadView('students.result_template', compact('data', 'settings', 'exams', 'subjects'));

        return $pdf->stream('result.pdf');
    }

    public function generateExamResult(Request $request, $id)
    {
        if (! Auth::user()->can('view-exam-result')) {
            return redirect(route('home'))->withErrors([
                'message' => trans('no_permission_message'),
            ]);
        }

        $settings = getSettings();
        $date = date('d-m-Y');

        $exam_id = $request->query('exam_id');
        $semester_id = $request->query('semester_id');
        $session_year_id = $request->query('session_year_id', $settings['session_year'] ?? null);

        $validator = Validator::make([
            'exam_id' => $exam_id,
            'session_year_id' => $session_year_id,
            'semester_id' => $semester_id,
        ], [
            'exam_id' => 'required|exists:exams,id',
            'session_year_id' => 'required|exists:session_years,id',
            'semester_id' => 'nullable|exists:semesters,id',
        ], [
            'exam_id.required' => 'Exam not selected.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors()->first());
        }

        $sessionYear = SessionYear::where('id', $session_year_id)
            ->value('name');

        // Student
        $student = StudentSessions::with(
            'student.user:id,first_name,last_name,dob',
            'class_section.class.medium',
            'class_section.class.streams',
            'class_section.section',
            'student.father',
            'student.guardian'
        )
            ->where('student_id', $id)
            ->where('session_year_id', $session_year_id)
            ->first();

        if (! $student) {
            return redirect()->back()->withErrors(trans('no_data_found'));
        }

        // Basic Info
        $student_name = $student->student->user->first_name.' '.$student->student->user->last_name;
        $father_name = optional($student->student->father)->first_name.' '.optional($student->student->father)->last_name;
        $guardian_name = optional($student->student->guardian)->first_name.' '.optional($student->student->guardian)->last_name;

        $dob = date('d-m-Y', strtotime($student->student->user->dob));
        $gr_no = $student->student->admission_no;
        $roll_number = $student->student->roll_number;

        $class_section = $student->class_section->class->name.' '.
            $student->class_section->section->name.' '.
            $student->class_section->class->medium->name.' '.
            ($student->class_section->class->streams->name ?? '');

        $class_id = $student->class_section->class->id;

        // Student Subjects
        $student_subject = $student->student->subjects();

        $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

        $elective_subjects = $student_subject['elective_subject'] ?? [];

        if ($elective_subjects) {
            $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
        }

        $student_subject_ids = array_merge($core_subjects, $elective_subjects);

        // Get only subjects available in this exam timetable
        $exam_subject_ids = ExamTimetable::where('exam_id', $exam_id)
            ->where('class_id', $class_id)
            ->whereIn('subject_id', $student_subject_ids)
            ->pluck('subject_id')
            ->toArray();

        // Final subjects = only subjects present in exam timetable
        $subjects = Subject::whereIn('id', $exam_subject_ids)->get();

        // Single Exam with timetable
        $exam = Exam::with(['timetable' => function ($q) use ($class_id, $exam_subject_ids) {
            $q->where('class_id', $class_id)
                ->whereIn('subject_id', $exam_subject_ids);
        }])
            ->where('id', $exam_id)
            ->where('session_year_id', $session_year_id)
            ->when($semester_id, function ($query) use ($semester_id) {
                $query->where('semester_id', $semester_id);
            })
            ->where('publish', 1)
            ->first();

        if (! $exam) {
            return redirect()->back()->withErrors('Exam not found');
        }

        $subjectMarks = [];
        $totalMarks = 0;
        $obtainmarks = 0;

        foreach ($subjects as $subject) {

            $timetable = $exam->timetable->where('subject_id', $subject->id)->first();

            $obtained = 0;
            $total = 0;
            $grade = null;

            if ($timetable) {

                $exam_marks = ExamMarks::where('exam_timetable_id', $timetable->id)
                    ->where('student_id', $student->student_id)
                    ->where('session_year_id', $session_year_id)
                    ->first();

                if ($exam_marks) {
                    $obtained = $exam_marks->obtained_marks;
                    $total = $timetable->total_marks;

                    $totalMarks += $total;
                    $obtainmarks += $obtained;

                    // Grade
                    if ($total > 0) {
                        $percent = round(($obtained / $total) * 100);
                        $grade = Grade::where('starting_range', '<=', $percent)
                            ->where('ending_range', '>=', $percent)
                            ->value('grade');

                    }
                }
            }

            $subjectMarks[$subject->name.' ('.$subject->type.')'] = [
                'obtained' => $obtained,
                'total' => $total,
                'grade' => $grade,
            ];
        }

        // Overall Result
        $percentage = $totalMarks > 0
            ? round(($obtainmarks / $totalMarks) * 100, 2)
            : 0;

        $grade = Grade::where('starting_range', '<=', round($percentage))
            ->where('ending_range', '>=', round($percentage))
            ->value('grade');

        $result = ($percentage >= 40) ? 'Passed' : 'Failed';

        // Final Data
        $data = [
            'student_name' => $student_name,
            'guardian_name' => $guardian_name,
            'gr_no' => $gr_no,
            'dob' => $dob,
            'roll_number' => $roll_number,
            'class_section' => $class_section,
            'sessionYear' => $sessionYear,
            'date' => $date,
            'father_name' => $father_name,
            'exam_name' => $exam->name, // added
            'subjects' => $subjectMarks,
            'totalMarks' => $totalMarks,
            'obtainmarks' => $obtainmarks,
            'percentage' => $percentage,
            'grade' => $grade,
            'result' => $result,
        ];

        $pdf = Pdf::loadView(
            'students.exam_result_template',
            compact('data', 'settings', 'exam', 'subjects')
        );

        return $pdf->stream('result.pdf');
    }

    public function studentList(Request $request)
    {
        if (! Auth::user()->can('student-list')) {
            return response()->json([
                'message' => trans('no_permission_message'),
            ]);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

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

            $operate = '';
            if (Auth::user()->can('generate-result')) {
                $operate = '<a href="'.route('generate.result', $student->id).'" 
                class="btn btn-xs btn-gradient-success btn-rounded btn-icon" 
                data-id="'.$student->id.'" title="Generate Result">
                <i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
            }

            $rows[] = [
                'id' => $student->id,
                'no' => $no++,
                'user_id' => $student->user_id,
                'student_name' => $student->user->first_name.' '.$student->user->last_name,
                'dob' => optional($student->user->dob)->format($dateFormat['date_formate']),
                'admission_no' => $student->admission_no,
                'class_section_id' => $session->class_section_id,
                'class_section_name' => ($session->class_section->class->name ?? '').' '.
                    ($session->class_section->section->name ?? '').' '.
                    ($session->class_section->class->medium->name ?? '').' '.
                    ($session->class_section->class->streams->name ?? ''),
                'roll_number' => $student->roll_number,
                'operate' => $operate,
            ];
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    public function getStudentResultDetails(Request $request)
    {
        // Only used by the result modal on the promote-students page.
        if (! Auth::user()->can('promote-student-list')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $validator = Validator::make($request->all(), [
            'student_id' => 'required',
            'class_section_id' => 'required',
            'session_year_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $student_id = $request->student_id;
            $class_section_id = $request->class_section_id;
            $sessionYearId = $request->session_year_id;

            // Fetch student with relations (eager loaded)
            $studentDetails = Students::with(['user', 'class_section.class', 'class_section.section'])
                ->findOrFail($student_id);

            $name = trim($studentDetails->user->first_name.' '.$studentDetails->user->last_name);

            // Get Avatar
            $avatarUrl = 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=10B981&color=fff';
            $imagePath = $studentDetails->user->image;
            $validImage = $imagePath ? Storage::exists($imagePath) : false;
            $imageUrl = $validImage ? Storage::url($imagePath) : $avatarUrl;

            // 1. Get exam results with exam relation
            $examDetails = ExamResult::with('exam')
                ->where('student_id', $student_id)
                ->where('class_section_id', $class_section_id)
                ->where('session_year_id', $sessionYearId)
                ->get();

            if ($examDetails->isEmpty()) {
                return response()->json([
                    'student' => [
                        'name' => $name,
                        'roll_number' => $studentDetails->roll_number,
                        'admission_no' => $studentDetails->admission_no,
                        'class_section' => $studentDetails->class_section->class->name.' '.$studentDetails->class_section->section->name,
                        'image' => $imageUrl,
                    ],
                    'summary' => [],
                    'exam_history' => [],
                ]);
            }

            // 2. Extract exam IDs
            $examIds = $examDetails->pluck('exam_id')->filter()->unique()->values();

            // 3. Fetch timetables for these exams
            $examTimetables = ExamTimetable::whereIn('exam_id', $examIds)
                ->get(['id', 'exam_id', 'date', 'total_marks', 'subject_id'])
                ->keyBy('id');

            $timetableIds = $examTimetables->keys()->all();

            // 4. Fetch marks with all necessary relations
            $examMarks = ExamMarks::with([
                'timetable.subject',
                'subject',
            ])
                ->whereIn('exam_timetable_id', $timetableIds)
                ->where('student_id', $student_id)
                ->get();

            // Group marks by exam_id using timetable->exam_id
            $marksGroupedByExam = [];
            foreach ($examMarks as $mark) {
                $timetable = $mark->timetable;
                if (! $timetable || is_null($timetable->exam_id)) {
                    continue;
                }
                $marksGroupedByExam[$timetable->exam_id][] = $mark;
            }

            // -------------------------
            // BUILD EXAM HISTORY
            // -------------------------
            $examHistory = [];
            $summaryTotalObtained = 0;
            $summaryTotalMax = 0;
            $percentages = [];

            foreach ($examDetails as $examResult) {
                $examId = $examResult->exam_id;
                $subjectMarks = collect($marksGroupedByExam[$examId] ?? []);

                $subjectList = [];
                $examTotalObtained = 0;
                $examTotalMarks = 0;
                $examDateCarbons = [];

                foreach ($subjectMarks as $mark) {
                    $timetable = $mark->timetable;
                    $subject = $mark->subject ?? $timetable?->subject; // Safe fallback, both eager loaded

                    $total = $timetable?->total_marks ?? 0;
                    $obt = $mark->obtained_marks ?? 0;
                    $percent = $total > 0 ? round(($obt / $total) * 100) : 0;

                    $subjectList[] = [
                        'name' => $subject?->name ?? 'Unknown Subject',
                        'obtained_marks' => $obt,
                        'total_marks' => $total,
                        'percentage' => $percent,
                        'grade' => $mark->grade ?? null,
                    ];

                    $examTotalObtained += $obt;
                    $examTotalMarks += $total;

                    if ($timetable?->date) {
                        try {
                            $examDateCarbons[] = Carbon::parse($timetable->date);
                        } catch (\Throwable $e) {
                            // Ignore invalid dates
                            $response = [
                                'error' => true,
                                'message' => trans('error_occurred'),
                            ];
                        }
                    }
                }

                $examPercent = $examResult->percentage; // Keep using stored value for consistency

                $percentages[] = $examPercent;
                $summaryTotalObtained += $examTotalObtained;
                $summaryTotalMax += $examTotalMarks;

                // Earliest exam date
                $examDate = null;
                if ($examDateCarbons) {
                    $earliest = collect($examDateCarbons)->sort()->first();
                    $examDate = $earliest?->format('d M Y');
                }

                $examHistory[] = [
                    'name' => $examResult->exam?->name ?? 'Unknown Exam',
                    'date' => $examDate,
                    'percentage' => $examPercent,
                    'obtained_marks' => $examTotalObtained,
                    'total_marks' => $examTotalMarks,
                    'grade' => $examResult->grade,
                    'subjects' => $subjectList,
                ];
            }

            // Sort exam history by date descending (null dates last)
            $examHistory = collect($examHistory)->sortByDesc(function ($item) {
                if (empty($item['date'])) {
                    return PHP_INT_MAX;
                }
                try {
                    return Carbon::createFromFormat('d M Y', $item['date'])->timestamp;
                } catch (\Throwable $e) {
                    return PHP_INT_MAX - 1;
                }
            })->values()->all();

            // -------------------------
            // BUILD SUMMARY
            // -------------------------
            $summary = [
                'total_exams' => count($examHistory),
                'average' => count($percentages) ? round(array_sum($percentages) / count($percentages), 2) : 0,
                'highest' => $percentages ? max($percentages) : 0,
                'lowest' => $percentages ? min($percentages) : 0,
                'overall_grade' => $examDetails->sortByDesc('percentage')->first()->grade ?? '-',
                'total_obtained' => $summaryTotalObtained,
                'total_max' => $summaryTotalMax,
                'total_percentage' => $summaryTotalMax > 0
                    ? round(($summaryTotalObtained / $summaryTotalMax) * 100)
                    : 0,
            ];

            // -------------------------
            // FINAL RESPONSE
            // -------------------------
            $response = [
                'student' => [
                    'name' => $name,
                    'roll_number' => $studentDetails->roll_number,
                    'admission_no' => $studentDetails->admission_no,
                    'class_section' => $studentDetails->class_section->class->name.' '.$studentDetails->class_section->section->name,
                    'image' => $imageUrl,
                ],
                'summary' => $summary,
                'exam_history' => $examHistory,
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => config('app.debug') ? $e->getMessage() : trans('error_contact_admin'),
            ];
        }

        return response()->json($response);
    }
}
