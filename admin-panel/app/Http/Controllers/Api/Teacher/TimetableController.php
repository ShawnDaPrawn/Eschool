<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Teacher;

use App\Http\Controllers\Api\Teacher\Concerns\TeacherAccess;
use App\Http\Controllers\Controller;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Timetable;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher app: the teacher's timetable and live-class links.
 */
class TimetableController extends Controller
{
    use TeacherAccess;

    public function getTeacherTimetable(Request $request)
    {
        try {
            // Get authenticated teacher
            $teacher = $this->currentTeacher();

            // Fetch timetable with required relations
            $timetable = $this->teacherTimetableQuery($teacher)
                ->with(['class_section', 'subject'])
                ->get();

            // Standard success response
            return ResponseService::successResponse(
                'Timetable Fetched Successfully',
                $timetable
            );
        } catch (\Exception $e) {
            // Centralized error handling
            return ResponseService::errorResponse(
                'error_occurred',
                null,
                103,
                $e
            );
        }
    }

    public function updateTimetableLink(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timetable_id' => 'required',
            'live_class_link' => 'nullable|url',
            'link_name' => 'nullable',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {

            // Only an entry of the teacher's own timetable (as getTeacherTimetable lists it).
            $timetable = $this->teacherTimetableQuery($this->currentTeacher())
                ->where('id', $request->timetable_id)
                ->first();
            if (! $timetable) {
                return ResponseService::errorResponse('no_permission_message', null, 403);
            }
            $timetable->live_class_url = $request->live_class_link;
            $timetable->link_name = $request->link_name;
            $timetable->save();
            return ResponseService::successResponse('data_update_successfully');
        } catch (\Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /** The timetable entries getTeacherTimetable shows the teacher (current session year and semester). */
    private function teacherTimetableQuery(Teacher $teacher)
    {
        // Fetch current session year ID once
        $sessionYearId = getSettings('session_year')['session_year'];

        // Get current semester for this session
        $currentSemester = Semester::where('session_year_id', $sessionYearId)
            ->get()
            ->first(fn ($s) => $s->current);

        // Get subject-teacher IDs for:
        // - current semester OR semester is null
        $subjectTeacherIds = SubjectTeacher::where('teacher_id', $teacher->id)
            ->where(function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester?->id)
                    ->orWhereNull('semester_id');
            })
            ->pluck('id');

        return Timetable::whereIn('subject_teacher_id', $subjectTeacherIds)
            ->where('session_year_id', $sessionYearId)
            ->where(function ($query) use ($currentSemester) {
                $query->where('semester_id', $currentSemester?->id)
                    ->orWhereNull('semester_id');
            });
    }
}
