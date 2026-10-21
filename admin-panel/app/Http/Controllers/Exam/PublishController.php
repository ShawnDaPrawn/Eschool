<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ClassSection;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\StudentSessions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Admin panel: publish an exam's results.
 */
class PublishController extends Controller
{
    public function publishExamResult($id)
    {
        // The publish button is on the exam list, which is gated on exam-create.
        if (! Auth::user()->can('exam-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        try {
            $exam_marks_db = ExamTimetable::where('exam_id', $id)->with('exam_marks')->get();

            foreach ($exam_marks_db as $data) {
                if ($data->exam_marks->count() == 0) {
                    return response()->json([
                        'error' => true,
                        'message' => trans('marks_are_not_submitted'),
                    ]);
                }
            }

            $exam = Exam::with([
                'marks' => function ($query) {
                    $query->selectRaw('
                        SUM(total_marks) as total_marks,
                        SUM(obtained_marks) as total_obtained_marks,
                        student_id
                    ')
                        ->groupBy('student_id');
                },
                'timetable' => function ($query) {
                    $query->selectRaw('exam_id')
                        ->groupBy('class_id');
                },
            ])
                ->with('exam_classes')
                ->where('id', $id)
                ->first();

            if (! $exam) {
                return response()->json([
                    'error' => true,
                    'message' => trans('exam_not_found'),
                ]);
            }

            foreach ($exam->exam_classes as $data) {

                $starting_date = ExamTimetable::where([
                    'exam_id' => $data->exam_id,
                    'class_id' => $data->class_id,
                ])->min('date');

                $ending_date = ExamTimetable::where([
                    'exam_id' => $data->exam_id,
                    'class_id' => $data->class_id,
                ])->max('date');

                $current_date = Carbon::now()->toDateString();

                if ($current_date >= $starting_date && $current_date <= $ending_date) {
                    $exam_status = 1; // Ongoing
                } elseif ($current_date < $starting_date) {
                    $exam_status = 0; // Upcoming
                } else {
                    $exam_status = 2; // Completed
                }

                break;
            }

            $size_of_timetable_array = $exam->timetable->count();
            $size_of_marks_array = $exam->marks->count();

            if (
                $exam_status == 2 &&
                $size_of_timetable_array != 0 &&
                $size_of_marks_array != 0
            ) {

                if ($exam->publish == 0) {

                    $studentIds = $exam->marks
                        ->pluck('student_id')
                        ->unique()
                        ->toArray();

                    /**
                     * Fetch class_section_id from student_sessions
                     */
                    $studentSessions = StudentSessions::whereIn('student_id', $studentIds)
                        ->where('session_year_id', $exam->session_year_id)
                        ->get()
                        ->keyBy('student_id');

                    $exam_result = [];

                    foreach ($exam->marks as $exam_marks) {

                        $percentage = (
                            $exam_marks->total_obtained_marks * 100
                        ) / $exam_marks->total_marks;

                        $grade = findExamGrade(
                            $exam->session_year_id,
                            $percentage
                        );

                        if ($grade == null) {

                            return response()->json([
                                'error' => true,
                                'message' => trans('grades_data_does_not_exists'),
                            ]);
                        }

                        $studentSession = $studentSessions->get($exam_marks->student_id);

                        if (! $studentSession) {

                            return response()->json([
                                'error' => true,
                                'message' => 'Student session not found.',
                            ]);
                        }

                        $exam_result[] = [
                            'exam_id' => $exam->id,
                            'class_section_id' => $studentSession->class_section_id,
                            'student_id' => $exam_marks->student_id,
                            'total_marks' => $exam_marks->total_marks,
                            'obtained_marks' => $exam_marks->total_obtained_marks,
                            'percentage' => round($percentage, 2),
                            'grade' => $grade,
                            'session_year_id' => $exam->session_year_id,
                        ];
                    }

                    ExamResult::insert($exam_result);

                    $exam->publish = 1;
                } else {

                    ExamResult::where('exam_id', $id)->delete();

                    $exam->publish = 0;
                }

                $exam->save();

                $response = [
                    'error' => false,
                    'message' => trans('data_store_successfully'),
                ];
            } else {

                $response = [
                    'error' => true,
                    'message' => trans('exam_not_completed_yet'),
                ];
            }
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    // public function uploadMarks()
    // {
    //     if (! Auth::user()->can('exam-upload-marks')) {
    //         $response = [
    //             'message' => trans('no_permission_message'),
    //         ];

    //         return redirect(route('home'))->withErrors($response);
    //     }

    //     $session_year_id = getSettings('session_year')['session_year'];
    //     $currentSemesterId = Semester::where('session_year_id', $session_year_id)->get()->first(function ($semester) {
    //         return $semester->current;
    //     })->id;

    //     $teacher_id = Auth::user()->teacher->id;

    //     $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)
    //         ->where('session_year_id', $session_year_id)
    //         ->where(function ($query) use ($currentSemesterId) {

    //             $query->whereNull('semester_id');

    //             if ($currentSemesterId) {
    //                 $query->orWhere('semester_id', $currentSemesterId);
    //             }
    //         })
    //         ->pluck('class_section_id');

    //     $class_ids = ClassSection::whereIn('id', $class_section_id)
    //         ->pluck('class_id');

    //     $classes = ClassSection::with(
    //         'class',
    //         'section',
    //         'class.medium',
    //         'streams'
    //     )
    //         ->whereIn('id', $class_section_id)
    //         ->whereIn('class_id', $class_ids)
    //         ->get();

    //     return response(view('exams.upload-marks', compact('classes')));
    // }

    public function getPublishExam(Request $request, $class_section_id)
    {

        try {
            $class_id = ClassSection::where('id', $class_section_id)->value('class_id');
            $semester_id = $request->query('semester_id');

            $exam_data = ExamClass::with('exam')
                ->where('class_id', $class_id)
                ->whereHas('exam', function ($query) use ($semester_id) {
                    $query->where('publish', 1)
                        ->when($semester_id, function ($q) use ($semester_id) {
                            $q->where('semester_id', $semester_id);
                        });
                })->get();

            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exam_data,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }
}
