<?php

declare(strict_types=1);

namespace App\Http\Controllers\Classes;

use App\Http\Controllers\Classes\Concerns\ClassSessionYear;
use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ElectiveSubjectGroup;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin panel: elective subjects: groups, and which electives each student chose.
 */
class ElectiveController extends Controller
{
    use ClassSessionYear;

    public function assignElectiveSubject(Request $request)
    {
        try {
            if (! Auth::user()->can('assign-elective-subjects')) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ]);
            }

            // Validation
            $validator = Validator::make($request->all(), [
                'student_id' => 'required_if:is_bulk,0|exists:students,id',
                'student_ids' => 'required_if:is_bulk,1|array',
                'student_ids.*' => 'exists:students,id',
                'elective_group' => 'required|exists:elective_subject_groups,id',
                'selected_subjects' => 'required|array',
                'selected_subjects.*' => 'required|array|min:1',
                'is_bulk' => 'nullable|in:0,1',
                'session_year_id' => 'required|numeric',
                'semester_id' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $isBulk = (int) $request->input('is_bulk', 0);
            $electiveGroupId = $request->input('elective_group');
            $selectedSubjects = $request->input('selected_subjects');
            $semester_id = $request->input('semester_id') ?: null;

            // Flatten all selected subject IDs into one array
            $selectedSubjectIds = collect($selectedSubjects)->flatten()->toArray();

            $electiveGroup = ElectiveSubjectGroup::with('electiveSubjects')
                ->when($semester_id, function ($query) use ($semester_id) {
                    $query->where('semester_id', $semester_id);
                })
                ->findOrFail($electiveGroupId);

            $selectedCount = count($selectedSubjectIds);
            $requiredCount = $electiveGroup->total_selectable_subjects;

            if ($selectedCount != $requiredCount) {
                return response()->json([
                    'error' => true,
                    'message' => __('you_must_select_exactly_subjects', ['count' => $requiredCount]),
                ], 422);
            }

            $validSubjectIds = $electiveGroup->electiveSubjects->pluck('subject_id')->toArray();
            $invalidSubjects = array_diff($selectedSubjectIds, $validSubjectIds);

            if (! empty($invalidSubjects)) {
                return response()->json([
                    'error' => true,
                    'message' => __('selected_subjects_do_not_belong_to_elective_group'),
                ], 422);
            }

            // Get session year and current semester

            $sessionYearId = $request->session_year_id;

            // Build list of students (single or bulk)
            $studentIds = $isBulk ? $request->input('student_ids') : [$request->input('student_id')];

            DB::beginTransaction();

            foreach ($studentIds as $studentId) {

                $studentSession = StudentSessions::where('student_id', $studentId)
                    ->where('session_year_id', $sessionYearId)
                    ->first();

                if (! $studentSession) {
                    throw new \Exception("Student session not found for student ID {$studentId}");
                }

                $classSectionId = $studentSession->class_section_id;

                StudentSubject::where('student_id', $studentId)
                    ->where('class_section_id', $classSectionId)
                    ->where('session_year_id', $sessionYearId)
                    ->when(
                        is_null($semester_id),
                        fn ($q) => $q->whereNull('semester_id'),
                        fn ($q) => $q->where('semester_id', $semester_id)
                    )
                    ->whereIn('subject_id', $validSubjectIds)
                    ->delete();

                foreach ($selectedSubjectIds as $subjectId) {

                    StudentSubject::create([
                        'student_id' => $studentId,
                        'subject_id' => $subjectId,
                        'class_section_id' => $classSectionId,
                        'session_year_id' => $sessionYearId,
                        'semester_id' => $semester_id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'error' => false,
                'message' => $isBulk
                    ? 'Elective subjects assigned successfully to all selected students!'
                    : 'Elective subjects assigned successfully!',
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ], 500);
        }
    }

    public function getStudentAssignedSubjects(Request $request)
    {
        try {
            if (! Auth::user()->can('assign-elective-subjects')) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ]);
            }
            $session_year_id = $request->session_year_id;
            $semester_id = $request->semester_id;
            $studentId = $request->input('student_id');

            if (! $studentId) {
                return response()->json(['error' => 'Student ID is required'], 400);
            }

            $student = Students::find($studentId);
            if ($student === null) {
                // Not a learner of this school.
                return response()->json(['error' => true, 'message' => trans('no_data_found')], 404);
            }

            $student_class_section_id = StudentSessions::where('student_id', $student->id)->where('session_year_id', $session_year_id)
                ->first()->class_section_id;

            $class_id = ClassSection::find($student_class_section_id)->class_id;

            $hasSemesters = ClassSessionConfig::where('class_id', $class_id)
                ->where('session_year_id', $session_year_id)
                ->where('include_semesters', true)
                ->exists();

            // Get assigned elective subjects for this student
            $assignedSubjects = StudentSubject::where('student_id', $studentId)
                ->where('class_section_id', $student_class_section_id)
                ->where('session_year_id', $session_year_id)
                ->when($hasSemesters, function ($query) use ($semester_id) {
                    $query->where('semester_id', $semester_id);
                }, function ($query) {
                    $query->whereNull('semester_id');
                })
                ->pluck('subject_id')
                ->toArray();

            // return response()->json([
            //     'studentId' => $studentId,
            //     'student_class_section_id' => $student_class_section_id,
            //     'session_year_id' => $session_year_id,
            //     'semester_id' => $semester_id,
            // ]);

            return response()->json([
                'error' => false,
                'assigned_subjects' => $assignedSubjects,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ], 500);
        }
    }

    public function selectElectiveSubjects()
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $classes = [];
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        if (Auth::user()->hasRole('Super Admin')) {
            // Get unique classes, with required relations
            $classes = ClassSchool::with([
                'medium',
                'streams',
            ])->get();
        }

        // Students will depend on selected class_section, so keep it empty for now
        $elective_subjects = [];

        return view(
            'select_elective_subjects.index',
            compact('classes', 'elective_subjects', 'session_years')
        );
    }

    public function getElectiveGroups(Request $request)
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        $session_year_id = $request->session_year_id;
        $class_id = $request->class_id;
        $semester_id = $request->semester_id;

        $hasSemesters = ClassSessionConfig::where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->where('include_semesters', true)
            ->exists();

        $groups = ElectiveSubjectGroup::with([
            'electiveSubjects' => function ($query) {
                $query->without('semester')->with('subject');
            },
        ])
            ->where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->when($hasSemesters, function ($query) use ($semester_id) {
                $query->where('semester_id', $semester_id);
            }, function ($query) {
                $query->whereNull('semester_id');
            })
            ->get()
            ->values();

        // Return as JSON for AJAX
        return response()->json($groups);
    }

    public function getElectiveSubjects(Request $request)
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $request->validate([
            'class_id' => 'required|integer',
            'session_year_id' => 'required|integer',
            'semester_id' => 'nullable|integer',
        ]);

        $class_id = $request->class_id;
        $session_year_id = $request->session_year_id;
        $semester_id = $request->semester_id;

        // Fetch elective subjects corresponding to class
        $hasSemesters = ClassSessionConfig::where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->where('include_semesters', true)
            ->exists();

        $subjects = ElectiveSubjectGroup::with([
            'electiveSubjects' => function ($query) {
                $query->without('semester')->with('subject');
            },
        ])
            ->where('class_id', $class_id)->where('session_year_id', $session_year_id)
            ->when($hasSemesters, function ($query) use ($semester_id) {
                $query->where('semester_id', $semester_id);
            }, function ($query) {
                $query->whereNull('semester_id');
            })
            ->get()
            ->pluck('electiveSubjects')
            ->flatten()
            ->pluck('subject')
            ->filter()
            ->values();

        // Return as JSON for AJAX
        return response()->json($subjects);
    }

    public function fetchStudentSubjects(Request $request)
    {
        if (! Auth::user()->can('assign-elective-subjects')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $search = $request->input('search');
        $status = $request->input('filter_status');
        $semester_id = $request->input('semester_id');

        // Get session year
        $sessionYearId = (int) $request->session_year_id;

        // ---------- NEW: single class_id from request ----------
        $class_id = (int) $request->input('class_id');
        if (! $class_id) {
            return response()->json([
                'total' => 0,
                'rows' => [],
            ]);
        }
        // -------------------------------------------------------

        // Single elective subject filter (e.g., filter_elective_subject=6)
        $filterSubjectId = $request->filled('filter_elective_subject')
            ? (int) $request->input('filter_elective_subject')
            : null;

        /* -------------------------------------------------------
        1. Get all students that belong to the given class (session-wise)
        ------------------------------------------------------- */

        $query = StudentSessions::with([
            'student.user',
            'class_section.class',
            'class_section.section',
        ])
            ->where('session_year_id', $sessionYearId)
            ->whereHas('class_section', function ($q) use ($class_id) {
                $q->where('class_id', $class_id);
            })
            ->when($search, function ($q) use ($search) {
                $q->whereHas('student', function ($q) use ($search) {
                    $q->where('admission_no', 'LIKE', "%$search%")
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->whereRaw(
                                "CONCAT(first_name,' ',last_name) LIKE ?",
                                ["%$search%"]
                            );
                        });
                });
            })
            ->orderBy($sort, $order);

        $students = $query->get();

        /* -------------------------------------------------------
       2. Rules, elective IDs and current selections for THIS class only
       ------------------------------------------------------- */
        $rule = ElectiveSubjectGroup::where('class_id', $class_id)
            ->where('session_year_id', $sessionYearId)
            ->when($semester_id, function ($q) use ($semester_id) {
                $q->where('semester_id', $semester_id);
            })
            ->first();

        $electiveIds = ClassSubject::where('class_id', $class_id)
            ->where('type', 'Elective')
            ->when($semester_id, function ($q) use ($semester_id) {
                $q->where('semester_id', $semester_id);
            })
            ->where('session_year_id', $sessionYearId)
            ->pluck('subject_id')
            ->toArray();

        $selections = StudentSubject::whereIn('student_id', $students->pluck('student_id'))
            ->whereIn('subject_id', $electiveIds)
            ->whereIn('class_section_id', $students->pluck('class_section_id'))
            ->where('session_year_id', $sessionYearId)
            ->when($semester_id, function ($q) use ($semester_id) {
                $q->where('semester_id', $semester_id);
            })
            ->select('student_id', 'subject_id')
            ->get()
            ->groupBy('student_id');

        $filtered = [];

        foreach ($students as $s) {
            $st = $this->calcStatus(
                $s,
                collect([$class_id => $rule]),
                collect([$class_id => $electiveIds]),
                $selections
            );

            // ---- status filter -------------------------------------------------
            if ($status && $st['label'] !== $this->map($status)) {
                continue;
            }

            // ---- elective-subject filter ----------------------------------------
            if ($filterSubjectId) {
                $studentElectiveIds = $selections->get($s->id, collect())
                    ->pluck('subject_id')
                    ->toArray();

                if (! in_array($filterSubjectId, $studentElectiveIds)) {
                    continue;
                }
            }

            // ---- selected subject names -----------------------------------------
            $names = $selections->get($s->student_id, collect())
                ->map(fn ($x) => Subject::find($x->subject_id)?->name ?? '')
                ->filter()
                ->implode(', ');

            $filtered[] = [
                'id' => $s->student_id,
                'full_name' => $s->student->user->full_name,
                'photo' => $s->student->user->image,
                'status' => $st['label'],
                'status_badge' => $st['badge'],
                'selected_subjects' => $names ?: '—',
                'class_id' => $class_id,
                'operate' => '<a href="javascript:void(0)" class="btn btn-xs border border-dark rounded btn-secondary bg-transparent text-dark assign-elective" 
                    data-class-id="'.$class_id.'" data-student-id="'.$s->student_id.'"
                    data-toggle="modal" data-target="#assignModal">'.__('assign').'</a>',
            ];
        }

        $total = count($filtered);
        $rows = array_slice($filtered, $offset, $limit);

        // ---- KEEP ORIGINAL RESPONSE STRUCTURE ----
        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    private function map($key)
    {
        return ['not_assigned' => 'Not Assigned', 'incomplete' => 'Incomplete', 'complete' => 'Completed'][$key] ?? '';
    }

    private function calcStatus(
        $studentSession,
        $rules,
        $electiveIds,
        $selections
    ): array {
        // Defensive normalization: accept arrays or Collections
        $rules = collect($rules); // keyed by class_id => rule-object/array
        $electiveIds = collect($electiveIds)
            ->map(function ($ids) {
                // ensure every value is an array of IDs
                return is_array($ids) ? array_values($ids) : [(int) $ids];
            });
        $selections = collect($selections)
            ->map(function ($items) {
                // ensure each student's selections is a Collection
                return collect($items);
            });

        // Get class id from student (null-safe)
        $classId = $studentSession->class_section?->class_id ?? null;

        if (! $classId) {
            return [
                'label' => 'No Class',
                'badge' => '<span class="badge badge-secondary">N/A</span>',
                'selected' => 0,
                'required' => 0,
            ];
        }

        // Lookup rule for this class
        $rule = $rules->get($classId);
        $requiredCount = $rule->total_selectable_subjects ?? 0;

        // Get student's selections (collection) and elective subject ids for the class
        $studentSelections = $selections->get($studentSession->student_id, collect());
        $validElectiveIds = $electiveIds->get($classId, []);

        // Ensure IDs are integers (defensive) and always array
        $validElectiveIds = array_map('intval', (array) $validElectiveIds);

        // Count how many selected subjects are actually elective for this class
        $selectedCount = $studentSelections->whereIn('subject_id', $validElectiveIds)->count();

        // Clear, separate conditions for easier reasoning & debugging:
        if ($requiredCount === 0) {
            // No rule exists (class doesn't require/select electives)
            return [
                'label' => __('no_rule'),
                'badge' => '<span class="badge badge-info">'.__('no_rule').'</span>',
                'selected' => $selectedCount,
                'required' => $requiredCount,
            ];
        }
        if ($selectedCount === 0) {
            // Rule exists but student hasn't selected any valid elective
            return [
                'label' => __('not_assigned'),
                'badge' => '<span class="badge badge-secondary border rounded border-dark text-dark bg-transparent">'.__('not_assigned').'</span>',
                'selected' => 0,
                'required' => $requiredCount,
            ];
        }

        if ($selectedCount >= $requiredCount) {
            return [
                'label' => __('complete'),
                'badge' => '<span class="badge badge-success border rounded border-success text-success bg-transparent">'.__('complete').'</span>',
                'selected' => $selectedCount,
                'required' => $requiredCount,
            ];
        }

        return [
            'label' => __('incomplete'),
            'badge' => '<span class="badge badge-warning border rounded border-warning text-warning bg-transparent">'.__('incomplete').'</span>',
            'selected' => $selectedCount,
            'required' => $requiredCount,
        ];
    }
}
