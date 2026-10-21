<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamTimetable;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Teacher app: exams, entering marks and students' results.
 */
class ExamController extends Controller
{
    use TeacherAccess;

    public function getExamList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'in:0,1,2,3',
            'publish' => 'in:0,1',
            'class_section_id' => 'nullable',
            'get_timetable' => 'nullable',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {

            $teacher = Auth::user()->teacher;

            $session_year_id = getSettings('session_year')['session_year'];

            $teacherSemesterIds = [];

            if (isset($request->class_section_id)) {

                // Verify teacher access to requested section
                $classTeacherQuery = ClassTeacher::where('class_teacher_id', $teacher->id)
                    ->where('session_year_id', $session_year_id)
                    ->where('class_section_id', $request->class_section_id);

                if (! $classTeacherQuery->exists()) {
                    ResponseService::errorResponse('no_permission_message');
                }

                $class_section_ids = collect([$request->class_section_id]);

                $class_ids = ClassSection::where('id', $request->class_section_id)
                    ->pluck('class_id');

                // Semester access for this section
                $teacherSemesterIds = $classTeacherQuery
                    ->whereNotNull('semester_id')
                    ->pluck('semester_id')
                    ->unique()
                    ->toArray();

            } else {

                $classTeacherRecords = ClassTeacher::where('class_teacher_id', $teacher->id)
                    ->where('session_year_id', $session_year_id)
                    ->get();

                $class_section_ids = $classTeacherRecords
                    ->pluck('class_section_id')
                    ->unique();

                $class_ids = ClassSection::whereIn('id', $class_section_ids)
                    ->pluck('class_id');

                $teacherSemesterIds = $classTeacherRecords
                    ->whereNotNull('semester_id')
                    ->pluck('semester_id')
                    ->unique()
                    ->toArray();
            }

            $sql = ExamClass::with(
                'exam.session_year:id,name',
                'exam.timetable.subject',
                'class',
                'class.medium',
                'class.streams'
            )
                ->whereIn('class_id', $class_ids)

                ->whereHas('exam', function ($q) use (
                    $session_year_id,
                    $class_ids,
                    $teacherSemesterIds
                ) {

                    $q->where('session_year_id', $session_year_id);

                    foreach ($class_ids as $class_id) {

                        $hasSemester = ClassSessionConfig::classHasSemester(
                            $class_id,
                            $session_year_id
                        );

                        if ($hasSemester) {

                            $q->whereIn('semester_id', $teacherSemesterIds);
                        }
                    }
                });

            if (isset($request->publish)) {

                $publish = $request->publish;

                $sql->whereHas('exam', function ($q) use ($publish) {
                    $q->where('publish', $publish);
                });
            }

            $exam_data_db = $sql->get();

            foreach ($exam_data_db as $data) {

                // date status
                $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))
                    ->where('exam_id', $data->exam_id)
                    ->whereIn('class_id', $class_ids)
                    ->first();

                $starting_date = $starting_date_db['min(date)'];

