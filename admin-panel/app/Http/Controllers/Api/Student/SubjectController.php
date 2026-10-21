<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\ElectiveSubjectGroup;
use App\Models\Semester;
use App\Models\Settings;
use App\Models\StudentSubject;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Student app: the student's subjects and elective choices.
 */
class SubjectController extends Controller
{
    use StudentAccess;

    public function subjects(Request $request)
    {
        try {
            $user = $request->user();
            $subjects = $user->student->subjects();

            if ($subjects['elective_subject'] == []) {
                $subjects['elective_subject'] = null;
            }

            ResponseService::successResponse('Student Subject Fetched Successfully.', $subjects);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function classSubjects(Request $request)
    {
        try {
            $user = $request->user();
            $subjects = $user->student->classSubjects();
            ResponseService::successResponse('Class Subject Fetched Successfully.', $subjects);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function selectSubjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject_group' => 'required|array',
            'subject_group.*.id' => 'required|integer',
            'subject_group.*.subject_id' => 'required|array',
            'subject_group.*.subject_id.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {

            $currentSemester = Semester::get()->first(function ($semester) {
                return $semester->current;
            });
            $semester_id = null;
            $student = $request->user()->student;
            $class_section = $student->class_section;
            $class_id = $class_section->class->id;
            $session_year_id = Settings::select('message')->where('type', 'session_year')->pluck('message')->first();
            $class = ClassSchool::withSessionConfigFor((int) $session_year_id)->where('id', $class_id)->first();

            $include_semester = $class?->getIncludeSemestersForSession((int) $session_year_id);
            if ($include_semester == 1) {
                $semester_id = $currentSemester->id;
            }

            // The elective groups the class offers this session (and semester),
            // as classSubjects() lists them to the app.
            $elective_groups = ElectiveSubjectGroup::where('class_id', $class_id)
                ->where('session_year_id', $session_year_id)
                ->when($semester_id, fn ($q) => $q->where('semester_id', $semester_id))
                ->with('electiveSubjects')
                ->get()
                ->keyBy('id');

            $seen_groups = [];
            foreach ($request->subject_group as $subject_group) {
                $group = $elective_groups->get((int) $subject_group['id']);
                if (! $group || in_array($group->id, $seen_groups)) {
                    ResponseService::validationError('Invalid subject group.');
                }
                $seen_groups[] = $group->id;

                $group_subject_ids = $group->electiveSubjects->pluck('subject_id')->map(fn ($id) => (int) $id)->all();
                $selected_ids = array_map('intval', $subject_group['subject_id']);
                if (count($selected_ids) !== count(array_unique($selected_ids)) || array_diff($selected_ids, $group_subject_ids)) {
                    ResponseService::validationError('Invalid subject selected.');
                }

                // Together with what the student already picked in this group,
                // no more than the group allows.
                $already_selected = StudentSubject::where([
                    'student_id' => $student->id,
                    'semester_id' => $semester_id ?? null,
                    'class_section_id' => $class_section->id,
                    'session_year_id' => intval($session_year_id),
                ])->whereIn('subject_id', $group_subject_ids)->pluck('subject_id')->map(fn ($id) => (int) $id)->all();
                if (count(array_unique(array_merge($already_selected, $selected_ids))) > (int) $group->total_selectable_subjects) {
                    ResponseService::validationError('You can select only '.$group->total_selectable_subjects.' subjects in this group.');
                }
            }

            $student_subject = [];
            foreach ($request->subject_group as $key => $subject_group) {
                // $subject_group_id = $subject_group['id'];
                foreach ($subject_group['subject_id'] as $subject_id) {

                    $if_subject_already_selected = StudentSubject::where([
                        'student_id' => $student->id,
                        'subject_id' => $subject_id,
                        'semester_id' => $semester_id ?? null,
                        'class_section_id' => $class_section->id,
                        'session_year_id' => intval($session_year_id),
                    ])->first();

                    if (! $if_subject_already_selected) {
                        $student_subject[] = [
                            'student_id' => $student->id,
                            'subject_id' => $subject_id,
                            'semester_id' => $semester_id ?? null,
                            'class_section_id' => $class_section->id,
                            'session_year_id' => intval($session_year_id),
                        ];
                    }
                }
            }
            StudentSubject::insert($student_subject);

            ResponseService::successResponse('Subject Selected Successfully', $student_subject);
        } catch (Throwable $e) {
            ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
