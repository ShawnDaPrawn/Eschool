<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSessionConfig;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Semester;
use App\Models\StudentSessions;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Student app: lessons and their topics.
 */
class LessonController extends Controller
{
    use StudentAccess;

    /**
     * @param
     * subject_id : 2
     * lesson_id : 1 //OPTIONAL
     */
    public function getLessons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'nullable|numeric',
            'subject_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $student = $request->user()->student;
            $session_year_id = getSettings('session_year')['session_year'];
            $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $student->id)
                ->with('class_section')
                ->first();
            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester($studentSession->class_section->class_id, $session_year_id);

            $data = Lesson::where('class_section_id', $studentSession->class_section->id)
                ->where('subject_id', $request->subject_id)->with('topic', 'file');

            if ($isClassHaveSemester && $currentSemester) {
                $data->where(function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                });
            }

            if ($request->lesson_id) {
                $data->where('id', $request->lesson_id);
            }
            $data = $data->get();

            return ResponseService::successResponse('Lessons Fetched Successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * @param
     * lesson_id : 1
     * topic_id : 1    //OPTIONAL
     */
    public function getLessonTopics(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => 'required|numeric',
            'topic_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $student = $request->user()->student;

            if (! $student) {
                return ResponseService::errorResponse('Student not found');
            }

            $session_year_id = getSettings('session_year')['session_year'];

            $studentSession = StudentSessions::where('session_year_id', $session_year_id)
                ->where('student_id', $student->id)
                ->with('class_section')
                ->first();

            if (! $studentSession || ! $studentSession->class_section) {
                return ResponseService::errorResponse('Student session not found');
            }

            $currentSemester = Semester::where('session_year_id', $session_year_id)
                ->get()
                ->first(fn ($s) => $s->current);

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $studentSession->class_section->class_id,
                $session_year_id
            );

            // Base query: only lessons of the student's own class section
            $query = LessonTopic::with(['lesson', 'file'])
                ->where('lesson_id', $request->lesson_id)
                ->whereHas('lesson', function ($q) use ($studentSession) {
                    $q->where('class_section_id', $studentSession->class_section_id);
                });

            // Semester filter via lesson relation
            if ($isClassHaveSemester && $currentSemester) {
                $query->whereHas('lesson', function ($q) use ($currentSemester) {
                    $q->where(function ($subQuery) use ($currentSemester) {
                        $subQuery->where('semester_id', $currentSemester->id)
                            ->orWhereNull('semester_id');
                    });
                });
            }

            // Optional topic filter
            if ($request->topic_id) {
                $query->where('id', $request->topic_id);
            }

            $data = $query->get();

            return ResponseService::successResponse('Topics Fetched Successfully', $data);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