                $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))
                    ->where('exam_id', $data->exam_id)
                    ->whereIn('class_id', $class_ids)
                    ->first();

                $ending_date = $ending_date_db['max(date)'];

                $currentTime = Carbon::now();

                $current_date = date($currentTime->toDateString());

                if ($current_date >= $starting_date && $current_date <= $ending_date) {
                    $exam_status = '1';
                } elseif ($current_date < $starting_date) {
                    $exam_status = '0';
                } else {
                    $exam_status = '2';
                }

                // $request->status
                // 0 = all
                // 1 = upcoming
                // 2 = ongoing
                // 3 = completed

                if (isset($request->status)) {

                    if ($request->status == 0) {

                        $allowExam = true;

                    } elseif ($request->status == 1) {

                        $allowExam = ($exam_status == 0);

                    } elseif ($request->status == 2) {

                        $allowExam = ($exam_status == 1);

                    } else {

                        $allowExam = ($exam_status == 2);
                    }

                } else {

                    $allowExam = true;
                }

                if ($allowExam) {

                    $examPayload = [
                        'id' => $data->exam->id,
                        'name' => $data->exam->name,
                        'description' => $data->exam->description,
                        'publish' => $data->exam->publish,
                        'session_year' => $data->exam->session_year->name,
                        'exam_starting_date' => $starting_date,
                        'exam_ending_date' => $ending_date,
                        'exam_status' => $exam_status,
                        'class_id' => $data->class_id,
                        'class_name' => $data->class->name.'-'.$data->class->medium->name,
                        'class_streams' => $data->class->streams->name ?? null,
                    ];

                    if ($request->get_timetable == 1) {
                        $examPayload['exam_timetable'] = $data->exam->timetable;
                    }

                    $exam_data[] = $examPayload;
                }
            }

            ResponseService::successResponse(
                'Exam Marks Fetched Successfully',
                $exam_data ?? []
            );

        } catch (\Exception $e) {

            ResponseService::errorResponse(
                'error_occurred',
                null,
                103,
                $e
            );
        }
    }

    public function getExamDetails(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|integer',
            'class_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $sessionYearId = getSettings('session_year')['session_year'];

            $classId = $request->class_id;

            // Only for a class the teacher teaches in (as class teacher or subject teacher).
            if (! ClassSection::where('class_id', $classId)->whereIn('id', $this->teacherClassSectionIds())->exists()) {
                ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $classSection = ClassSection::with('class', 'section', 'class.medium', 'class.streams')
                ->where('class_id', $classId)
                ->first();

            $examData = Exam::with([
                'semester',
                'timetable' => function ($query) use ($request, $classId) {
                    $query->where([
                        'exam_id' => $request->exam_id,
                        'class_id' => $classId,
                    ])->with('subject');
                },
            ])
                ->where('id', $request->exam_id)
                ->where('session_year_id', $sessionYearId)
                ->get();

            // Transform: move semester inside subject
            $examData = $examData->map(function ($exam) {

                $semester = $exam->semester;

                if ($exam->timetable) {
                    $exam->timetable->transform(function ($tt) use ($semester) {

                        if ($tt->subject) {
                            $tt->subject->semester = $semester;
                        }

                        return $tt;
                    });
                }
                unset($exam->semester);

                return $exam;
            });

            ResponseService::successResponse('Data Fetched Successfully', $examData, [
                'class_id' => $classId,
                'class_section_id' => $classSection->id,
                'class_name' => $classSection->class->name.'-'.$classSection->section->name.' '.$classSection->class->medium->name,
                'stream_name' => $classSection->class->streams->name ?? null,
                'code' => 200,
            ]);
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function submitExamMarksBySubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required|numeric',
            'exam_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'marks_data' => 'required|array',
            'marks_data.*.student_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }

        try {
            $exam_published = Exam::where(['id' => $request->exam_id, 'publish' => 1])->first();
            if (isset($exam_published)) {
                ResponseService::errorResponse('exam_published', null, 400);
            }

            // Marks are entered by the class teacher (as the app's result screens do) or by
            // the teacher of the subject in this class section, for its own students only.
            if (! $this->isClassTeacherOf($request->class_section_id)
                && ! $this->teachesSubject($request->class_section_id, $request->subject_id)) {
                ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
            }
            if (! $this->allStudentsInClassSection(collect($request->marks_data)->pluck('student_id'), $request->class_section_id)) {
                ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $class_id = ClassSection::where('id', $request->class_section_id)->pluck('class_id');

            // check exam status
            $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
            $starting_date = $starting_date_db['min(date)'];
            $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
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
            if ($exam_status != 2) {
                ResponseService::errorResponse('exam_not_completed_yet', null, 400);
            } else {
                $grades = Grade::orderBy('ending_range', 'desc')->get();
                $exam_timetable = ExamTimetable::where('exam_id', $request->exam_id)
                    ->where('class_id', ClassSection::where('id', $request->class_section_id)->value('class_id'))
                    ->where('subject_id', $request->subject_id)
                    ->firstOrFail();
                foreach ($request->marks_data as $marks) {
                    if ($this->obtainedMarksOutOfRange($marks['obtained_marks'] ?? null, $exam_timetable->total_marks)) {
                        ResponseService::errorResponse('Obtained marks must be between 0 and the total marks.', null, 400);
                    }
                }
                // Every row needs a grade, found before anything is written so that one row
                // without a grade band can't leave the rows before it saved.
                $exam_grades = [];
                foreach ($request->marks_data as $index => $marks) {
                    $marks_percentage = ($marks['obtained_marks'] / $exam_timetable['total_marks']) * 100;

                    $exam_grades[$index] = findExamGrade($exam_timetable->session_year_id, $marks_percentage);
                    if ($exam_grades[$index] == null) {
                        ResponseService::errorResponse('grades_data_does_not_exists', null, 400);
                    }
                }
                foreach ($request->marks_data as $index => $marks) {
                    $passing_marks = $exam_timetable->passing_marks;
                    if ($marks['obtained_marks'] >= $passing_marks) {
                        $status = 1;
                    } else {
                        $status = 0;
                    }
                    $exam_grade = $exam_grades[$index];

                    $exam_marks = ExamMarks::where(['exam_timetable_id' => $exam_timetable->id, 'subject_id' => $request->subject_id, 'student_id' => $marks['student_id']])->first();
                    if ($exam_marks) {
                        $exam_marks_db = ExamMarks::find($exam_marks->id);
                        $exam_marks_db->obtained_marks = $marks['obtained_marks'];
                        $exam_marks_db->passing_status = $status;
                        $exam_marks_db->grade = $exam_grade;
                        $exam_marks_db->save();
                        $updated = true;
                    } else {
                        $exam_result_marks[] = [
                            'exam_timetable_id' => $exam_timetable->id,
                            'student_id' => $marks['student_id'],
                            'subject_id' => $request->subject_id,
                            'obtained_marks' => $marks['obtained_marks'],
                            'passing_status' => $status,
                            'session_year_id' => $exam_timetable->session_year_id,
                            'grade' => $exam_grade,
                        ];
                    }
                }
                if (isset($exam_result_marks)) {
                    ExamMarks::insert($exam_result_marks);
                }
                // Respond once every row is saved: replying (which exits) on the first
                // existing row used to drop the marks of every student after it.
                ResponseService::successResponse(isset($updated) ? 'data_update_successfully' : 'data_store_successfully');
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function submitExamMarksByStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'exam_id' => 'required|numeric',
            'student_id' => 'required|numeric',
            'marks_data' => 'required|array',
            'marks_data.*.subject_id' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $exam_published = Exam::where(['id' => $request->exam_id, 'publish' => 1])->first();
            if (isset($exam_published)) {
                ResponseService::errorResponse('exam_published', null, 400);
            }

            // The student must be in this class section, and every subject written must be one
            // the teacher may mark here: all of them as class teacher, otherwise their own.
            if (! $this->allStudentsInClassSection([$request->student_id], $request->class_section_id)) {
                ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $isClassTeacher = $this->isClassTeacherOf($request->class_section_id);
            foreach ($request->marks_data as $marks) {
                if (! $isClassTeacher && ! $this->teachesSubject($request->class_section_id, $marks['subject_id'] ?? null)) {
                    ResponseService::errorResponse('You are not assigned to this class section and subject combination.', null, 403);
                }
            }

            // $class_section_id = Students::where('id',$request->student_id)->pluck('class_section_id');
            $class_id = ClassSection::where('id', $request->class_section_id)->pluck('class_id');

            // exam status
            $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
            $starting_date = $starting_date_db['min(date)'];
            $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $class_id])->first();
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

            if ($exam_status != 2) {
                ResponseService::errorResponse('exam_not_completed_yet', null, 400);
            } else {
                $grades = Grade::orderBy('ending_range', 'desc')->get();

                // This class's timetable entry for each subject, with the marks checked
                // against it before anything is written.
                $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
                $exam_timetables = [];
                foreach ($request->marks_data as $marks) {
                    $exam_timetables[$marks['subject_id']] = ExamTimetable::where(['exam_id' => $request->exam_id, 'class_id' => $classId, 'subject_id' => $marks['subject_id']])->firstOrFail();
                    if ($this->obtainedMarksOutOfRange($marks['obtained_marks'] ?? null, $exam_timetables[$marks['subject_id']]->total_marks)) {
                        ResponseService::errorResponse('Obtained marks must be between 0 and the total marks.', null, 400);
                    }
                }

                // Every row needs a grade, found before anything is written so that one row
                // without a grade band can't leave the rows before it saved.
                $exam_grades = [];
                foreach ($request->marks_data as $index => $marks) {
                    $exam_timetable = $exam_timetables[$marks['subject_id']];
                    $marks_percentage = ($marks['obtained_marks'] / $exam_timetable->total_marks) * 100;

                    $exam_grades[$index] = findExamGrade($exam_timetable->session_year_id, $marks_percentage);
                    if ($exam_grades[$index] == null) {
                        ResponseService::errorResponse('grades_data_does_not_exists', null, 400);
                    }
                }

                foreach ($request->marks_data as $index => $marks) {
                    $exam_timetable = $exam_timetables[$marks['subject_id']];
                    $passing_marks = $exam_timetable->passing_marks;
                    if ($marks['obtained_marks'] >= $passing_marks) {
                        $status = 1;
                    } else {
                        $status = 0;
                    }
                    $exam_grade = $exam_grades[$index];

                    $exam_marks = ExamMarks::where(['exam_timetable_id' => $exam_timetable->id, 'student_id' => $request->student_id, 'subject_id' => $marks['subject_id']])->first();
                    if ($exam_marks) {
                        $exam_marks_db = ExamMarks::find($exam_marks->id);
                        $exam_marks_db->obtained_marks = $marks['obtained_marks'];
                        $exam_marks_db->passing_status = $status;
                        $exam_marks_db->grade = $exam_grade;
                        $exam_marks_db->save();
                        $updated = true;
                    } else {
                        $exam_result_marks[] = [
                            'exam_timetable_id' => $exam_timetable->id,
                            'student_id' => $request->student_id,
                            'subject_id' => $marks['subject_id'],
                            'obtained_marks' => $marks['obtained_marks'],
                            'passing_status' => $status,
                            'session_year_id' => $exam_timetable->session_year_id,
                            'grade' => $exam_grade,
                        ];
                    }
                }
                if (isset($exam_result_marks)) {
                    ExamMarks::insert($exam_result_marks);
                }
                // Respond once every row is saved: replying (which exits) on the first
                // existing row used to drop the marks of every student after it.
                ResponseService::successResponse(isset($updated) ? 'data_update_successfully' : 'data_store_successfully');
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function GetStudentExamResult(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            // $teacher_id = Auth::user()->teacher->id;
            $class_section_id = Students::where('id', $request->student_id)->pluck('class_section_id');

            $class_data = ClassSection::where('id', $class_section_id)->with('class.medium', 'section')->get()->first();

            $exam_marks_db = ExamClass::with(['exam.timetable' => function ($q) use ($request, $class_data) {
                $q->where('class_id', $class_data->class_id)->with(['exam_marks' => function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                }])->with('subject:id,name,type,image,code');
            }])->with(['exam.results' => function ($q) use ($request) {
                $q->where('student_id', $request->student_id)->with(['student' => function ($q) {
                    $q->select('id', 'user_id', 'roll_number')->with('user:id,first_name,last_name');
                }])->with('session_year:id,name');
            }])->where('class_id', $class_data->class_id)->get();

            if (count($exam_marks_db)) {
                foreach ($exam_marks_db as $data_db) {
                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $data_db->exam_id, 'class_id' => $class_data->class_id])->first();
                    $starting_date = $starting_date_db['min(date)'];
                    $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $data_db->exam_id, 'class_id' => $class_data->class_id])->first();
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

                    // check wheather exam is completed or not
                    if ($exam_status == 2) {
                        $marks_array = [];

                        // check wheather timetable exists or not
                        if (count($data_db->exam->timetable)) {
                            foreach ($data_db->exam->timetable as $timetable_db) {
                                $total_marks = $timetable_db->total_marks;
                                $exam_marks = [];
                                if (count($timetable_db->exam_marks)) {
                                    foreach ($timetable_db->exam_marks as $marks_data) {
                                        $exam_marks = [
                                            'marks_id' => $marks_data->id,
                                            'subject_name' => $marks_data->subject->name,
                                            'subject_type' => $marks_data->subject->type,
                                            'total_marks' => $total_marks,
                                            'obtained_marks' => $marks_data->obtained_marks,
                                            'grade' => $marks_data->grade,
                                        ];
                                    }
                                } else {
                                    $exam_marks = (object) [];
                                }
                                if ($exam_marks != (object) []) {
                                    $marks_array[] = [
                                        'subject_id' => $timetable_db->subject->id,
                                        'subject_name' => $timetable_db->subject->name,
                                        'subject_type' => $timetable_db->subject->type,
                                        'total_marks' => $total_marks,
                                        'subject_code' => $timetable_db->subject->code,
                                        'marks' => $exam_marks,
                                    ];
                                }
                            }

                            $exam_result = [];
                            if (count($data_db->exam->results)) {
                                foreach ($data_db->exam->results as $result_data) {
                                    $exam_result = [
                                        'result_id' => $result_data->id,
                                        'exam_id' => $result_data->exam_id,
                                        'exam_name' => $data_db->exam->name,
                                        'class_name' => $class_data->class->name.'-'.$class_data->section->name.' '.$class_data->class->medium->name,
                                        'student_name' => $result_data->student->user->first_name.' '.$result_data->student->user->last_name,
                                        'exam_date' => $starting_date,
                                        'total_marks' => $result_data->total_marks,
                                        'obtained_marks' => $result_data->obtained_marks,
                                        'percentage' => $result_data->percentage,
                                        'grade' => $result_data->grade,
                                        'session_year' => $result_data->session_year->name,
                                    ];
                                }
                            } else {
                                $exam_result = (object) [];
                            }
                            if ($marks_array != null && $exam_result != null) {
                                $data[] = [
                                    'exam_id' => $data_db->exam_id,
                                    'exam_name' => $data_db->exam->name,
                                    'exam_date' => $starting_date,
                                    'marks_data' => $marks_array,
                                    'result' => $exam_result,
                                ];
                            }
                        }
                    }
                }
                ResponseService::successResponse('Exam Marks Fetched Successfully', $data ?? []);
            } else {
                ResponseService::successResponse('Exam Marks Fetched Successfully', []);
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function GetStudentExamMarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|nullable',
        ]);
        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            // $teacher_id = Auth::user()->teacher->id;
            $class_section_id = Students::where('id', $request->student_id)->pluck('class_section_id');

            $class_data = ClassSection::where('id', $class_section_id)->with('class.medium', 'section')->get()->first();

            $exam_marks_db = ExamClass::with(['exam.timetable' => function ($q) use ($request, $class_data) {
                $q->where('class_id', $class_data->class_id)->with(['exam_marks' => function ($q) use ($request) {
                    $q->where('student_id', $request->student_id);
                }])->with('subject:id,name,type,image');
            }])->where('class_id', $class_data->class_id)->get();

            if (count($exam_marks_db)) {
                foreach ($exam_marks_db as $data_db) {
                    $marks_array = [];
                    foreach ($data_db->exam->timetable as $marks_db) {
                        $exam_marks = [];
                        if (count($marks_db->exam_marks)) {
                            foreach ($marks_db->exam_marks as $marks_data) {
                                $exam_marks = [
                                    'marks_id' => $marks_data->id,
                                    'subject_name' => $marks_data->subject->name,
                                    'subject_type' => $marks_data->subject->type,
                                    'total_marks' => $marks_data->timetable->total_marks,
                                    'obtained_marks' => $marks_data->obtained_marks,
                                    'grade' => $marks_data->grade,
                                ];
                            }
                        } else {
                            $exam_marks = [];
                        }
                        if ($exam_marks != []) {
                            $marks_array[] = [
                                'subject_id' => $marks_db->subject->id,
                                'subject_name' => $marks_db->subject->name,
                                'marks' => $exam_marks,
                            ];
                        }
                    }
                    $data[] = [
                        'exam_id' => $data_db->exam_id,
                        'exam_name' => $marks_db->exam->name,
                        'marks_data' => $marks_array,
                    ];
                }
                ResponseService::successResponse('Exam Marks Fetched Successfully', $data);
            } else {
                ResponseService::successResponse('Exam Marks Fetched Successfully', []);
            }
        } catch (\Exception $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getStudentResultPdf(Request $request)
    {
        try {
            if (! $this->teachesStudent($request->student_id)) {
                ResponseService::errorResponse('no_permission_message', null, 403);
            }

            $father_name = null;
            $mother_name = null;
            $guardian_name = null;

            $id = $request->student_id;
            $date = date('d-m-Y', strtotime(Carbon::now()->toDateString()));

            $settings = getSettings();
            $sessionYear = SessionYear::select('name')->where('id', $settings['session_year'])->pluck('name')->first();

            $student = Students::select('id', 'roll_number', 'admission_no', 'admission_date', 'user_id', 'class_section_id', 'guardian_id', 'father_id', 'mother_id')->with('user:id,first_name,last_name,dob', 'class_section.class:id,name,medium_id,stream_id', 'class_section.class.medium:id,name', 'class_section.class.streams:id,name', 'father:id,first_name,last_name', 'guardian:id,first_name,last_name')->where('id', $id)->first();

            $student_name = $student->user->first_name.' '.$student->user->last_name;

            if ($student->father) {
                $father_name = $student->father->first_name.' '.$student->father->last_name;
                $mother_name = $student->mother->first_name.' '.$student->mother->last_name;
            }

            if ($student->guardian) {
                $guardian_name = $student->guardian->first_name.' '.$student->guardian->last_name;
            }
            $admission_date = $student->admission_date;
            $gr_no = $student->admission_no;
            $dob = date('d-m-Y', strtotime($student->user->dob));
            $roll_number = $student->roll_number;
            $class_section = $student->class_section->class->name.' '.$student->class_section->section->name.' '.$student->class_section->class->medium->name.' '.($student->class_section->class->streams->name ?? '');

            $class_id = $student->class_section->class->id;

            $student_subject = $student->subjects();
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
                            ->where('student_id', $student->id)
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
            $totalMarks = null;

            foreach ($subjects as $subject) {
                $examObtainedMarks = null;
                $examTotalMarks = null;
                $subjectGrade = null;
                $subjectType = $subject->type;

                foreach ($examarray as $exam_data) {
                    foreach ($exam_data['timetable'] as $timetable) {
                        if ($timetable['subject_id'] == $subject->id) {
                            $exam_marks = $timetable['exam_marks'];

                            if ($exam_marks) {
                                $ObtainedMarks = $exam_marks['obtained_marks'];
                                $totalMarks = $timetable['total_marks'];

                                $examObtainedMarks += $ObtainedMarks;
                                $examTotalMarks += $totalMarks;

                                $subjectMarks[$subject->name.' ('.$subjectType.')'][$exam_data['name']] = $ObtainedMarks.'/'.$totalMarks;

                                if ($totalMarks > 0) {  // Check if totalMarks is greater than 0
                                    $percent = round(($ObtainedMarks / $totalMarks) * 100, 2);
                                    $subjectGrade = DB::table('grades')
                                        ->where('starting_range', '<=', $percent)
                                        ->where('ending_range', '>=', $percent)
                                        ->pluck('grade')
                                        ->first();
                                } else {
                                    $subjectGrade = null; // If totalMarks is 0 or not set, set grade to null
                                }
                            }

                            break 2;
                        }
                    }
                }

                // Store subject-wise total marks
                $subjectMarks[$subject->name.' ('.$subjectType.')']['total_obtained'] = $examObtainedMarks;
                $subjectMarks[$subject->name.' ('.$subjectType.')']['total_marks'] = $examTotalMarks;
                $subjectMarks[$subject->name.' ('.$subjectType.')']['grade'] = $subjectGrade;
            }

            $obtainmarks = array_sum(array_column($subjectMarks, 'total_obtained'));
            $totalmarks = array_sum(array_column($subjectMarks, 'total_marks')) ?? '';

            if ($obtainmarks == null && $totalmarks == null) {
                $percentage = null;
                $grade = null;
                $result = null;
            } else {
                $percentage = round(($obtainmarks / $totalmarks) * 100, 2);
                $grade = DB::table('grades')
                    ->where('starting_range', '<=', $percentage)
                    ->where('ending_range', '>=', $percentage)
                    ->pluck('grade')
                    ->first();
                $result = ($percentage >= 40) ? 'Passed' : 'Failed';
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
                'totalMarks' => $totalmarks,
                'obtainmarks' => $obtainmarks,
                'percentage' => $percentage,
                'grade' => $grade,
                'result' => $result,
            ];
            // Load the HTML
            $pdf = PDF::loadView('students.result_template', compact('data', 'settings', 'exams', 'subjects'));

            // Get The Output Of PDF
            $output = $pdf->output();
            ResponseService::successResponse('Data Fetched Successfully', null, ['pdf' => base64_encode($output)]);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /** Obtained marks must be a number from 0 up to the exam's total marks. */
    private function obtainedMarksOutOfRange($obtainedMarks, $totalMarks): bool
    {
        return ! is_numeric($obtainedMarks) || $obtainedMarks < 0 || $obtainedMarks > $totalMarks;
    }
}
