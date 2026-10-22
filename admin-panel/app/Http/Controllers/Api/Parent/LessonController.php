<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSection;
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
 * Parent app: a child's lessons and their topics.
 */
class LessonController extends Controller
{
    use ParentAccess;

    /**
     * @param
     * subject_id : 2
     * lesson_id : 1 //OPTIONAL
     */
    public function getLessons(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'lesson_id' => 'nullable|numeric',
            'subject_id' => 'required',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $children = $this->resolveChild($request);
            $session_year_id = getSettings('session_year')['session_year'];
            $class_section_id = StudentSessions::where('student_id', $children->id)
                ->where('session_year_id', $session_year_id)
                ->first()
                ->class_section_id;

            $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            $data = Lesson::where('class_section_id', $class_section_id)->where('subject_id', $request->subject_id)->with('topic', 'file');
            if ($request->lesson_id) {
                $data->where('id', $request->lesson_id);
            }

            if ($isClassHaveSemester && $currentSemester) {
                $data->where(function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                });
            }

            $data = $data->get();

            return ResponseService::successResponse('Lessons Fetched Successfully', $data, [], 200);
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
            'child_id' => 'required|numeric',
            'lesson_id' => 'required|numeric',
            'topic_id' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $children = $this->resolveChild($request);
            // $subjects = $children->subjects();
            $session_year_id = getSettings('session_year')['session_year'];

            // $student = $request->user()->student;
            $class_section_id = StudentSessions::where('student_id', $children->id)
                ->where('session_year_id', $session_year_id)
                ->first()
                ->class_section_id;

            $class_id = ClassSection::where('id', $class_section_id)->pluck('class_id');

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });

            $isClassHaveSemester = ClassSessionConfig::classHasSemester(
                $class_id,
                $session_year_id
            );

            // Only topics of a lesson taught to the child's own class section.
            $data = LessonTopic::where('lesson_id', $request->lesson_id)
                ->whereHas('lesson', function ($q) use ($class_section_id) {
                    $q->where('class_section_id', $class_section_id);
                })
                ->with(['file', 'lesson']);
            if ($request->topic_id) {
                $data->where('id', $request->topic_id);
            }
            if ($isClassHaveSemester && $currentSemester) {
                $data->whereHas('lesson', function ($q) use ($currentSemester) {
                    $q->where(function ($subQuery) use ($currentSemester) {
                        $subQuery->where('semester_id', $currentSemester->id)
                            ->orWhereNull('semester_id');
                    });
                });
            }

            $data = $data->get();

            return ResponseService::successResponse('Topics Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
