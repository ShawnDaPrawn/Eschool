<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\Exam;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamStudentAnswer;
use App\Models\Semester;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Models\Teacher;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: a child's online exams and results.
 */
class OnlineExamController extends Controller
{
    use ParentAccess;

    public function getOnlineExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'nullable|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->resolveChild($request);

            $student_subject = $student->subjects();
            // $class_subject = $student->classSubjects();

            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');

            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

            // get current
            $time_data = Carbon::now()->toArray();
            $current_date_time = $time_data['formatted'];

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            // checks the subject id param is passed or not .
            // query meets the condition for both class section and class
            if (isset($request->subject_id) && ! empty($request->subject_id)) {
                $exam_data_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'subject_id' => $request->subject_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])
                    ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                        if ($currentSemester) {
                            $q->where('semester_id', $currentSemester->id);
                        }
                    })
                    ->has('question_choice')->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })->orWhere(function ($query) use ($class_id, $session_year_id, $current_date_time, $student, $request, $isClassHaveSemester, $currentSemester) {
                        $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'subject_id' => $request->subject_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])
                            ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                                if ($currentSemester) {
                                    $q->where('semester_id', $currentSemester->id);
                                }
                            })
                            ->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                                $q->where('student_id', $student->id);
                            });
                    })->orderby('start_date')->paginate(15)->toArray();
            } else {
                $exam_data_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])->whereIn('subject_id', $subject_id)
                    ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                        if ($currentSemester) {
                            $q->where('semester_id', $currentSemester->id);
                        }
                    })
                    ->has('question_choice')->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })->orWhere(function ($query) use ($class_id, $subject_id, $session_year_id, $current_date_time, $student, $isClassHaveSemester, $currentSemester) {
                        $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'session_year_id' => $session_year_id,  ['end_date', '>=', $current_date_time]])->whereIn('subject_id', $subject_id)
                            ->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                                if ($currentSemester) {
                                    $q->where('semester_id', $currentSemester->id);
                                }
                            })
                            ->with('subject')->whereDoesntHave('student_attempt', function ($q) use ($student) {
                                $q->where('student_id', $student->id);
                            });
                    })->orderby('start_date')->paginate(15)->toArray();
            }
            if (isset($exam_data_db) && ! empty($exam_data_db)) {

                $exam_data = [];
                $exam_list = [];
                // making the array of exam data
                foreach ($exam_data_db['data'] as $data) {

                    // total marks of exams
                    $total_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->first();
                    $total_marks = $total_marks['sum(marks)'];

                    if ($data['model_type'] == 'App\Models\ClassSection') {
                        $class_section_data = ClassSection::where('id', $data['model_id'])->with('class.medium', 'section')->first();
                        $class_name = $class_section_data->class->name.' - '.$class_section_data->section->name.' '.$class_section_data->class->medium->name;
                    } else {
                        $class_data = ClassSchool::where('id', $data['model_id'])->with('medium')->first();
                        $class_name = $class_data->name.' '.$class_data->medium->name;
                    }

                    $exam_list[] = [
                        'exam_id' => $data['id'],
                        'class' => [
                            'id' => $data['model_id'],
                            'name' => $class_name,
                        ],
                        'subject' => [
                            'id' => $data['subject_id'],
                            'name' => $data['subject']['name'].' - '.$data['subject']['type'],
                        ],
                        'title' => $data['title'],
                        // exam_key is not sent: the teacher gives it to students when the exam starts.
                        'duration' => $data['duration'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'total_marks' => $total_marks,
                    ];
                }

                // adding the exam data with pagination data
                $exam_data = [
                    'current_page' => $exam_data_db['current_page'],
                    'data' => $exam_list,
                    'from' => $exam_data_db['from'],
                    'last_page' => $exam_data_db['last_page'],
                    'per_page' => $exam_data_db['per_page'],
                    'to' => $exam_data_db['to'],
                    'total' => $exam_data_db['total'],
                ];
            } else {
                // if no data found
                $exam_data = null;
            }
            ResponseService::successResponse('Exam List Fetched Successfully', $exam_data, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamResultList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'subject_id' => 'nullable|numeric',
            'session_year_id' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->resolveChild($request);

            // session year: optional override, else current
            $session_year_id = $request->filled('session_year_id')
                ? (int) $request->session_year_id
                : getSettings('session_year')['session_year'];

            $studentSession = StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', $session_year_id)->with('class_section')->first();
            if (! $studentSession) {
                ResponseService::errorResponse('No session found for this student in the current session year', null, 113);

                return;
            }
            $class_section_id = $studentSession->class_section_id;
            $class_id = $studentSession->class_section->class_id;

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            // get the class subject id on the basis of subject id passed
            // query meets the condition for both class section and class
            if (isset($request->subject_id) && ! empty($request->subject_id)) {
                $online_exam_db = OnlineExam::where([
                    'model_type' => 'App\Models\ClassSection',
                    'model_id' => $class_section_id,
                    'subject_id' => $request->subject_id,
                    'session_year_id' => $session_year_id,
                ])->whereHas('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                })->orWhere(function ($query) use ($class_id, $session_year_id, $request, $student) {
                    $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'session_year_id' => $session_year_id, 'subject_id' => $request->subject_id])->with('subject')->whereHas('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    });
                })->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                    if ($currentSemester) {
                        $q->where('semester_id', $currentSemester->id);
                    }
                })->with('subject')->paginate(10)->toArray();
            } else {
                $online_exam_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'session_year_id' => $session_year_id])->whereHas('student_attempt', function ($q) use ($student) {
                    $q->where('student_id', $student->id);
                })->orWhere(function ($query) use ($class_id, $session_year_id, $student) {
                    $query->where(['model_type' => 'App\Models\ClassSchool', 'model_id' => $class_id, 'session_year_id' => $session_year_id])->with('subject')->whereHas('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    });
                })->when($isClassHaveSemester, function ($q) use ($currentSemester) {
                    if ($currentSemester) {
                        $q->where('semester_id', $currentSemester->id);
                    }
                })->with('subject')->paginate(10)->toArray();
            }
            $exam_list_data = [];
            foreach ($online_exam_db['data'] as $data) {
                $exam_submitted_date = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('submitted_date')->first();

                // a question counts only when exactly its correct options were picked
                $score = app(\App\Services\OnlineExamScoringService::class)->score((int) $student->id, (int) $data['id']);

                // get the data of only attempted data
                $total_obtained_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->whereIn('id', $score['correct_choice_ids'])->first();
                $total_obtained_marks_exam = $total_obtained_marks_exam['sum(marks)'];
                $total_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->first();
                $total_marks_exam = $total_marks_exam['sum(marks)'];

                $exam_list_data[] = [
                    'online_exam_id' => $data['id'],
                    'subject' => [
                        'id' => $data['subject_id'],
                        'name' => $data['subject']['name'].' - '.$data['subject']['type'],
                    ],
                    'title' => $data['title'],
                    'obtained_marks' => $total_obtained_marks_exam ?? '0',
                    'total_marks' => $total_marks_exam ?? '0',
                    'exam_submitted_date' => $exam_submitted_date ?? date('Y-m-d', strtotime($data['end_date'])),
                ];
            }
            $exam_list = [
                'current_page' => $online_exam_db['current_page'],
                'data' => $exam_list_data ?? '',
                'from' => $online_exam_db['from'],
                'last_page' => $online_exam_db['last_page'],
                'per_page' => $online_exam_db['per_page'],
                'to' => $online_exam_db['to'],
                'total' => $online_exam_db['total'],
            ];
            ResponseService::successResponse('Exam List Fetched Successfully', $exam_list, [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamResult(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'online_exam_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $this->resolveChild($request);

            // Only an online exam set for a class or class section the child has studied in.
            $childClassSectionIds = StudentSessions::where('student_id', $student->id)->pluck('class_section_id')
                ->push($student->class_section_id)->filter()->unique()->values()->all();
            $childClassIds = ClassSection::withTrashed()->whereIn('id', $childClassSectionIds)->pluck('class_id')->all();
            $examIsForChild = OnlineExam::whereKey((int) $request->online_exam_id)
                ->where(function ($query) use ($childClassSectionIds, $childClassIds) {
                    $query->where(function ($q) use ($childClassSectionIds) {
                        $q->where('model_type', ClassSection::class)->whereIn('model_id', $childClassSectionIds);
                    })->orWhere(function ($q) use ($childClassIds) {
                        $q->where('model_type', ClassSchool::class)->whereIn('model_id', $childClassIds);
                    });
                })->exists();
            if (! $examIsForChild) {
                ResponseService::errorResponse('No Online Exam Found', null, 103);
            }

            // get the total questions count
            $total_questions = OnlineExamQuestionChoice::where('online_exam_id', $request->online_exam_id)->count();

            // a question counts only when exactly its correct options were picked
            $score = app(\App\Services\OnlineExamScoringService::class)->score((int) $student->id, (int) $request->online_exam_id);
            $choice_marks = OnlineExamQuestionChoice::where('online_exam_id', $request->online_exam_id)->pluck('marks', 'id');

            // get the correct answers counter
            $exam_correct_answers = count($score['correct_choice_ids']);

            // array of correct answer with choiced exam id and marks
            $correct_answers_data = [];
            foreach ($score['correct_choice_ids'] as $choice_id) {
                $correct_answers_data[] = [
                    'question_id' => $choice_id,
                    'marks' => $choice_marks[$choice_id] ?? null,
                ];
            }

            // get the incorrect answers && unattempted counter
            $exam_in_correct_answers = count($score['incorrect_choice_ids']);

            // array of in correct answer && unattempted with choiced exam id and marks
            $in_correct_answers_data = [];
            foreach ($score['incorrect_choice_ids'] as $choice_id) {
                $in_correct_answers_data[] = [
                    'question_id' => $choice_id,
                    'marks' => $choice_marks[$choice_id] ?? null,
                ];
            }

            // total obtained and total marks
            $total_obtained_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $request->online_exam_id)->whereIn('id', $score['correct_choice_ids'])->first();
            $total_obtained_marks = $total_obtained_marks['sum(marks)'];
            $total_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $request->online_exam_id)->first();
            $total_marks = $total_marks['sum(marks)'];

            // final array data
            $exam_result = [
                'total_questions' => $total_questions,
                'correct_answers' => [
                    'total_questions' => $exam_correct_answers,
                    'question_data' => $correct_answers_data ?? '',
                ],
                'in_correct_answers' => [
                    'total_questions' => $exam_in_correct_answers,
                    'question_data' => $in_correct_answers_data ?? '',
                ],
                'total_obtained_marks' => $total_obtained_marks ?? '0',
                'total_marks' => $total_marks,
            ];
            ResponseService::successResponse('Exam Result Fetched Successfully', $exam_result ?? '', [], 200);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
