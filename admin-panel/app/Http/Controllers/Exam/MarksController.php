<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin panel: enter and correct exam marks.
 */
class MarksController extends Controller
{
    public function uploadMarks()
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $session_year_id = getSettings('session_year')['session_year'];

        $teacher_id = Auth::user()->teacher->id;

        $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)
            ->where('session_year_id', $session_year_id)
            ->pluck('class_section_id');

        $class_ids = ClassSection::whereIn('id', $class_section_id)->pluck('class_id');
        $classes = ClassSection::with('class', 'section', 'class.medium', 'streams')
            ->whereIn('id', $class_section_id)->whereIn('class_id', $class_ids)->get();

        return response(view('exams.upload-marks', compact('classes')));
    }

    public function marksList(Request $request)
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        if (! $request->exam_id || ! $request->subject_id || ! $request->class_id || ! $request->class_section_id) {
            return false;
        }
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

        $teacher_id = Auth::user()->teacher->id;
        $class_section_id = ClassSection::where('id', $request->class_section_id)->where('class_id', $request->class_id)->pluck('id');

        $exam = Exam::findOrFail($request->exam_id);
        $session_year_id = $exam->session_year_id;

        $allowed_class_section_ids = $this->teacherMarksClassSectionIds($session_year_id, $request->subject_id);
        if ($allowed_class_section_ids !== null && ! $allowed_class_section_ids->contains($request->class_section_id)) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $exam_timetable_id = ExamTimetable::where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id, 'subject_id' => $request->subject_id])->pluck('id')->first();

        $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id])->first();
        $starting_date = $starting_date_db['min(date)'];
        $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))->where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id])->first();
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
            $response = [
                'error' => true,
                'message' => trans('exam_not_completed_yet'),
            ];

            return response()->json($response);
        }

        // Fetching Students Data on Basis of Class Section ID and Session Year with Relation Exam Marks
        $studentIds = StudentSessions::where('class_section_id', $class_section_id)
            ->where('session_year_id', $session_year_id)
            ->pluck('student_id');

        $sql = Students::with(['user:id,first_name,last_name'])->with(['class_section.class.allSubjects' => function ($q) use ($request) {
            $q->where('subject_id', $request->subject_id)->with('subject');
        }])->with(['exam_marks' => function ($q) use ($exam_timetable_id) {
            $q->where('exam_timetable_id', $exam_timetable_id);
        }])->whereIn('id', $studentIds);

        $subject_total_marks = ExamTimetable::where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id, 'subject_id' => $request->subject_id])->pluck('total_marks');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%")
                    ->orWhere('mobile', 'LIKE', "%$search%");
            });
        }

        $total = $sql->count();

        $sql->orderBy($sort, $order);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        $class_subject = ClassSubject::where('subject_id', $request->subject_id)->where('class_id', $request->class_id)->where('session_year_id', $session_year_id)->first();

        foreach ($res as $row) {
            if ($class_subject == null) {
                continue;
            }

            if ($class_subject->type == 'Elective') {
                $student_subject = StudentSubject::where('student_id', $row->id)->where('subject_id', $request->subject_id)->where('class_section_id', $row->class_section_id)->where('session_year_id', $session_year_id)->first();
                if ($student_subject) {
                    $operate = '<a href="#" class="btn btn-xs btn-secondary btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                    $operate .= '<a href='.route('exams.destroy', $row->id).' class="btn btn-xs btn-secondary btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';
                    $tempRow['id'] = $row->id;
                    $tempRow['no'] = $no++;
                    $tempRow['student_name'] = $row->user->first_name.' '.$row->user->last_name;
                    $tempRow['student_id'] = $row->id;
                    foreach ($subject_total_marks as $total_marks) {
                        $tempRow['total_marks'] = $total_marks;
                    }
                    foreach ($row->exam_marks as $exam_result) {
                        $tempRow['exam_marks_id'] = $exam_result ? $exam_result->id : '';
                        $tempRow['obtained_marks'] = $exam_result ? $exam_result->obtained_marks : '';
                    }
                    $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
                    $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
                    $tempRow['operate'] = $operate;
                    $rows[] = $tempRow;
                }
            } else {
                $operate = '<a href="#" class="btn btn-xs btn-secondary btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                $operate .= '<a href='.route('exams.destroy', $row->id).' class="btn btn-xs btn-secondary btn-icon delete-form" data-id='.$row->id.'><i class="fa fa-trash"></i></a>';
                $tempRow['id'] = $row->id;
                $tempRow['no'] = $no++;
                $tempRow['student_name'] = $row->user->first_name.' '.$row->user->last_name;
                $tempRow['student_id'] = $row->id;
                foreach ($subject_total_marks as $total_marks) {
                    $tempRow['total_marks'] = $total_marks;
                }
                foreach ($row->exam_marks as $exam_result) {

                    $tempRow['exam_marks_id'] = $exam_result ? $exam_result->id : '';
                    $tempRow['obtained_marks'] = $exam_result ? $exam_result->obtained_marks : '';
                }
                $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
                $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
                $tempRow['operate'] = $operate;
                $rows[] = $tempRow;
            }
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function submitMarks(Request $request)
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'exam_id' => 'required|numeric',
            'class_id' => 'required|numeric',
            'subject_id' => 'required|numeric',
            'exam_marks.*.student_id' => 'required|numeric',
            'exam_marks.*.obtained_marks' => 'required|numeric|lte:exam_marks.*.total_marks',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        try {
            $teacher_id = Auth::user()->teacher->id;
            $exam_timetable = ExamTimetable::where(['exam_id' => $request->exam_id, 'class_id' => $request->class_id])->where('subject_id', $request->subject_id)->firstOrFail();

            $submitted_marks = (array) $request->exam_marks;
            // Teachers may only submit marks for students of their own class sections.
            if (! $this->canManageStudentMarks(array_column($submitted_marks, 'student_id'), $exam_timetable->session_year_id, $request->subject_id)) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ]);
            }
            // An existing marks row may only be overwritten with marks of the same timetable and student.
            foreach ($submitted_marks as $exam_marks) {
                if (! empty($exam_marks['exam_marks_id']) && ! ExamMarks::where('id', $exam_marks['exam_marks_id'])
                    ->where('exam_timetable_id', $exam_timetable->id)
                    ->where('student_id', $exam_marks['student_id'])
                    ->exists()) {
                    return response()->json([
                        'error' => true,
                        'message' => trans('no_permission_message'),
                    ]);
                }
            }

            foreach ($request->exam_marks as $exam_marks) {
                $passing_marks = $exam_timetable->passing_marks;
                if ($exam_marks['obtained_marks'] >= $passing_marks) {
                    $status = 1;
                } else {
                    $status = 0;
                }
                $marks_percentage = ($exam_marks['obtained_marks'] / $exam_marks['total_marks']) * 100;
                $exam_grade = findExamGrade($exam_timetable->session_year_id, $marks_percentage);

                if ($exam_grade == null) {
                    $response = [
                        'error' => true,
                        'message' => trans('grades_data_does_not_exists'),
                    ];

                    return response()->json($response);
                }

                ExamMarks::updateOrInsert(
                    ['id' => isset($exam_marks['exam_marks_id']) ? $exam_marks['exam_marks_id'] : null],
                    ['exam_timetable_id' => $exam_timetable->id, 'student_id' => $exam_marks['student_id'], 'subject_id' => $request->subject_id, 'obtained_marks' => $exam_marks['obtained_marks'], 'passing_status' => $status, 'session_year_id' => $exam_timetable->session_year_id, 'grade' => $exam_grade]
                );
            }
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function updateExamResultMarks(Request $request)
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'edit.*.marks_id' => 'required|numeric',
            'edit.*.obtained_marks' => 'required|numeric|lte:edit.*.total_marks',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $teacher_id = Auth::user()->teacher->id;

            foreach ((array) $request->edit as $data) {
                $mark = ExamMarks::with('timetable')->find($data['marks_id'] ?? null);
                // The marks row must belong to the submitted student and exam, and Teachers
                // may only edit marks of students in their own class sections.
                if (! $mark || ! $mark->timetable
                    || (int) $mark->student_id !== (int) ($data['student_id'] ?? 0)
                    || (int) $mark->timetable->exam_id !== (int) ($data['exam_id'] ?? 0)
                    || ! $this->canManageStudentMarks([$mark->student_id], $mark->timetable->session_year_id, $mark->subject_id)) {
                    return response()->json([
                        'error' => true,
                        'message' => trans('no_permission_message'),
                    ]);
                }
            }

            foreach ($request->edit as $data) {
                $class_id = ExamClass::where('exam_id', $data['exam_id'])->pluck('class_id')->first();
                $marks_db = ExamMarks::find($data['marks_id']);
                $marks_db->obtained_marks = $data['obtained_marks'];

                $passing_marks = $data['passing_marks'];
                if ($data['obtained_marks'] >= $passing_marks) {
                    $marks_db->passing_status = 1;
                } else {
                    $marks_db->passing_status = 0;
                }

                $marks_percentage = ($data['obtained_marks'] / $data['total_marks']) * 100;
                $results = ExamResult::where('exam_id', $data['exam_id'])
                    ->where('student_id', $data['student_id'])
                    ->get(['id', 'session_year_id']);

                $exam_result_id = $results->pluck('id');
                $session_year_id = $results->pluck('session_year_id')->first();

                $grade = findExamGrade($session_year_id, $marks_percentage);
                if ($grade == null) {
                    $response = [
                        'error' => true,
                        'message' => trans('grades_data_does_not_exists'),
                    ];

                    return response()->json($response);
                }
                $marks_db->grade = $grade;
                $marks_db->save();

                // $exam_result_id = ExamResult::where(['' => $data[''], 'student_id' => $data['student_id']])->pluck('id');

                $exam = Exam::with(['marks' => function ($query) use ($data) {
                    $query->with('student:id,class_section_id')->selectRaw('SUM(obtained_marks) as total_obtained_marks,student_id')->where('student_id', $data['student_id'])->groupBy('student_id');
                }, 'timetable' => function ($query) use ($data, $class_id) {
                    $query->selectRaw(',SUM(total_marks) as total_marks')->where(['exam_id' => $data['exam_id'], 'class_id' => $class_id]);
                }])->where('id', $data['exam_id'])->first();

                foreach ($exam->marks as $exam_marks) {
                    $percentage = ($exam_marks['total_obtained_marks'] * 100) / $exam->timetable[0]['total_marks'];

                    $grade = findExamGrade($exam->session_year_id, $percentage);
                    if ($grade == null) {
                        $response = [
                            'error' => true,
                            'message' => trans('grades_data_does_not_exists'),
                        ];

                        return response()->json($response);
                    }

                    $exam_result_db = ExamResult::find($exam_result_id)->first();
                    $exam_result_db->obtained_marks = $exam_marks['total_obtained_marks'];
                    $exam_result_db->percentage = round($percentage, 2);
                    $exam_result_db->grade = $grade;
                    $exam_result_db->save();

                    $response = [
                        'error' => false,
                        'message' => trans('data_update_successfully'),
                    ];
                }
            }
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    /**
     * Class sections a Teacher may handle marks of the subject in: those they are
     * the class teacher of (as showExamResult checks) or teach the subject in.
     * Returns null for other roles, which are limited by their permissions alone.
     */
    private function teacherMarksClassSectionIds($session_year_id, $subject_id): ?Collection
    {
        $user = Auth::user();
        if (! $user->hasRole('Teacher')) {
            return null;
        }
        $teacher_id = $user->teacher->id ?? null;
        if (! $teacher_id) {
            return collect();
        }
        $inSessionYear = function ($query) use ($session_year_id) {
            $query->where('session_year_id', $session_year_id)->orWhereNull('session_year_id');
        };

        return ClassTeacher::where('class_teacher_id', $teacher_id)->where($inSessionYear)->pluck('class_section_id')
            ->merge(SubjectTeacher::where('teacher_id', $teacher_id)->where('subject_id', $subject_id)->where($inSessionYear)->pluck('class_section_id'))
            ->unique()
            ->values();
    }

    /**
     * Whether the user may handle the marks of these students: for Teachers, every
     * student must be in one of their class sections in that session year.
     */
    private function canManageStudentMarks(array $student_ids, $session_year_id, $subject_id): bool
    {
        $class_section_ids = $this->teacherMarksClassSectionIds($session_year_id, $subject_id);
        if ($class_section_ids === null) {
            return true;
        }
        $student_ids = array_values(array_unique(array_map('intval', $student_ids)));
        if (empty($student_ids) || $class_section_ids->isEmpty()) {
            return false;
        }
        $assigned = StudentSessions::whereIn('student_id', $student_ids)
            ->where('session_year_id', $session_year_id)
            ->whereIn('class_section_id', $class_section_ids)
            ->distinct()
            ->count('student_id');

        return $assigned === count($student_ids);
    }
}
