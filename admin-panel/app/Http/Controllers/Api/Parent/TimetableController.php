<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Http\Resources\TimetableCollection;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\Semester;
use App\Models\StudentSessions;
use App\Models\Timetable;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: a child's class timetable.
 */
class TimetableController extends Controller
{
    use ParentAccess;

    public function getTimetable(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $children = $this->resolveChild($request);
            $child_subject = $children->subjects();

            $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

            $elective_subjects = $child_subject['elective_subject'] ?? [];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
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

            $timetables = Timetable::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->when($isClassHaveSemester, function ($query) use ($currentSemester) {
                    $query->where('semester_id', $currentSemester->id);
                })
                ->with(['subject_teacher' => function ($q) use ($subject_id) {
                    $q->whereIn('subject_id', $subject_id);
                }])->orderBy('day', 'asc')->orderBy('start_time', 'asc')->get();

            $new_timetable = [];
            foreach ($timetables as $timetable) {
                if ($timetable->subject_teacher != null) {
                    $new_timetable[] = $timetable;
                }
            }

            return ResponseService::successResponse('Timetable Fetched Successfully', new TimetableCollection($new_timetable), [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
