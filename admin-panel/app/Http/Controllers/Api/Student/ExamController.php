<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSessionConfig;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Term;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Student app: the offline (paper) exams: schedule, details and marks.
 */
class ExamController extends Controller
{
    use StudentAccess;

    public function getExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:0,1,2,3',
            'get_timetable' => 'nullable|in:0,1',
            'term_id' => 'nullable|numeric|exists:terms,id',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {

            $student_id = Auth::user()->student->id;
            $student = Students::with('class_section')->where('id', $student_id)->first();
            $session_year_id = getSettings('session_year')['session_year'];
            $student_subject = $student->subjects();
            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            $core_subjects = array_column($student_subject['core_subject'], 'subject_id') ?? [];

            $elective_subjects = $student_subject['elective_subject'] ?? [];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            if ($request->filled('term_id')) {
                $term_id = Term::where('id', $request->term_id)
                    // ->where('session_year_id', $session_year_id) //not needed because term id is unique across all session years
                    ->first();

                if (! $term_id) {
                    return ResponseService::validationError('Invalid term id.');
                }
            } else {
                $term_id = Term::currentForClass((int) $session_year_id, (int) $class_id);
            }

            $isClassHaveTerm = ClassSessionConfig::classHasTerm($class_id, $session_year_id);

            $exam_data_db = ExamClass::with('exam.session_year:id,name', 'exam.timetable.subject')
                ->where('class_id', $class_id)
                ->whereHas('exam.timetable', function ($query) use ($subject_id) {
                    $query->whereIn('subject_id', $subject_id);
                })
                ->when($isClassHaveTerm && $term_id, function ($query) use ($term_id) {
                    $query->whereHas('exam', function ($q) use ($term_id) {
                        $q->where('term_id', $term_id->id);
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

            return ResponseService::successResponse('Exam List Fetched Successfully', $exam_data ?? []);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student_id = Auth::user()->student->id;
            $student = Students::with('class_section')->where('id', $student_id)->first();
            $session_year_id = getSettings('session_year')['session_year'];
            $student_subject = $student->subjects();
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

            $elective_subjects = $student_subject['elective_subject'] == null ? [] : $student_subject['elective_subject']->pluck('subject_id')->toArray();

            $subject_id = array_merge($core_subjects, $elective_subjects);

            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;
            $exam_data_db = Exam::with(['timetable' => function ($q) use ($request, $class_id, $subject_id) {
                $q->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->whereIn('subject_id', $subject_id)->with(['subject'])->orderby('date');
            }])->where('id', $request->exam_id)->first();

            if (! $exam_data_db) {
                return ResponseService::errorResponse('error_occurred', null, 103);
            }

            foreach ($exam_data_db->timetable as $data) {
                $exam_data[] = [
                    'id' => $data->id,
                    'total_marks' => $data->total_marks,
                    'passing_marks' => $data->passing_marks,
                    'date' => $data->date,
                    'starting_time' => $data->start_time,
                    'ending_time' => $data->end_time,
                    'venue' => $data->venue,
                    'revision' => $data->revision,
                    'subject' => [
                        'id' => $data->subject->id,
                        'name' => $data->subject->name,
                        'bg_color' => $data->subject->bg_color,
                        'image' => $data->subject->image,
                        'type' => $data->subject->type,
                    ],
                ];
            }

            return ResponseService::successResponse('Exam Details Fetched Successfully', $exam_data ?? []);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getExamMarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'term_id' => 'nullable|numeric|exists:terms,id',
            'session_year_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $session_year_id = $request->filled('session_year_id')
                ? (int) $request->session_year_id
                : getSettings('session_year')['session_year'];

            $student = $request->user()->student;
            $class_data = Students::where('id', $student->id)
                ->with('class_section.class.medium', 'class_section.section')
                ->first();

            $class_id = $class_data->class_section->class_id;

            // Determine term context
            $isClassHaveTerm = ClassSessionConfig::classHasTerm($class_id, $session_year_id);

            $termId = null;
            if ($isClassHaveTerm) {
                if ($request->filled('term_id')) {
                    $term = Term::where('id', $request->term_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    if (! $term) {
                        return ResponseService::validationError('Invalid term_id for current session year.');
                    }

                    $termId = $term->id;
                } elseif (! $request->filled('session_year_id')) {
                    $currentTerm = Term::currentForClass((int) $session_year_id, (int) $class_id);

                    if (! $currentTerm) {
                        return ResponseService::validationError('Current term not found for current session year.');
                    }

                    $termId = $currentTerm?->id;
                }
            }

            // Build exam result query
            $exam_result_query = ExamResult::with([
                'student' => fn ($q) => $q->select('id', 'user_id', 'roll_number')
                    ->with('user:id,first_name,last_name'),
                'exam',
                'session_year',
                'exam.marks' => fn ($q) => $q->where('student_id', $student->id),
            ])
                ->where('student_id', $student->id)
                ->where('session_year_id', $session_year_id);

            // Filter by term if applicable
            if ($isClassHaveTerm && $termId) {
                $exam_result_query->whereHas('exam', function ($q) use ($termId) {
                    $q->where('term_id', $termId);
                });
            }

            $exam_result_db = $exam_result_query->get();

            if ($exam_result_db->isNotEmpty()) {
                $data = [];

                foreach ($exam_result_db as $exam_result_data) {
                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))
                        ->where([
                            'exam_id' => $exam_result_data->exam_id,
                            'class_id' => $class_id,
                            'session_year_id' => $session_year_id,
                        ])->first();

                    $starting_date = $starting_date_db['min(date)'];

                    $exam_result = [
                        'result_id' => $exam_result_data->id,
                        'exam_id' => $exam_result_data->exam_id,
                        'exam_name' => $exam_result_data->exam->name,
                        'class_name' => $class_data->class_section->class->name.'-'
                            .$class_data->class_section->section->name.' '
                            .$class_data->class_section->class->medium->name,
                        'student_name' => $exam_result_data->student->user->first_name.' '
                            .$exam_result_data->student->user->last_name,
                        'exam_date' => $starting_date,
                        'total_marks' => $exam_result_data->total_marks,
                        'obtained_marks' => $exam_result_data->obtained_marks,
                        'percentage' => $exam_result_data->percentage,
                        'grade' => $exam_result_data->grade,
                        'session_year' => $exam_result_data->session_year->name,
                    ];

                    $exam_marks = [];
                    foreach ($exam_result_data->exam->marks as $marks) {
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

                return ResponseService::successResponse('Exam Result Fetched Successfully', $data);
            } else {
                return ResponseService::successResponse('Exam Result Fetched Successfully', []);
            }
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
