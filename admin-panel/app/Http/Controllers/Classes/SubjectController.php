<?php

declare(strict_types=1);

namespace App\Http\Controllers\Classes;

use App\Http\Controllers\Classes\Concerns\ClassSessionYear;
use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\ClassSubject;
use App\Models\ElectiveSubjectGroup;
use App\Models\Mediums;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Stream;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\Subject;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: which subjects each class studies, including subject groups and semesters.
 */
class SubjectController extends Controller
{
    use ClassSessionYear;

    public function subject()
    {
        if (! Auth::user()->can('assign-class-subject')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $currentSessionYearId = $this->getCurrentSessionYearId();
        $classes = ClassSchool::withSessionConfigFor($currentSessionYearId)
            ->orderBy('id', 'DESC')
            ->with('medium', 'sections', 'streams')
            ->get();
        $subjects = Subject::orderBy('id', 'ASC')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        $streams = Stream::orderBy('id', 'ASC')->get();
        $semesters = Semester::orderBy('id', 'ASC')->get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return response(view('class.subject', compact('classes', 'subjects', 'mediums', 'streams', 'semesters', 'session_years')));
    }

    public function update_subjects(Request $request)
    {
        ResponseService::noPermissionThenSendJson('assign-class-subject');

        $validation_rules = [
            'class_id' => 'required|numeric',
            'edit_core_subject' => 'nullable|array',
            'edit_core_subject.*' => 'nullable|array|required_array_keys:class_subject_id,subject_id',
            'core_subjects' => 'nullable|array',
            'elective_subject_id' => 'array',
            'elective_subjects' => 'nullable|array',
            'elective_subjects.*.subject_id' => 'required|array',
            'elective_subjects.*.total_selectable_subjects' => 'required|numeric',
            'session_year_id' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $validation_rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | PREVENT THE SAME SUBJECT BEING ASSIGNED TWICE WITHIN ONE SEMESTER
        |--------------------------------------------------------------------------
        | A subject may repeat across different semesters (e.g. English in both
        | Semester 1 and Semester 2), but not twice within the same semester -
        | whether as two core subjects, two elective choices, or a core/elective
        | overlap.
        */

        $subjectSemesterPairs = [];

        foreach (array_merge($request->edit_core_subject ?? [], $request->core_subjects ?? []) as $row) {
            $subjectSemesterPairs[] = ($row['subject_id'] ?? '').'_'.($row['semester_id'] ?? '');
        }

        foreach (array_merge($request->edit_elective_subjects ?? [], $request->elective_subjects ?? []) as $group) {
            foreach ($group['subject_id'] ?? [] as $subjectId) {
                $subjectSemesterPairs[] = $subjectId.'_'.($group['semester_id'] ?? '');
            }
        }

        if (count($subjectSemesterPairs) !== count(array_unique($subjectSemesterPairs))) {
            return response()->json([
                'error' => true,
                'message' => trans('same_subject_cannot_be_assigned_twice_in_the_same_semester'),
            ]);
        }

        $session_year_id = $request->session_year_id;

        // try {

        /*
        |--------------------------------------------------------------------------
        | UPDATE CORE SUBJECTS
        |--------------------------------------------------------------------------
        */

        if ($request->edit_core_subject) {
            foreach ($request->edit_core_subject as $row) {
                $edit_core_subject = ClassSubject::where('id', $row['class_subject_id'])
                    ->where('session_year_id', $session_year_id)
                    ->first();
                if (! $edit_core_subject) {
                    continue;
                }
                $edit_core_subject->subject_id = $row['subject_id'];
                $edit_core_subject->semester_id = $row['semester_id'] ?? null;
                $edit_core_subject->save();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | ADD NEW CORE SUBJECTS
        |--------------------------------------------------------------------------
        */

        if ($request->core_subjects) {
            $core_subjects = [];
            foreach ($request->core_subjects as $row) {
                $core_subjects[] = [
                    'class_id' => $request->class_id,
                    'type' => 'Compulsory',
                    'subject_id' => $row['subject_id'],
                    'semester_id' => $row['semester_id'] ?? null,
                    'session_year_id' => $session_year_id,
                ];
            }
            ClassSubject::insert($core_subjects);
        }

        /*
        |--------------------------------------------------------------------------
        | EDIT ELECTIVE SUBJECT GROUPS + CLEANUP STUDENT SUBJECTS
        |--------------------------------------------------------------------------
        */

        if ($request->edit_elective_subjects) {
            foreach ($request->edit_elective_subjects as $subject_group) {

                // --- FETCH OLD SUBJECT IDS BEFORE UPDATE ---
                $old_subject_ids = ClassSubject::where([
                    'elective_subject_group_id' => $subject_group['subject_group_id'],
                    'session_year_id' => $session_year_id,
                ])->pluck('subject_id');

                // --- UPDATE ELECTIVE GROUP ---
                $elective_subject_group = ElectiveSubjectGroup::where([
                    'id' => $subject_group['subject_group_id'],
                    'session_year_id' => $session_year_id,
                ])->first();

                if (! $elective_subject_group) {
                    continue;
                }

                $elective_subject_group->total_subjects = count($subject_group['subject_id']);
                $elective_subject_group->total_selectable_subjects = $subject_group['total_selectable_subjects'];
                $elective_subject_group->class_id = $request->class_id;
                $elective_subject_group->semester_id = $subject_group['semester_id'] ?? null;
                $elective_subject_group->save();

                // --- UPDATE SUBJECTS INSIDE THE GROUP ---
                $new_subject_ids = collect($subject_group['subject_id']);

                foreach ($subject_group['subject_id'] as $key => $subject_id) {

                    if (! empty($subject_group['class_subject_id'][$key])) {
                        // Existing subject
                        $elective_subject = ClassSubject::where([
                            'id' => $subject_group['class_subject_id'][$key],
                            'session_year_id' => $session_year_id,
                        ])->first();

                        if (! $elective_subject) {
                            $elective_subject = new ClassSubject;
                        }
                    } else {
                        // New subject
                        $elective_subject = new ClassSubject;
                    }

                    $elective_subject->class_id = $request->class_id;
                    $elective_subject->type = 'Elective';
                    $elective_subject->subject_id = $subject_id;
                    $elective_subject->elective_subject_group_id = $elective_subject_group->id;
                    $elective_subject->semester_id = $subject_group['semester_id'] ?? null;
                    $elective_subject->session_year_id = $session_year_id;
                    $elective_subject->save();
                }

                // --- CLEANUP: REMOVE STUDENT SUBJECTS FOR REMOVED ELECTIVE SUBJECTS ---
                $removed_subject_ids = $old_subject_ids->diff($new_subject_ids);

                if ($removed_subject_ids->count()) {

                    // find class sections that actually have students in the target session
                    $class_section_ids = StudentSessions::query()
                        ->where('session_year_id', $session_year_id)
                        ->whereHas('class_section', function ($q) use ($request) {
                            $q->where('class_id', $request->class_id);
                        })
                        ->pluck('class_section_id')
                        ->unique()
                        ->values();

                    // delete from student_subjects
                    StudentSubject::whereIn('subject_id', $removed_subject_ids)
                        ->whereIn('class_section_id', $class_section_ids)
                        ->where('session_year_id', $session_year_id)
                        ->when($subject_group['semester_id'] ?? null, function ($query, $semester) {
                            return $query->where('semester_id', $semester);
                        })
                        ->delete();
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE NEW ELECTIVE SUBJECT GROUPS
        |--------------------------------------------------------------------------
        */

        if ($request->elective_subjects) {
            foreach ($request->elective_subjects as $subject_group) {

                // Create group
                $elective_subject_group = new ElectiveSubjectGroup;
                $elective_subject_group->total_subjects = count($subject_group['subject_id']);
                $elective_subject_group->total_selectable_subjects = $subject_group['total_selectable_subjects'];
                $elective_subject_group->class_id = $request->class_id;
                $elective_subject_group->session_year_id = $session_year_id;
                $elective_subject_group->semester_id = $subject_group['semester_id'] ?? null;
                $elective_subject_group->save();

                foreach ($subject_group['subject_id'] as $subject_id) {
                    $elective_subject = [
                        'class_id' => $request->class_id,
                        'type' => 'Elective',
                        'subject_id' => $subject_id,
                        'session_year_id' => $session_year_id,
                        'semester_id' => $subject_group['semester_id'] ?? null,
                        'elective_subject_group_id' => $elective_subject_group->id,
                    ];
                    ClassSubject::insert($elective_subject);
                }
            }
        }

        return response()->json([
            'error' => false,
            'message' => trans('data_store_successfully'),
        ]);
        // } catch (\Throwable $e) {

        //     return response()->json([
        //         'error' => true,
        //         'message' => $e->getMessage(),
        //     ]);
        // }
    }

    public function subject_list()
    {
        if (! Auth::user()->can('assign-class-subject')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $offset = $_GET['offset'] ?? 0;
        $limit = $_GET['limit'] ?? 10;
        $sort = $_GET['sort'] ?? 'id';
        $order = $_GET['order'] ?? 'DESC';
        $session_year_id = request()->query('session_year_id');

        $sql = ClassSchool::withSessionConfigFor($session_year_id)->with([
            'sections',
            'medium',
            'streams',
            'coreSubject' => function ($q) use ($session_year_id) {
                if ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }
                $q->with('semester');
            },
            'electiveSubjectGroup' => function ($q) use ($session_year_id) {
                if ($session_year_id) {
                    $q->where('session_year_id', $session_year_id);
                }

                $q->with(['electiveSubjects' => function ($subQ) use ($session_year_id) {
                    if ($session_year_id) {
                        $subQ->where('session_year_id', $session_year_id);
                    }
                }, 'electiveSubjects.subject']);
            },
        ]);

        if (! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(fn ($query) => $query->where('id', 'LIKE', "%$search%")
                ->orWhere('name', 'LIKE', "%$search%"));
        }

        if (! empty($_GET['medium_id'])) {
            $sql->where('medium_id', $_GET['medium_id']);
        }

        $total = $sql->count();

        // $currentSemester = Semester::get()->first(function ($semester) {
        //     return $semester->current;
        // });
        $currentSemester = Semester::where('session_year_id', $session_year_id)
            ->first();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $no = 1;

        foreach ($res as $row) {
            $includesSemesters = $row->getIncludeSemestersForSession($session_year_id);

            if ($includesSemesters !== null) {
                // Edit the same session year the list is showing.
                $editUrl = route('class-subject-edit.index', array_filter(['id' => $row->id, 'session_year_id' => $session_year_id]));
                $operate = '<a href="'.e($editUrl).'" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            } else {
                $operate = '<a href="javascript:void(0)" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon disabled" title="'.trans('semester_not_configured').'" disabled><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            }

            $tempRow = [];
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['medium_id'] = $row->medium->id;
            $tempRow['medium_name'] = $row->medium->name;
            $tempRow['stream_id'] = $row->streams->id ?? ' ';
            $tempRow['stream_name'] = $row->streams->name ?? '-';
            $tempRow['section_names'] = $row->sections->pluck('name');
            $tempRow['include_semesters'] = $includesSemesters;

            // if ($includesSemesters && ! empty($currentSemester)) {
            //     $tempRow['core_subjects'] = $row->coreSubject->filter(function ($data) use ($currentSemester) {
            //         return $data->semester_id == $currentSemester->id;
            //     })->values(); // Filter subjects based on current semester

            //     $tempRow['elective_subject_groups'] = $row->electiveSubjectGroup->filter(function ($data) use ($currentSemester) {
            //         return $data->semester_id == $currentSemester->id;
            //     })->values();
            // } else {
            //     $tempRow['core_subjects'] = $row->coreSubject;
            //     $tempRow['elective_subject_groups'] = $row->electiveSubjectGroup;
            // }
            if ($includesSemesters) {

                $tempRow['core_subjects'] = $row->coreSubject
                    ->where('semester_id', $currentSemester->id)
                    ->values();

                $tempRow['elective_subject_groups'] = $row->electiveSubjectGroup
                    ->where('semester_id', $currentSemester->id)
                    ->values();

            } else {

                $tempRow['core_subjects'] = $row->coreSubject;

                $tempRow['elective_subject_groups'] = $row->electiveSubjectGroup;
            }

            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Returns the full semester-wise subject breakdown for a single class,
     * scoped to the selected session year. Consumed by the bootstrap-table
     * detail view (expand row) on the class-subject listing.
     */
    public function subjectSemesterDetails(Request $request, $class_id)
    {
        if (! Auth::user()->can('assign-class-subject')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $session_year_id = $request->query('session_year_id') ?: $this->getCurrentSessionYearId();

            $class = ClassSchool::with([
                'coreSubject' => function ($q) use ($session_year_id) {
                    if ($session_year_id) {
                        $q->where('session_year_id', $session_year_id);
                    }
                    $q->with('subject', 'semester');
                },
                'electiveSubjectGroup' => function ($q) use ($session_year_id) {
                    if ($session_year_id) {
                        $q->where('session_year_id', $session_year_id);
                    }
                    $q->with(['electiveSubjects' => function ($subQ) use ($session_year_id) {
                        if ($session_year_id) {
                            $subQ->where('session_year_id', $session_year_id);
                        }
                    }, 'electiveSubjects.subject']);
                },
            ])->findOrFail($class_id);

            // Order chronologically (start_date) so semesters read Sem 1 → Sem 2 …;
            // fall back to id for any semester missing a start date.
            $semesters = Semester::where('session_year_id', $session_year_id)
                ->orderByRaw('start_date IS NULL, start_date ASC')
                ->orderBy('id', 'ASC')
                ->get();

            $data = [];
            foreach ($semesters as $semester) {
                $coreSubjects = $class->coreSubject
                    ->where('semester_id', $semester->id)
                    ->map(function ($classSubject) {
                        return [
                            'name' => $classSubject->subject->name ?? '',
                            'type' => $classSubject->subject->type ?? '',
                        ];
                    })->values();

                $electiveGroups = $class->electiveSubjectGroup
                    ->where('semester_id', $semester->id)
                    ->map(function ($group) {
                        return [
                            'total_subjects' => $group->total_subjects,
                            'total_selectable_subjects' => $group->total_selectable_subjects,
                            'subjects' => $group->electiveSubjects->map(function ($electiveSubject) {
                                return [
                                    'name' => $electiveSubject->subject->name ?? '',
                                    'type' => $electiveSubject->subject->type ?? '',
                                ];
                            })->values(),
                        ];
                    })->values();

                $data[] = [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'core_subjects' => $coreSubjects,
                    'elective_groups' => $electiveGroups,
                ];
            }

            return response()->json([
                'error' => false,
                'semesters' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function subject_destroy($id)
    {
        ResponseService::noPermissionThenSendJson('assign-class-subject');

        try {
            $classSubject = ClassSubject::findOrFail($id);

            // Cascade + bookkeeping handled in model
            $classSubject->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function subject_group_destroy($id)
    {
        ResponseService::noPermissionThenSendJson('assign-class-subject');

        try {
            $group = ElectiveSubjectGroup::findOrFail($id);

            // Cascade handled at model level
            $group->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function getSubjectsByMediumId($medium_id)
    {
        try {
            $subjects = Subject::where('medium_id', $medium_id)->get();
            $response = [
                'error' => false,
                'data' => $subjects,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function classSubjectsEdit(Request $request, $id)
    {
        if (! Auth::user()->can('assign-class-subject')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $session_year_id = $request->query('session_year_id');

        $sessionYearId = $session_year_id ?: $this->getCurrentSessionYearId();

        $class = ClassSchool::withSessionConfigFor($sessionYearId)->with([
            'medium',
            'sections',
            'streams',
            'coreSubject' => function ($q) use ($sessionYearId) {
                if ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                }
                $q->with('semester');
            },
            'electiveSubjectGroup' => function ($q) use ($sessionYearId) {
                if ($sessionYearId) {
                    $q->where('session_year_id', $sessionYearId);
                }

                $q->with(['electiveSubjects' => function ($subQ) use ($sessionYearId) {
                    if ($sessionYearId) {
                        $subQ->where('session_year_id', $sessionYearId);
                    }
                }, 'electiveSubjects.subject']);
            },
        ])->findOrFail($id);

        $semesters = Semester::where('session_year_id', $sessionYearId)->orderBy('id', 'ASC')->get();
        $subjects = Subject::orderBy('id', 'ASC')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        $streams = Stream::orderBy('id', 'ASC')->get();

        $sessionYearName = SessionYear::whereKey($sessionYearId)->value('name');

        return response(view('class.edit_subject', compact('class', 'semesters', 'subjects', 'mediums', 'streams', 'sessionYearId', 'sessionYearName')));
    }
}
