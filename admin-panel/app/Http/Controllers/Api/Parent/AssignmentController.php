<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\ClassSessionConfig;
use App\Models\StudentSessions;
use App\Models\Term;
use App\Services\ResponseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: a child's assignments.
 */
class AssignmentController extends Controller
{
    use ParentAccess;

    /**
     * @param
     * assignment_id : 1    //OPTIONAL
     * subject_id : 1       //OPTIONAL
     */
    public function getAssignments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required|numeric',
            'assignment_id' => 'nullable|numeric',
            'subject_id' => 'nullable|numeric',
            'is_submitted' => 'nullable|numeric',
            'session_year_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            $children = $this->resolveChild($request);
            $child_subject = $children->subjects();
            $child_id = $children->id;
            $core_subjects = array_column($child_subject['core_subject'], 'subject_id');

            $elective_subjects = $child_subject['elective_subject'] ?? [];

            $session_year = getSettings('session_year');
            $session_year_id = $request->session_year_id ?? $session_year['session_year'];

            if ($elective_subjects) {
                $elective_subjects = $elective_subjects->pluck('subject_id')->toArray();
            }

            $subject_id = array_merge($core_subjects, $elective_subjects);

            $student_session = StudentSessions::where('student_id', $child_id)
                ->where('session_year_id', $session_year_id)
                ->with('class_section')
                ->first();
            $class_section_id = $student_session->class_section_id;
            $class_id = $student_session->class_section->class_id;

            $isClassHaveTerm = ClassSessionConfig::classHasTerm($class_id, $session_year_id);

            $currentTerm = null;
            if ($isClassHaveTerm) {
                $currentTerm = Term::currentForClass((int) $session_year_id, (int) $class_id);
            }

            $data = Assignment::where('class_section_id', $class_section_id)
                ->where('session_year_id', $session_year_id)
                ->whereIn('subject_id', $subject_id)->with('file', 'subject')
                ->with('submission', function ($q) use ($child_id) {
                    $q->where('student_id', $child_id)->with('file');
                })
                ->when($currentTerm, function ($query) use ($currentTerm) {
                    $query->where('term_id', $currentTerm?->id);
                });

            if ($request->assignment_id) {
                $data->where('id', $request->assignment_id);
            }
            if ($request->subject_id) {
                $data->where('subject_id', $request->subject_id);
            }
            // The app sends is_submitted=0 for the "Assigned" tab, so "0" is a filter too.
            if (isset($request->is_submitted)) {
                if ($request->is_submitted == 1) {
                    $data->whereHas('submission', function ($q) use ($child_id) {
                        $q->where('student_id', $child_id);
                    });
                } elseif ($request->is_submitted == 0) {
                    $data->whereDoesntHave('submission', function ($q) use ($child_id) {
                        $q->where('student_id', $child_id);
                    });
                }
            }

            $data = $data->orderBy('id', 'desc')->paginate();
            $data->getCollection()->transform(function ($assignment) {
                $submission = $assignment->submission;

                if ($submission instanceof Collection) {
                    $assignment->setRelation(
                        'submission',
                        $submission->isNotEmpty() ? $submission->first() : null
                    );
                }

                return $assignment;
            });

            return ResponseService::successResponse('Assignments Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
