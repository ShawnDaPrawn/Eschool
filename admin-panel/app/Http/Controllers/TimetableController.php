<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TimetableController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {

        if (! Auth::user()->can('timetable-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $session_years = SessionYear::orderBy('id', 'ASC')->get();

        return view('timetable.index', compact('session_years'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('timetable-create')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $request->validate([
            'day' => 'required',
            'class_section_id' => 'required',
            'session_year_id' => 'required|integer',
            'term_id' => 'nullable|integer',
        ]);

        try {
            $day_name = $request->day;
            $class_section_id = $request->class_section_id;

            // $class = ClassSection::where('id', $class_section_id)->with('class')->get();

            if ($day_name == 'monday') {
                $day = 1;
            } elseif ($day_name == 'tuesday') {
                $day = 2;
            } elseif ($day_name == 'wednesday') {
                $day = 3;
            } elseif ($day_name == 'thursday') {
                $day = 4;
            } elseif ($day_name == 'friday') {
                $day = 5;
            } elseif ($day_name == 'saturday') {
                $day = 6;
            } elseif ($day_name == 'sunday') {
                $day = 7;
            }
            $a = $day_name.'_group';
            foreach ($request->$a as $data) {
                if (isset($data['id']) && $data['id'] != '') {
                    $timetable = Timetable::find($data['id']);
                } else {
                    $timetable = new Timetable;
                }

                $class_id = ClassSection::where('id', $class_section_id)->value('class_id');
                $session_year_id = getSettings('session_year')['session_year'];

                $currentTerm = Term::currentForClass((int) $session_year_id, (int) $class_id);

                $isClassHaveTerm = ClassSessionConfig::classHasTerm($class_id, $session_year_id);

                $subject_teacher_id = SubjectTeacher::select('id')->where('subject_id', $data['subject_id'])
                    ->where('teacher_id', $data['teacher_id'])
                    ->when($isClassHaveTerm, function ($q) use ($currentTerm) {
                        if ($currentTerm) {
                            $q->where('term_id', $currentTerm->id);
                        }
                    })
                    ->pluck('id')->first();

                $timetable->subject_teacher_id = ($subject_teacher_id) ? ($subject_teacher_id) : 0;
                $timetable->class_section_id = $class_section_id;
                $timetable->session_year_id = $request->session_year_id;
                $timetable->term_id = $request->term_id ?? null;
                $timetable->start_time = $data['start_time'];
                $timetable->end_time = $data['end_time'];
                $timetable->day = $day;
                $timetable->day_name = $day_name;
                $timetable->live_class_url = $data['live_class_url'];
                $timetable->link_name = $data['link_name'];
                $timetable->save();
            }

            return redirect()->back()->with('success', trans('data_store_successfully'));
        } catch (Throwable $e) {
            return redirect()->back()->with('error', trans('error_occurred'));
        }
    }

    public function getSubjectByClassSection(Request $request)
    {
        // Used by the timetable editor (index), which is gated on timetable-list.
        if (! Auth::user()->can('timetable-list')) {
            return $this->noPermissionJson();
        }
        $class = ClassSchool::withSessionConfigFor($request->session_year_id)
            ->where('id', $request->class_id)
            ->first();

        if ($class && $class->getIncludeTermsForSession($request->session_year_id) && $request->term_id) {
            $subjects = ClassSubject::SubjectTeacher()
                ->where('class_id', $request->class_id)
                ->where('session_year_id', $request->session_year_id)
                ->where('term_id', $request->term_id)
                ->with('subject')
                ->get();
        } else {
            $subjects = ClassSubject::SubjectTeacher()
                ->where('session_year_id', $request->session_year_id)
                ->where('class_id', $request->class_id)
                ->with('subject')
                ->get();
        }

        return response($subjects);
    }

    public function getClassTerms(Request $request)
    {
        if (! Auth::user()->can('timetable-list')) {
            return $this->noPermissionJson();
        }
        $class = ClassSchool::withSessionConfigFor($request->session_year_id)
            ->where('id', $request->class_id)
            ->first();

        $hasTerms = $class && $class->getIncludeTermsForSession($request->session_year_id);

        if ($hasTerms) {
            $terms = Term::where('session_year_id', $request->session_year_id)
                ->orderBy('id', 'ASC')
                ->get();
        } else {
            $terms = collect();
        }

        return response()->json([
            'has_terms' => $hasTerms,
            'terms' => $terms,
        ]);
    }

    public function getClassSectionsWithTerms(Request $request)
    {
        $sessionYearId = $request->session_year_id;
        $user = Auth::user();
        $isTeacher = $user->hasRole('Teacher') && $user->teacher;
        $isClassTeacherScope = $request->scope === 'teacher' && $isTeacher;

        $query = ClassSection::with('class.medium', 'section', 'class.streams');

        // If scope=teacher, limit to class teacher's assigned class sections
        if ($isClassTeacherScope) {
            $classSectionIds = ClassTeacher::where('class_teacher_id', $user->teacher->id)
                ->where('session_year_id', $sessionYearId)
                ->pluck('class_section_id');
            $query->whereIn('id', $classSectionIds);
        } elseif ($isTeacher) {
            // For subject teachers (non class-teacher scope), filter by subject_teachers assignments
            $query->whereHas('subject_teachers', function ($q) use ($sessionYearId, $user) {
                $q->where('session_year_id', $sessionYearId)
                    ->where('teacher_id', $user->teacher->id);
            });
        }

        $classSections = $query->get();
        $result = [];

        foreach ($classSections as $section) {
            $class = ClassSchool::withSessionConfigFor($sessionYearId)
                ->where('id', $section->class->id)
                ->first();

            $hasTerms = $class && $class->getIncludeTermsForSession($sessionYearId);

            if ($hasTerms) {
                // For class-teacher scope, show terms from class_teachers assignments
                if ($isClassTeacherScope) {
                    $termIds = ClassTeacher::where('class_section_id', $section->id)
                        ->where('class_teacher_id', $user->teacher->id)
                        ->where('session_year_id', $sessionYearId)
                        ->whereNotNull('term_id')
                        ->pluck('term_id')
                        ->unique()
                        ->values();

                    $terms = Term::where('session_year_id', $sessionYearId)
                        ->whereIn('id', $termIds)
                        ->orderBy('id', 'ASC')
                        ->get();
                } elseif ($isTeacher) {
                    // For subject-teacher scope, only show terms they are assigned to
                    $terms = Term::where('session_year_id', $sessionYearId)
                        ->whereIn('id', function ($q) use ($section, $sessionYearId, $user) {
                            $q->select('term_id')
                                ->from('subject_teachers')
                                ->where('class_section_id', $section->id)
                                ->where('session_year_id', $sessionYearId)
                                ->where('teacher_id', $user->teacher->id)
                                ->whereNotNull('term_id')
                                ->whereNull('deleted_at');
                        })
                        ->orderBy('id', 'ASC')
                        ->get();
                } else {
                    $terms = Term::where('session_year_id', $sessionYearId)
                        ->orderBy('id', 'ASC')
                        ->get();
                }

                foreach ($terms as $term) {
                    $termStartDate = is_string($term->start_date)
                        ? date('d-m-Y', strtotime($term->start_date))
                        : $term->start_date->format('d-m-Y');

                    $termEndDate = is_string($term->end_date)
                        ? date('d-m-Y', strtotime($term->end_date))
                        : $term->end_date->format('d-m-Y');

                    $label = $section->class->name.' '.$section->section->name
                        .' - '.$section->class->medium->name
                        .($section->class->streams->name ?? '')
                        .' - '.$term->name;

                    $result[] = [
                        'class_section_id' => $section->id,
                        'class_id' => $section->class->id,
                        'term_id' => $term->id,
                        'term_start_date' => $termStartDate,
                        'term_end_date' => $termEndDate,
                        'label' => $label,
                    ];
                }
            } else {
                $label = $section->class->name.' '.$section->section->name
                    .' - '.$section->class->medium->name
                    .($section->class->streams->name ?? '');

                $result[] = [
                    'class_section_id' => $section->id,
                    'class_id' => $section->class->id,
                    'term_id' => null,
                    'term_start_date' => null,
                    'term_end_date' => null,
                    'label' => $label,
                ];
            }
        }

        return response()->json($result);
    }

    public function getteacherbysubject(Request $request)
    {
        if (! Auth::user()->can('timetable-list')) {
            return $this->noPermissionJson();
        }
        $request->validate([
            'class_section_id' => 'required|integer',
            'subject_id' => 'required|integer',
            'session_year_id' => 'required|integer',
        ]);

        $classId = ClassSection::where('id', $request->class_section_id)->value('class_id');
        if ($classId === null) {
            // Not a homeroom of this school.
            return response()->json(['error' => true, 'message' => trans('no_data_found')], 404);
        }
        $isClassHasTerm = ClassSessionConfig::classHasTerm($classId, $request->session_year_id);
        $currentTerm = Term::currentForClass((int) $request->session_year_id, (int) $classId);

        $teacher = SubjectTeacher::where([
            'class_section_id' => $request->class_section_id,
            'subject_id' => $request->subject_id,
            'session_year_id' => $request->session_year_id,
        ])
            ->with('teacher')
            ->when($isClassHasTerm && $currentTerm, function ($query) use ($currentTerm) {
                $query->where('term_id', $currentTerm->id);
            })
            ->get();

        return response(
            $teacher
        );
    }

    public function checkTimetable(Request $request)
    {
        if (! Auth::user()->can('timetable-list')) {
            return $this->noPermissionJson();
        }
        $query = Timetable::with('subject_teacher')->where([
            'class_section_id' => $request->class_section_id,
            'session_year_id' => $request->session_year_id,
            'day' => $request->day,
        ]);

        if ($request->term_id) {
            $query->where('term_id', $request->term_id);
        }

        return response($query->get());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('timetable-delete')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        try {
            Timetable::find($id)->delete();
            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function class_timetable()
    {
        // check the user if teacher exists
        $user = Auth::user()->teacher;
        if ($user) {
            $session_years = SessionYear::orderBy('id', 'ASC')->get();

            return view('timetable.class_timetable', compact('session_years'));
        } else {
            // if teacher doesn't exists then send the class section data for select option directly to view
            if (! Auth::user()->can('class-timetable')) {
                $response = [
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ];

                return response()->json($response);
            }
            $session_years = SessionYear::orderBy('id', 'ASC')->get();

            return view('timetable.class_timetable', compact('session_years'));
        }
    }

    public function gettimetablebyclass(Request $request)
    {
        // Same rule as the class timetable page (class_timetable()).
        if (! Auth::user()->teacher && ! Auth::user()->can('class-timetable')) {
            return $this->noPermissionJson();
        }
        Session::put('class_timetable', $request->class_section_id);

        $query = Timetable::where('class_section_id', $request->class_section_id)
            ->where('session_year_id', $request->session_year_id);

        if ($request->term_id) {
            $query->where('term_id', $request->term_id);
        }

        $timetable = (clone $query)->with('subject_teacher')
            ->orderBy('day', 'asc')
            ->get();

        $day = (clone $query)->select('day', 'day_name')
            ->groupBy('day', 'day_name')
            ->get();

        return [
            'timetable' => $timetable,
            'days' => $day,
        ];
    }

    public function teacher_timetable()
    {
        // check the user if teacher exists
        $user = Auth::user()->teacher;
        if ($user) {
            // if teacher exists then send the timetable data directly to view by its credentials
            $session_years = SessionYear::orderBy('id', 'ASC')->get();

            return view('timetable.teacher_timetable', compact('session_years'));
        } else {
            // if teacher doesn't exists then send the class section data for select option directly to view
            if (! Auth::user()->can('teacher-timetable')) {
                $response = [
                    'error' => true,
                    'message' => trans('no_permission_message'),
                ];

                return response()->json($response);
            }

            $teacher = Teacher::with('user')->teachers()->get();
            $session_years = SessionYear::orderBy('id', 'ASC')->get();

            return view('timetable.teacher_timetable', compact('teacher', 'session_years'));
        }
    }

    public function gettimetablebyteacher(Request $request)
    {
        // Same rule as the teacher timetable page (teacher_timetable()).
        if (! Auth::user()->teacher && ! Auth::user()->can('teacher-timetable')) {
            return $this->noPermissionJson();
        }
        $subject_teacher = SubjectTeacher::select('id')->where('teacher_id', $request->teacher_id)->pluck('id');
        $timetable = [];
        $day = [];
        for ($i = 0; $i < count($subject_teacher); $i++) {
            $timetable[] = Timetable::with('subject_teacher', 'class_section', 'term')
                ->where('session_year_id', $request->session_year_id)
                ->where('subject_teacher_id', $subject_teacher[$i])
                ->get();
        }
        $day[] = Timetable::select('day', 'day_name')
            ->whereIn('subject_teacher_id', $subject_teacher)
            ->where('session_year_id', $request->session_year_id)
            ->groupBy('day', 'day_name')
            ->get();

        return $data = [
            'timetable' => $timetable,
            'days' => $day,
        ];
    }

    public function getTimetableBySubjectTeacherClass(Request $request)
    {
        if (! Auth::user()->teacher && ! Auth::user()->can('teacher-timetable')) {
            return $this->noPermissionJson();
        }
        $teacher = Auth::user()->teacher;
        // Users who aren't teachers (e.g. Super Admin) have no timetable of their own.
        $subject_teacher_id = $teacher ? SubjectTeacher::where('teacher_id', $teacher->id)->pluck('id')->toArray() : [];

        if ($request->class_section_id) {

            $timetableQuery = Timetable::whereIn('subject_teacher_id', $subject_teacher_id)
                ->where('session_year_id', $request->session_year_id)
                ->where('class_section_id', $request->class_section_id);

            if ($request->term_id) {
                $timetableQuery->where('term_id', $request->term_id);
            }

            $timetable = (clone $timetableQuery)->with('subject_teacher', 'class_section', 'term')
                ->orderBy('day', 'asc')->get();

            $day = (clone $timetableQuery)->select('day', 'day_name')
                ->groupBy('day', 'day_name')->get();
        } else {
            $timetableQuery = Timetable::whereIn('subject_teacher_id', $subject_teacher_id)
                ->where('session_year_id', $request->session_year_id);

            $timetable = (clone $timetableQuery)->with('subject_teacher', 'class_section', 'term')
                ->orderBy('day', 'asc')->get();

            $day = (clone $timetableQuery)->select('day', 'day_name')
                ->groupBy('day', 'day_name')->get();
        }

        return $data = [
            'timetable' => $timetable,
            'days' => $day,
        ];
    }

    public function linkUpdate(Request $request)
    {
        // Staff who can edit timetables, or the teacher who teaches this period.
        $timetable = Timetable::with('subject_teacher')->find($request->edit_id);
        $teacher_id = Auth::user()->teacher->id ?? null;
        $teachesPeriod = $timetable && $teacher_id && $timetable->subject_teacher
            && (int) $timetable->subject_teacher->teacher_id === (int) $teacher_id;
        if (! $timetable || (! Auth::user()->can('timetable-edit') && ! $teachesPeriod)) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $validator = Validator::make($request->all(), [
            'live_class_url' => 'required|url:http,https|max:255',
            // The name is shown as HTML on the timetable pages.
            'link_name' => ['required', 'string', 'max:255', 'not_regex:/[<>]/'],
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        try {
            $timetable->live_class_url = $request->live_class_url;
            $timetable->link_name = $request->link_name;
            $timetable->save();

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function getTimeatableDetails($id)
    {
        // Used by the live-class link editor on the teacher timetable page.
        if (! Auth::user()->teacher && ! Auth::user()->can('teacher-timetable')) {
            return $this->noPermissionJson();
        }
        $timetable = Timetable::where('id', $id)->first();

        return response($timetable);
    }

    private function noPermissionJson()
    {
        return response()->json([
            'error' => true,
            'message' => trans('no_permission_message'),
        ]);
    }
}
