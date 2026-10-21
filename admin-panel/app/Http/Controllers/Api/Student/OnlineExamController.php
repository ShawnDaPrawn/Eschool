<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\Exam;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestionChoice;
use App\Models\OnlineExamStudentAnswer;
use App\Models\Semester;
use App\Models\StudentOnlineExamStatus;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Subject;
use App\Services\OnlineExamScoringService;
use App\Services\ResponseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Student app: online exams: listing, taking them, and the results.
 */
class OnlineExamController extends Controller
{
    use StudentAccess;

    /** Minutes allowed after an online exam's end (or the student's time) for the answers to arrive. */
    private const ONLINE_EXAM_GRACE_MINUTES = 5;

    public function getOnlineExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $date = Carbon::now()->setTimezone('UTC');
            $student = $request->user()->student;

            $student_subject = $student->subjects();
            $core_subjects = array_column($student_subject['core_subject'], 'subject_id');
            $elective_subjects = $student_subject['elective_subject'] ?? [];
            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }
            $subject_id = array_merge($core_subjects, $elective_subjects);

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $student_session = StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', $session_year_id)
                ->with('class_section')
                ->first();

            $class_section_id = $student_session->class_section_id;
            $class_id = $student_session->class_section->class_id;

            $time_data = Carbon::now()->toArray();
            $current_date_time = $time_data['formatted'];

            // --- Semester context ---
            $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);

            $currentSemester = null;
            if ($isClassHaveSemester) {
                $currentSemester = Semester::where('session_year_id', $session_year_id)
                    ->get()
                    ->first(fn($s) => $s->current);
            }

            // Reusable closure to apply semester filter on any query scope
            $applySemesterFilter = function ($query) use ($isClassHaveSemester, $currentSemester) {
                if ($isClassHaveSemester && $currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                }
            };

            if (isset($request->subject_id) && ! empty($request->subject_id)) {

                $exam_data_db = OnlineExam::where([
                    'model_type' => 'App\Models\ClassSection',
                    'model_id' => $class_section_id,
                    'subject_id' => $request->subject_id,
                    'session_year_id' => $session_year_id,
                    ['end_date', '>=', $current_date_time],
                ])
                    ->tap($applySemesterFilter)
                    ->has('question_choice')
                    ->with('subject')
                    ->whereDoesntHave('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })
                    ->orWhere(function ($query) use ($class_id, $session_year_id, $current_date_time, $student, $request, $applySemesterFilter) {
                        $query->where([
                            'model_type' => 'App\Models\ClassSchool',
                            'model_id' => $class_id,
                            'subject_id' => $request->subject_id,
                            'session_year_id' => $session_year_id,
                            ['end_date', '>=', $current_date_time],
                        ])
                            ->tap($applySemesterFilter)
                            ->with('subject')
                            ->whereDoesntHave('student_attempt', function ($q) use ($student) {
                                $q->where('student_id', $student->id);
                            });
                    })
                    ->orderby('start_date')
                    ->paginate(15)
                    ->toArray();
            } else {

                $exam_data_db = OnlineExam::where([
                    'model_type' => 'App\Models\ClassSection',
                    'model_id' => $class_section_id,
                    'session_year_id' => $session_year_id,
                    ['end_date', '>=', $current_date_time],
                ])
                    ->tap($applySemesterFilter)
                    ->whereIn('subject_id', $subject_id)
                    ->has('question_choice')
                    ->with('subject')
                    ->whereDoesntHave('student_attempt', function ($q) use ($student) {
                        $q->where('student_id', $student->id);
                    })
                    ->orWhere(function ($query) use ($class_id, $subject_id, $session_year_id, $current_date_time, $student, $applySemesterFilter) {
                        $query->where([
                            'model_type' => 'App\Models\ClassSchool',
                            'model_id' => $class_id,
                            'session_year_id' => $session_year_id,
                            ['end_date', '>=', $current_date_time],
                        ])
                            ->tap($applySemesterFilter)
                            ->whereIn('subject_id', $subject_id)
                            ->with('subject')
                            ->whereDoesntHave('student_attempt', function ($q) use ($student) {
                                $q->where('student_id', $student->id);
                            });
                    })
                    ->orderby('start_date')
                    ->paginate(15)
                    ->toArray();
            }

            if (isset($exam_data_db) && ! empty($exam_data_db)) {

                $exam_data = [];
                $exam_list = [];

                foreach ($exam_data_db['data'] as $data) {

                    $total_marks = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))
                        ->where('online_exam_id', $data['id'])
                        ->first();
                    $total_marks = $total_marks['sum(marks)'];

                    if ($data['model_type'] == 'App\Models\ClassSection') {
                        $class_section_data = ClassSection::where('id', $data['model_id'])
                            ->with('class.medium', 'section')
                            ->first();
                        $class_name = $class_section_data->class->name . ' - ' . $class_section_data->section->name . ' ' . $class_section_data->class->medium->name;
                    } else {
                        $class_data = ClassSchool::where('id', $data['model_id'])
                            ->with('medium')
                            ->first();
                        $class_name = $class_data->name . ' ' . $class_data->medium->name;
                    }

                    if ($total_marks == null) {
                        $exam_list = [];
                    } else {
                        $exam_list[] = [
                            'exam_id' => $data['id'],
                            'class' => [
                                'id' => $data['model_id'],
                                'name' => $class_name,
                            ],
                            'subject' => [
                                'id' => $data['subject_id'],
                                'name' => $data['subject']['name'] . ' - ' . $data['subject']['type'],
                            ],
                            'title' => $data['title'],
                            // exam_key is not sent: the student must get it from the teacher.
                            'duration' => $data['duration'],
                            'start_date' => $data['start_date'],
                            'end_date' => $data['end_date'],
                            'total_marks' => $total_marks,
                        ];
                    }
                }

                $exam_data = [
                    'current_page' => $exam_data_db['current_page'],
                    'data' => $exam_list,
                    'current_date' => $date,
                    'from' => $exam_data_db['from'],
                    'last_page' => $exam_data_db['last_page'],
                    'per_page' => $exam_data_db['per_page'],
                    'to' => $exam_data_db['to'],
                    'total' => $exam_data_db['total'],
                ];
            } else {
                $exam_data = null;
            }

            ResponseService::successResponse('Exam List Fetched Successfully', $exam_data);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamQuestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required',
            'exam_key' => 'required',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $request->user()->student;

            // Only an exam of the student's own class/section, subjects and session, with the right key
            $exam = is_numeric($request->exam_id) ? $this->findStudentOnlineExam($student, (int) $request->exam_id) : null;
            if (! $exam || ! is_scalar($request->exam_key) || ! hash_equals((string) $exam->exam_key, (string) $request->exam_key)) {
                ResponseService::errorResponse('Invalid Exam Key', null, 103);
            }

            // checks student exam status
            $check_student_status = StudentOnlineExamStatus::where(['online_exam_id' => $exam->id, 'student_id' => $student->id])->count();
            if ($check_student_status != 0) {
                ResponseService::errorResponse('student_already_attempted_exam', null, 105);
            }

            // checks the exam started or not, and has not ended
            $now = Carbon::now();
            if ($now->lt(Carbon::parse($exam->start_date))) {
                ResponseService::errorResponse('exam_not_started_yet', null, 106);
            }
            if ($now->gt(Carbon::parse($exam->end_date))) {
                ResponseService::errorResponse('This exam has ended.', null, null);
            }

            // add the exam status
            $student_exam_status = new StudentOnlineExamStatus;
            $student_exam_status->online_exam_id = $exam->id;
            $student_exam_status->student_id = $student->id;
            $student_exam_status->status = 1;
            $student_exam_status->save();

            // get total questions
            $total_questions = OnlineExamQuestionChoice::where('online_exam_id', $exam->id)->count();

            // get the questions data
            $get_exam_questions_db = OnlineExamQuestionChoice::where('online_exam_id', $exam->id)->with('questions')->get();
            $questions_data = [];
            $total_marks = 0;
            foreach ($get_exam_questions_db as $exam_questions) {
                $total_marks += $exam_questions->marks;

                // make options array
                $options_data = [];
                foreach ($exam_questions->questions->options as $question_options) {
                    $options_data[] = [
                        'id' => $question_options->id,
                        'option' => safe_htmlspecialchars_decode($question_options->option),
                    ];
                }

                // The correct answers are never sent before the exam is submitted;
                // only how many to pick. Older app builds read the number of entries
                // in 'answers', so it keeps that many placeholders.
                $answers_count = $exam_questions->questions->answers->count();

                // make question array
                $questions_data[] = [
                    'id' => $exam_questions->id,
                    'question' => safe_htmlspecialchars_decode($exam_questions->questions->question),
                    'question_type' => $exam_questions->questions->question_type,
                    'options' => $options_data,
                    'answers' => array_fill(0, $answers_count, ['id' => 0, 'option_id' => 0, 'answer' => '']),
                    'answers_count' => $answers_count,
                    'marks' => $exam_questions->marks,
                    'image' => $exam_questions->questions->image_url,
                    'note' => $exam_questions->questions->note,
                ];
            }
            ResponseService::successResponse('Exam Questions Fetched Successfully', $questions_data ?? null, ['total_questions' => $total_questions, 'total_marks' => $total_marks]);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function submitOnlineExamAnswers(Request $request, OnlineExamScoringService $scoring)
    {
        $validator = Validator::make($request->all(), [
            'online_exam_id' => 'required|numeric',
            'answers_data' => 'required|array',
            'answers_data.*.question_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $request->user()->student;

            // checks the online exam exists for this student
            $exam = $this->findStudentOnlineExam($student, (int) $request->online_exam_id);
            if (! $exam) {
                ResponseService::errorResponse('invalid_online_exam_id', null, 103);
            }

            // Only while the exam is open (with a little slack for the upload)
            $now = Carbon::now();
            if ($now->lt(Carbon::parse($exam->start_date))) {
                ResponseService::errorResponse('exam_not_started_yet', null, 106);
            }
            if ($now->gt(Carbon::parse($exam->end_date)->addMinutes(self::ONLINE_EXAM_GRACE_MINUTES))) {
                ResponseService::errorResponse('This exam has ended.', null, null);
            }

            // Every question must be one of the exam's, every option one of that question's
            try {
                $answers = $scoring->validateSubmission($exam->id, $request->answers_data);
            } catch (ValidationException $e) {
                ResponseService::errorResponse($e->validator->errors()->first(), null, null);
            }

            $error = DB::transaction(function () use ($student, $exam, $answers, $now) {
                // The exam must have been started (questions fetched), and only once submitted
                $status = StudentOnlineExamStatus::where(['student_id' => $student->id, 'online_exam_id' => $exam->id])
                    ->lockForUpdate()
                    ->first();
                if (! $status) {
                    return 'Start the exam before submitting answers.';
                }
                if ($status->status == 2 || OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $exam->id])->exists()) {
                    return 'Answers already submitted';
                }
                if ($exam->duration > 0 && $now->gt(Carbon::parse($status->created_at)->addMinutes((int) $exam->duration + self::ONLINE_EXAM_GRACE_MINUTES))) {
                    return 'The time for this exam is over.';
                }

                $current_date = $now->toDateString();
                foreach ($answers as $choice_id => $option_ids) {
                    foreach ($option_ids as $option_id) {
                        // add the data of answers
                        $store_answers = new OnlineExamStudentAnswer;
                        $store_answers->student_id = $student->id;
                        $store_answers->online_exam_id = $exam->id;
                        $store_answers->question_id = $choice_id;
                        $store_answers->option_id = $option_id;
                        $store_answers->submitted_date = $current_date;
                        $store_answers->save();
                    }
                }

                $status->status = 2;
                $status->save();

                return null;
            });

            if ($error) {
                ResponseService::errorResponse($error, null, null);
            }
            ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamResultList(Request $request)
    {
        try {
            $student = $request->user()->student;
            // session year: optional override, else current
            $session_year_id = $request->filled('session_year_id')
                ? (int) $request->session_year_id
                : getSettings('session_year')['session_year'];
            $class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->first()->class_section_id;
            $class_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)->with('class_section')->first()->class_section->class_id;

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
                $online_exam_db = OnlineExam::where(['model_type' => 'App\Models\ClassSection', 'model_id' => $class_section_id, 'subject_id' => $request->subject_id, 'session_year_id' => $session_year_id])->whereHas('student_attempt', function ($q) use ($student) {
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
            $scoring = app(OnlineExamScoringService::class);
            foreach ($online_exam_db['data'] as $data) {
                $exam_submitted_date = OnlineExamStudentAnswer::where(['student_id' => $student->id, 'online_exam_id' => $data['id']])->pluck('submitted_date')->first();

                // a question counts only when exactly its correct options were picked
                $score = $scoring->score($student->id, (int) $data['id']);

                // get the data of only attempted data
                $total_obtained_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->whereIn('id', $score['correct_choice_ids'])->first();
                $total_obtained_marks_exam = $total_obtained_marks_exam['sum(marks)'];
                $total_marks_exam = OnlineExamQuestionChoice::select(DB::raw('sum(marks)'))->where('online_exam_id', $data['id'])->first();
                $total_marks_exam = $total_marks_exam['sum(marks)'];

                $exam_list_data[] = [
                    'online_exam_id' => $data['id'],
                    'subject' => [
                        'id' => $data['subject_id'],
                        'name' => $data['subject']['name'] . ' - ' . $data['subject']['type'],
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
            ResponseService::successResponse('Exam List Fetched Successfully', $exam_list ?? '');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getOnlineExamResult(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'online_exam_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $request->user()->student;

            // get the total questions count
            $total_questions = OnlineExamQuestionChoice::where('online_exam_id', $request->online_exam_id)->count();

            // a question counts only when exactly its correct options were picked
            $score = app(OnlineExamScoringService::class)->score($student->id, (int) $request->online_exam_id);
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
            ResponseService::successResponse('Exam Result Fetched Successfully', $exam_result ?? '');
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * An online exam the student may take: their class section (or class),
     * one of their subjects, this session and the current semester, as
     * getOnlineExamList lists them.
     */
    private function findStudentOnlineExam(Students $student, int $online_exam_id): ?OnlineExam
    {
        $session_year_id = getSettings('session_year')['session_year'];
        $student_session = StudentSessions::where('student_id', $student->id)
            ->where('session_year_id', $session_year_id)
            ->with('class_section')
            ->first();
        if (! $student_session || ! $student_session->class_section) {
            return null;
        }
        $class_section_id = $student_session->class_section_id;
        $class_id = $student_session->class_section->class_id;

        $currentSemester = null;
        if (ClassSessionConfig::classHasSemester($class_id, $session_year_id)) {
            $currentSemester = Semester::where('session_year_id', $session_year_id)
                ->get()
                ->first(fn($s) => $s->current);
        }

        return OnlineExam::where('id', $online_exam_id)
            ->where('session_year_id', $session_year_id)
            ->whereIn('subject_id', $this->studentSubjectIds($student))
            ->where(function ($query) use ($class_section_id, $class_id) {
                $query->where(fn ($q) => $q->where('model_type', 'App\Models\ClassSection')->where('model_id', $class_section_id))
                    ->orWhere(fn ($q) => $q->where('model_type', 'App\Models\ClassSchool')->where('model_id', $class_id));
            })
            ->when($currentSemester, fn ($query) => $query->where('semester_id', $currentSemester->id))
            ->first();
    }
}
