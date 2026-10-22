<?php

declare(strict_types=1);

namespace App\Http\Controllers\Classes;

use App\Http\Controllers\Classes\Concerns\ClassSessionYear;
use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\EducationalProgram;
use App\Models\ExamClass;
use App\Models\ExamResult;
use App\Models\FeesClass;
use App\Models\Lesson;
use App\Models\Mediums;
use App\Models\Section;
use App\Models\SessionYear;
use App\Models\Shift;
use App\Models\Stream;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Timetable;
use App\Rules\PlainName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Admin panel: classes and their sections.
 */
class ClassController extends Controller
{
    use ClassSessionYear;

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! Auth::user()->can('class-list')) {
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
        $sections = Section::orderBy('id', 'ASC')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();
        $streams = Stream::orderBy('id', 'ASC')->get();
        $shifts = Shift::where('status', 1)->get();
        $educational_programs = EducationalProgram::orderBy('id', 'ASC')->get();

        return response(view('class.index', compact('classes', 'sections', 'mediums', 'streams', 'shifts', 'educational_programs')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('class-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'medium_id' => 'required|numeric',
            'name' => ['required', new PlainName],
            'section_id' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $classIds = [];

            // Single class
            if (! $request->stream_id) {

                $class = new ClassSchool;

                $class->name = $request->name;
                $class->educational_program_id = $request->educational_program;
                $class->medium_id = $request->medium_id;
                $class->shift_id = $request->shift_id;

                $class->save();

                $classIds[] = $class->id;

            } else {
                // Multiple classes with streams
                foreach ($request->stream_id as $stream_id) {

                    $classId = ClassSchool::insertGetId([
                        'name' => $request->name,
                        'medium_id' => $request->medium_id,
                        'stream_id' => $stream_id,
                        'shift_id' => $request->shift_id,
                        'educational_program_id' => $request->educational_program,
                    ]);

                    $classIds[] = $classId;
                }
            }

            // Insert class sections
            $classSections = [];

            // A new class must have an authoritative ClassSessionConfig row for
            // every session year (not just the current one), defaulting to no
            // semesters. The admin opts a class into semesters per session year
            // from the Semester screen.
            $sessionYearIds = SessionYear::pluck('id')->all();
            $now = now();
            $sessionConfigs = [];

            foreach ($classIds as $classId) {

                foreach ($request->section_id as $section_id) {

                    $classSections[] = [
                        'class_id' => $classId,
                        'section_id' => $section_id,
                    ];
                }

                foreach ($sessionYearIds as $sessionYearId) {
                    $sessionConfigs[] = [
                        'class_id' => $classId,
                        'session_year_id' => $sessionYearId,
                        'include_semesters' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            ClassSection::insert($classSections);

            if (! empty($sessionConfigs)) {
                // upsert keeps it idempotent against the
                // (class_id, session_year_id) unique constraint.
                ClassSessionConfig::upsert(
                    $sessionConfigs,
                    ['class_id', 'session_year_id'],
                    ['include_semesters', 'updated_at']
                );
            }

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (! Auth::user()->can('class-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $validator = Validator::make($request->all(), [
            'medium_id' => 'required|numeric',
            'name' => ['required', new PlainName],
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $currentSessionYearId = $this->getCurrentSessionYearId();
            $class = ClassSchool::find($id);

            $class->name = $request->name;
            $class->educational_program_id = $request->educational_program;
            $class->medium_id = $request->medium_id;
            $class->shift_id = $request->shift_id;

            if ($request->stream_id != null) {
                $existingrow = ClassSchool::where('name', $request->name)->where('medium_id', $request->medium_id)->where('shift_id', $request->shift_id)->where('stream_id', $request->stream_id)->first();
                if ($existingrow) {
                    $existingrow->stream_id = $request->stream_id;
                } else {
                    $class->stream_id = $request->stream_id;
                }
            }
            $class->save();
            $all_section_ids = ClassSection::whereIn('section_id', $request->section_id)->where('class_id', $id)->pluck('section_id')->toArray();
            $delete_class_section = $class->sections->pluck('id')->toArray();
            $class_section = [];
            foreach ($request->section_id as $key => $section_id) {
                if (! in_array($section_id, $all_section_ids)) {
                    $class_section[] = [
                        'class_id' => $class->id,
                        'section_id' => $section_id,
                    ];
                } else {
                    unset($delete_class_section[array_search($section_id, $delete_class_section)]);
                }
            }
            ClassSection::insert($class_section);

            ClassSection::whereIn('section_id', $delete_class_section)
                ->where('class_id', $id)
                ->get()
                ->each
                ->delete();

            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (\Throwable $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    // delete all related data:
    public function destroy($id)
    {
        if (! Auth::user()->can('class-delete')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $class = ClassSchool::findOrFail($id);

            // Cascade handled at model level
            $class->delete();

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

    public function show()
    {
        if (! Auth::user()->can('class-list')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (isset($_GET['offset'])) {
            $offset = $_GET['offset'];
        }
        if (isset($_GET['limit'])) {
            $limit = $_GET['limit'];
        }

        if (isset($_GET['sort'])) {
            $sort = $_GET['sort'];
        }
        if (isset($_GET['order'])) {
            $order = $_GET['order'];
        }
        DB::enableQueryLog();
        $currentSessionYearId = $this->getCurrentSessionYearId();
        $sql = ClassSchool::withSessionConfigFor($currentSessionYearId)
            ->with('sections', 'medium', 'streams', 'shifts', 'educational_program');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(fn ($query) => $query->where('id', 'LIKE', "%$search%")->orwhere('name', 'LIKE', "%$search%")
                ->orWhereHas('sections', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })->orWhereHas('medium', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })->orWhereHas('streams', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                })->orWhereHas('shifts', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                })->orWhereHas('educational_program', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                }));
        }
        if (! empty($_GET['medium_id'])) {
            $sql = $sql->where('medium_id', $_GET['medium_id']);
        }
        if (! empty($_GET['shift_id'])) {
            $sql = $sql->where('shift_id', $_GET['shift_id']);
        }

        if (! empty($_GET['educational_program_id'])) {
            $sql = $sql->where('educational_program_id', $_GET['educational_program_id']);
        }
        $total = $sql->count();

        // The medium/stream/shift/educational-program/section columns are derived from related
        // tables, not real columns on `classes`, so ordering by their raw data-field names is a
        // no-op (or a SQL error). Map those fields to correlated subqueries so server-side sorting
        // works; real columns (id, name, created_at, updated_at) pass through unchanged.
        $relationSortSubqueries = [
            'medium_name' => function ($query) {
                $query->select('name')->from('mediums')->whereColumn('mediums.id', 'classes.medium_id');
            },
            'stream_name' => function ($query) {
                $query->select('name')->from('streams')->whereColumn('streams.id', 'classes.stream_id');
            },
            'shift_name' => function ($query) {
                $query->select('title')->from('shifts')->whereColumn('shifts.id', 'classes.shift_id');
            },
            'educational_program_name' => function ($query) {
                $query->select('title')->from('educational_programs')->whereColumn('educational_programs.id', 'classes.educational_program_id');
            },
            // A class can have many sections; sort by its first section name (alphabetically).
            'section_name' => function ($query) {
                $query->select('sections.name')->from('sections')
                    ->join('class_sections', 'class_sections.section_id', '=', 'sections.id')
                    ->whereColumn('class_sections.class_id', 'classes.id')
                    ->whereNull('class_sections.deleted_at')
                    ->orderBy('sections.name')
                    ->limit(1);
            },
        ];

        if (isset($relationSortSubqueries[$sort])) {
            $sql->orderBy($relationSortSubqueries[$sort], $order);
        } else {
            $sql->orderBy($sort, $order);
        }
        $sql->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $classIds = $res->pluck('id')->toArray();
        $classSectionMap = ClassSection::whereIn('class_id', $classIds)
            ->get()
            ->groupBy('class_id')
            ->map(fn ($sections) => $sections->pluck('id'));

        $allClassSectionIds = $classSectionMap->flatten()->toArray();

        $relatedByClassSection = [];
        if (! empty($allClassSectionIds)) {
            $relatedByClassSection = [
                'students' => Students::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'assignments' => Assignment::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'attendance' => Attendance::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'exam results' => ExamResult::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'lessons' => Lesson::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'student sessions' => StudentSessions::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'subject teachers' => SubjectTeacher::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
                'timetables' => Timetable::whereIn('class_section_id', $allClassSectionIds)->pluck('class_section_id')->unique()->toArray(),
            ];
        }

        $classesWithSubjects = ClassSubject::whereIn('class_id', $classIds)->pluck('class_id')->unique()->toArray();
        $classesWithExams = ExamClass::whereIn('class_id', $classIds)->pluck('class_id')->unique()->toArray();
        $classesWithFees = FeesClass::whereIn('class_id', $classIds)->pluck('class_id')->unique()->toArray();

        foreach ($res as $row) {
            // Build related data array for this class
            $sectionIds = $classSectionMap->get($row->id, collect())->toArray();
            $relatedData = [];
            foreach ($relatedByClassSection as $label => $csIds) {
                if (! empty(array_intersect($sectionIds, $csIds))) {
                    $relatedData[] = $label;
                }
            }
            if (in_array($row->id, $classesWithSubjects)) {
                $relatedData[] = 'class subjects';
            }
            if (in_array($row->id, $classesWithExams)) {
                $relatedData[] = 'exams';
            }
            if (in_array($row->id, $classesWithFees)) {
                $relatedData[] = 'fees';
            }

            $operate = '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '
                <a
                    href="'.route('class.destroy', $row->id).'"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    data-id="'.$row->id.'"'
                .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['educational_program_id'] = $row->educational_program->id ?? '';
            $tempRow['educational_program_name'] = $row->educational_program->title ?? '-';
            $tempRow['medium_id'] = $row->medium->id;
            $tempRow['medium_name'] = $row->medium->name;
            $tempRow['shift_id'] = $row->shifts->id ?? '';
            $tempRow['shift_name'] = $row->shifts->title ?? '-';
            $sections = $row->sections;
            $tempRow['section_id'] = $sections->pluck('id');
            $tempRow['section_name'] = $sections->pluck('name');
            $tempRow['stream_id'] = $row->streams->id ?? '';
            $tempRow['stream_name'] = $row->streams->name ?? '-';
            $tempRow['include_semesters'] = $row->getIncludeSemestersForSession($currentSessionYearId);
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function getClassSections($class_id)
    {
        $sections = ClassSection::with('class', 'section', 'streams')
            ->where('class_id', $class_id)
            ->get();

        return response()->json($sections);
    }
}
