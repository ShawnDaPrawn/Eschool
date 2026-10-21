<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Semester;
use App\Models\StudentSessions;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: a child's offline (paper) exams: schedule, details and marks.
 */
class ExamController extends Controller
{
    use ParentAccess;

    public function getExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|nullable',
            'status' => 'nullable:digits:0,1,2,3',
            'get_timetable' => 'nullable',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->resolveChild($request);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)
                ->with('class_section')->first()->class_section->class_id;

            $child_subject = $student->subjects();

            $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

            $elective_subjects = $child_subject['elective_subject'] ?? [];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            if ($request->filled('semester_id')) {
                $semester_id = Semester::where('id', $request->semester_id)
                    // ->where('session_year_id', $session_year_id) //not needed because semester id is unique across all session years
                    ->first();

                if (! $semester_id) {
                    return ResponseService::validationError('Invalid semester id.');
                }
            } else {
                $semester_id = Semester::where('session_year_id', $session_year_id)
                    ->get()
                    ->first(fn ($s) => $s->current);
            }

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $exam_data_db = ExamClass::with('exam.session_year:id,name', 'exam.timetable.subject')
                ->where('class_id', $class_id)
                ->whereHas('exam.timetable', function ($query) use ($subject_id) {
                    $query->whereIn('subject_id', $subject_id);
                })
                ->when($isClassHaveSemester, function ($query) use ($semester_id) {
                    $query->whereHas('exam', function ($q) use ($semester_id) {
                        $q->where('semester_id', $semester_id->id);
                    });
                })
                ->get();

            foreach ($exam_data_db as $data) {

                // date status
                $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $data->exam->id, 'class_id' => $class_id])->first();
                $starting_date = $starting_date_db['min(date)'];
                $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $data->exam->id, 'class_id' => $class_id])->first();
                $ending_date = $ending_date_db['max(date)'];
                $currentTime = Carbon::now();
                $current_date = date($currentTime->toDateString());
                if ($current_date >= $starting_date && $current_date <= $ending_date) {
                    $exam_status = '1'; // Upcoming = 0 , On Going = 1 , Completed = 2
                } elseif ($current_date < $starting_date) {
                    $exam_status = '0'; // Upcoming = 0 , On Going = 1 , Completed = 2
                } else {
                    $exam_status = '2'; // Upcoming = 0 , On Going = 1 , Completed = 2
                }

                // $request->status  =  0 :- all exams , 1 :- Upcoming , 2 :- On Going , 3 :- Completed

                if (isset($request->status)) {
                    if ($request->status == 0) {
                        if ($request->get_timetable == 1) {
                            $exam_data[] = [
                                'id' => $data->exam->id,
                                'name' => $data->exam->name,
                                'description' => $data->exam->description,
                                'publish' => $data->exam->publish,
                                'session_year' => $data->exam->session_year->name,
                                'exam_starting_date' => $starting_date,
                                'exam_ending_date' => $ending_date,
                                'exam_status' => $exam_status,
                                'exam_timetable' => $data->exam->timetable,
                            ];
                        } else {
                            $exam_data[] = [
                                'id' => $data->exam->id,
                                'name' => $data->exam->name,
                                'description' => $data->exam->description,
                                'publish' => $data->exam->publish,
                                'session_year' => $data->exam->session_year->name,
                                'exam_starting_date' => $starting_date,
                                'exam_ending_date' => $ending_date,
                                'exam_status' => $exam_status,
                            ];
                        }
                    } elseif ($request->status == 1) {
                        if ($exam_status == 0) {
                            if ($request->get_timetable == 1) {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                    'exam_timetable' => $data->exam->timetable,
                                ];
                            } else {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                ];
                            }
                        }
                    } elseif ($request->status == 2) {
                        if ($exam_status == 1) {
                            if ($request->get_timetable == 1) {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                    'exam_timetable' => $data->exam->timetable,
                                ];
                            } else {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                ];
                            }
                        }
                    } else {
                        if ($exam_status == 2) {
                            if ($request->get_timetable == 1) {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                    'exam_timetable' => $data->exam->timetable,
                                ];
                            } else {
                                $exam_data[] = [
                                    'id' => $data->exam->id,
                                    'name' => $data->exam->name,
                                    'description' => $data->exam->description,
                                    'publish' => $data->exam->publish,
                                    'session_year' => $data->exam->session_year->name,
                                    'exam_starting_date' => $starting_date,
                                    'exam_ending_date' => $ending_date,
                                    'exam_status' => $exam_status,
                                ];
                            }
                        }
                    }
                } else {
                    if ($request->get_timetable == 1) {
                        $exam_data[] = [
                            'id' => $data->exam->id,
                            'name' => $data->exam->name,
                            'description' => $data->exam->description,
                            'publish' => $data->exam->publish,
                            'session_year' => $data->exam->session_year->name,
                            'exam_starting_date' => $starting_date,
                            'exam_ending_date' => $ending_date,
                            'exam_status' => $exam_status,
                            'exam_timetable' => $data->exam->timetable,
                        ];
                    } else {
                        $exam_data[] = [
                            'id' => $data->exam->id,
                            'name' => $data->exam->name,
                            'description' => $data->exam->description,
                            'publish' => $data->exam->publish,
                            'session_year' => $data->exam->session_year->name,
                            'exam_starting_date' => $starting_date,
                            'exam_ending_date' => $ending_date,
                            'exam_status' => $exam_status,
                        ];
                    }
                }
            }
            return ResponseService::successResponse('Exam List Fetched Successfully', $exam_data ?? [], [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|nullable',
            'exam_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->resolveChild($request);
            $student_subject = $student->subjects();

            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] == null ? [] : $student_subject['elective_subject']->pluck('subject_id')->toArray();

            $subject_id = array_merge($core_subjects, $elective_subjects);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $exam_data = Exam::with([
                'timetable' => function ($q) use ($request, $class_id, $subject_id) {
                    $q->where([
                        'exam_id' => $request->exam_id,
                        'class_id' => $class_id,
                    ])
                        ->whereIn('subject_id', $subject_id)
                        ->with(['subject'])
                        ->orderBy('date');
                },
            ])
                ->where('id', $request->exam_id)
                ->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                })
                ->first();

            $data = isset($exam_data) ? $exam_data->timetable : [];
            return ResponseService::successResponse('Exam Details Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamMarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric|exists:students,id',
            'semester_id' => 'nullable|numeric|exists:semesters,id',
            'session_year_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $child = $this->resolveChild($request);
            $session_year_id = $request->filled('session_year_id')
                ? (int) $request->session_year_id
                : getSettings('session_year')['session_year'];

            $studentSession = StudentSessions::where('student_id', $child->id)
                ->where('session_year_id', $session_year_id)
                ->with('class_section')
                ->first();

            if (! $studentSession) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }

            $class_id = $studentSession->class_section->class_id;
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            $semesterId = null;
            if ($isClassHaveSemester) {
                if ($request->filled('semester_id')) {
                    $semester = Semester::where('id', $request->semester_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    if (! $semester) {
                        return ResponseService::validationError('Invalid semester_id for current session year.');
                    }

                    $semesterId = $semester->id;
                } elseif (! $request->filled('session_year_id')) {
                    $currentSemester = Semester::where('session_year_id', $session_year_id)
                        ->get()
                        ->first(fn ($semester) => $semester->current);

                    if (! $currentSemester) {
                        return ResponseService::validationError('Current semester not found for current session year.');
                    }

                    $semesterId = $currentSemester->id;
                }
            }

            $exam_result_db = ExamResult::with(['student' => function ($q) {
                $q->select('id', 'user_id', 'roll_number')->with('user:id,first_name,last_name');
            }])->with('exam', 'session_year:id,name')->where('student_id', $child->id)
                ->where('session_year_id', $session_year_id)
                ->when($isClassHaveSemester && $semesterId, function ($query) use ($semesterId) {
                    $query->whereHas('exam', function ($q) use ($semesterId) {
                        $q->where('semester_id', $semesterId);
                    });
                })
                ->get();

            if (count($exam_result_db)) {
                foreach ($exam_result_db as $result) {
                    $exam_timetable_id = ExamTimetable::where('exam_id', $result->exam_id)->pluck('id');

                    $exam_marks_db = ExamMarks::whereIn('exam_timetable_id', $exam_timetable_id)
                        ->where('student_id', $result->student_id)->get();

                    $class_data = ClassSection::where('id', $result->class_section_id)->with('class.medium', 'section')->first();

                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where([
                        'exam_id' => $result->exam_id,
                        'class_id' => $class_data->class_id,
                        'session_year_id' => $session_year_id,
                    ])->first();
                    $starting_date = $starting_date_db['min(date)'];

                    $exam_result = [];
                    $exam_result = [
                        'result_id' => $result->id,
                        'exam_id' => $result->exam_id,
                        'exam_name' => $result->exam->name,
                        'class_name' => $class_data->class->name.'-'.$class_data->section->name.' '.$class_data->class->medium->name,
                        'student_name' => $result->student->user->first_name.' '.$result->student->user->last_name,
                        'exam_date' => $starting_date,
                        'total_marks' => $result->total_marks,
                        'obtained_marks' => $result->obtained_marks,
                        'percentage' => $result->percentage,
                        'grade' => $result->grade,
                        'session_year' => $result->session_year->name,
                    ];

                    $exam_marks = [];
                    foreach ($exam_marks_db as $marks) {
                        $exam_marks[] = [
                            'marks_id' => $marks->id,
                            'subject_name' => $marks->subject->name,
                            'subject_type' => $marks->subject->type,
                            'total_marks' => $marks->timetable->total_marks,
                            'passing_marks' => $marks->timetable->passing_marks,
                            'obtained_marks' => $marks->obtained_marks,
                            'teacher_review' => $marks->teacher_review,
                            'grade' => $marks->grade,
                        ];
                    }
                    $data[] = [
                        'result' => $exam_result,
                        'exam_marks' => $exam_marks,
                    ];
                }
                return ResponseService::successResponse('Exam Result Fetched Successfully', $data, [], 200);
            } else {
                return ResponseService::successResponse('Exam Result Fetched Successfully', [], [], 200);
            }
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
