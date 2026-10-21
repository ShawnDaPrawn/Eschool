<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSessionConfig;
use App\Models\Semester;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: announcements for the parent's children.
 */
class AnnouncementController extends Controller
{
    use ParentAccess;

    public function getAnnouncements(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|in:subject,noticeboard,class',
            'child_id' => 'required_if:type,subject,class|numeric',
            'subject_id' => 'required_if:type,subject|numeric',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $children = null;
            if ($request->type !== 'noticeboard') {
                // Outside the CheckChild group: resolve the child through the parent here.
                $children = $this->resolveChild($request, 106);
                $class_id = $children->class_section->class->id;
            }

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $class_section_id = null;
            if (! empty($children)) {
                $studentSession = StudentSessions::where('student_id', $children->id)
                    ->where('session_year_id', $session_year_id)
                    ->first();
                if (empty($studentSession)) {
                    return ResponseService::errorResponse('Invalid Child ID', null, 106);
                }
                $class_section_id = $studentSession->class_section_id;
            }

            if (isset($request->type) && $request->type == 'subject') {
                $table = SubjectTeacher::where('class_section_id', $class_section_id)->where('subject_id', $request->subject_id)->get()->pluck('id');
                if (empty($table)) {
                    return ResponseService::errorResponse('Invalid Subject ID', null, 106);
                }
            }
            $data = Announcement::with('file')->where('session_year_id', $session_year_id);

            if (! isset($request->type)) {
                // Everything this child may see (as on the student API), not the whole school's.
                $subject_teacher_ids = SubjectTeacher::where('class_section_id', $class_section_id)
                    ->whereIn('subject_id', $this->childSubjectIds($children))
                    ->pluck('id');
                $data = $data->where(function ($query) use ($class_id, $class_section_id, $subject_teacher_ids) {
                    $query->where('table_type', '')
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSchool")->where('table_id', $class_id))
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSection")->where('table_id', $class_section_id))
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\SubjectTeacher")->whereIn('table_id', $subject_teacher_ids));
                });
            }

            if (isset($request->type) && $request->type == 'noticeboard') {
                // The app's notice board (asked for without a child): school-wide notices
                // plus those for each of the parent's children's classes and class sections.
                $childSessions = StudentSessions::where('session_year_id', $session_year_id)
                    ->whereIn('student_id', $this->parentChildIds($request))
                    ->with('class_section:id,class_id')
                    ->get();
                $classIds = $childSessions->pluck('class_section.class_id')->filter()->unique()->values();
                $sectionIds = $childSessions->pluck('class_section_id')->unique()->values();
                $data = $data->where(function ($query) use ($classIds, $sectionIds) {
                    $query->where('table_type', '')
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSchool")->whereIn('table_id', $classIds))
                        ->orWhere(fn ($q) => $q->where('table_type', "App\Models\ClassSection")->whereIn('table_id', $sectionIds));
                });
            }

            if (isset($request->type) && $request->type == 'class') {
                $data = $data->where('table_type', "App\Models\ClassSchool")->where('table_id', $class_id);
            }

            if (isset($request->type) && $request->type == 'subject') {
                $isClassHaveSemester = ClassSessionConfig::classHasSemester($class_id, $session_year_id);
                $data = $data->where('table_type', "App\Models\SubjectTeacher")->whereIn('table_id', $table);
                if ($isClassHaveSemester) {
                    $currentSemester = Semester::where('session_year_id', $session_year_id)
                        ->get()
                        ->first(fn ($s) => $s->current);
                    $data->where('semester_id', $currentSemester->id);
                }
            }

            $data = $data->orderBy('id', 'desc')->paginate();

            return ResponseService::successResponse('Announcement Details Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Ids of a child's core and elective subjects.
     *
     * @return list<int>
     */
    private function childSubjectIds(Students $child): array
    {
        $subjects = $child->subjects();
        $core = array_column($subjects['core_subject'], 'subject_id');
        $elective = $subjects['elective_subject'] ?? [];
        if ($elective) {
            $elective = $elective->pluck('subject_id')->toArray();
        }

        return array_values(array_map('intval', array_merge($core, $elective ?: [])));
    }
}
