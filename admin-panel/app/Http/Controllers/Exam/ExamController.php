<?php

declare(strict_types=1);

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\ExamMarks;
use App\Models\ExamResult;
use App\Models\ExamTimetable;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\Term;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Admin panel: exams and their timetables: create, list, view, edit and delete.
 */
class ExamController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $classes = ClassSchool::with('medium', 'streams')->get();
        $subjects = Subject::orderBy('id', 'DESC')->get();
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();

        return response(view('exams.index', compact('classes', 'subjects', 'session_year_all')));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'class_id' => 'required|array',
            'class_id.*' => 'required|exists:classes,id',
            'name' => 'required',
            'session_year_id' => 'required|exists:session_years,id',
            'description' => 'nullable',
            'term_id' => 'nullable|exists:terms,id',
        ], [
            'class_id.required' => 'Please select at least one class.',
            'class_id.array' => 'Class selection must be an array.',
            'class_id.*.required' => 'Class selection is required.',
            'class_id.*.exists' => 'Selected class is invalid.',
            'session_year_id.exists' => 'Selected session year is invalid.',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $exam = new Exam;
            $exam->name = trim($request->name);
            $exam->description = trim($request->description ?? '');
            $exam->session_year_id = $request->session_year_id;
            $exam->term_id = $request->term_id ?: null;
            $exam->save();

            if ($request->class_id && is_array($request->class_id)) {
                $exam_classes = [];
                foreach ($request->class_id as $class_id) {
                    // Verify class exists
                    $classExists = ClassSchool::find($class_id);
                    if (! $classExists) {
                        throw new \Exception("Class with ID {$class_id} does not exist.");
                    }

                    $exam_classes[] = [
                        'exam_id' => $exam->id,
                        'class_id' => $class_id,
                        'session_year_id' => $request->session_year_id,
                    ];
                }

                if (! empty($exam_classes)) {
                    ExamClass::insert($exam_classes);
                }
            }

            DB::commit();

            return ResponseService::successResponse('data_store_successfully');
        } catch (Throwable $e) {
            DB::rollBack();

            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show()
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';
        $session_year_id = $_GET['session_year_id'] ?? getSettings('session_year')['session_year'];

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

        $sql = Exam::where('session_year_id', $session_year_id)
            ->with('exam_classes.class.medium', 'exam_classes.class.streams', 'session_year', 'timetable');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhere('name', 'LIKE', "%$search%")
                    ->orWhere('description', 'LIKE', "%$search%");
                $timestamp = strtotime($search);
                if ($timestamp !== false) {
                    $date = date('Y-m-d H:i:s', $timestamp);

                    $query->orWhere('created_at', 'LIKE', "%$date%")
                        ->orWhere('updated_at', 'LIKE', "%$date%");
                }
                $query->orWhereHas('session_year', function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%$search%");
                });
            });
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        // Batch load related data existence to avoid N+1 queries
        $examIds = $res->pluck('id')->toArray();
        $examsWithTimetables = ExamTimetable::whereIn('exam_id', $examIds)->pluck('exam_id')->unique()->toArray();
        $examsWithResults = ExamResult::whereIn('exam_id', $examIds)->pluck('exam_id')->unique()->toArray();
        $examsWithClasses = ExamClass::whereIn('exam_id', $examIds)->pluck('exam_id')->unique()->toArray();

        $examTimetableIds = ExamTimetable::whereIn('exam_id', $examIds)->pluck('id')->toArray();
        $examsWithMarks = ! empty($examTimetableIds)
            ? ExamMarks::whereIn('exam_timetable_id', $examTimetableIds)
                ->join('exam_timetables', 'exam_marks.exam_timetable_id', '=', 'exam_timetables.id')
                ->pluck('exam_timetables.exam_id')->unique()->toArray()
            : [];

        foreach ($res as $row) {
            $operate = '';
            if ($row->publish == 0) {
                $operate .= '<a href="#" class="btn btn-xs btn-gradient-success btn-rounded btn-icon publish-exam-result" data-id='.$row->id.' title="Publish Exam Result"><i class="fa fa-check-circle"></i></a>&nbsp;&nbsp;';
            } else {
                $operate .= '<a href="#" class="btn btn-xs btn-gradient-warning btn-rounded btn-icon publish-exam-result" data-id='.$row->id.' title="Unpublish Exam Result"><i class="fa fa-times-circle"></i></a>&nbsp;&nbsp;';
            }
            if (count($row->timetable)) {
                foreach ($row->exam_classes as $data) {
                    // $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))->where(['' => $data->, 'class_id' => $data->class_id])->first();
                    $starting_date_db = ExamTimetable::select(DB::raw('MIN(date)'))
                        ->where([
                            'exam_id' => $data->exam_id,
                            'class_id' => $data->class_id,
                        ])
                        ->first();

                    $starting_date = $starting_date_db->{'MIN(date)'};
                    $starting_date = $starting_date_db['min(date)'];
                    $ending_date = ExamTimetable::where('exam_id', $data->exam_id)
                        ->where('class_id', $data->class_id)
                        ->max('date');
                    $currentTime = Carbon::now();
                    $current_date = date($currentTime->toDateString());
                    if ($current_date >= $starting_date && $current_date <= $ending_date) {
                        $exam_status = '1'; // Upcoming = 0 , On Going = 1 , Completed = 2
                    } elseif ($current_date < $starting_date) {
                        $exam_status = '0'; // Upcoming = 0 , On Going = 1 , Completed = 2
                    } else {
                        $exam_status = '2'; // Upcoming = 0 , On Going = 1 , Completed = 2
                    }
                }
            }
            if (isset($exam_status)) {
                if ($exam_status == 0) {
                    $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                }
            } else {
                $operate .= '<a href="#" class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            }
            $relatedData = [];
            if (in_array($row->id, $examsWithTimetables)) {
                $relatedData[] = 'exam timetables';
            }
            if (in_array($row->id, $examsWithMarks)) {
                $relatedData[] = 'exam marks';
            }
            if (in_array($row->id, $examsWithResults)) {
                $relatedData[] = 'exam results';
            }
            if (in_array($row->id, $examsWithClasses)) {
                $relatedData[] = 'exam classes';
            }

            $operate .= '
                <a
                    href="'.route('exams.destroy', $row->id).'"
                    class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form"
                    title="'.e(trans('Delete')).'" aria-label="'.e(trans('Delete')).'"
                    data-id="'.$row->id.'"'
                .(! empty($relatedData) ? " data-related='".json_encode($relatedData)."'" : '').
                '>
                    <i class="fa fa-trash"></i>
                </a>';

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['description'] = $row->description;
            $tempRow['class_name'] = [];
            foreach ($row->exam_classes as $exam_class) {
                $tempRow['class_name'][] = $exam_class->class->name.'-'.$exam_class->class->medium->name.' '.($exam_class->class->streams->name ?? '');
            }
            $tempRow['class_id'] = $row->exam_classes->pluck('class.id');
            $tempRow['session_year_name'] = $row->session_year->name;
            $tempRow['timetable'] = $row->timetable;
            $tempRow['publish'] = $row->publish;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (! Auth::user()->can('exam-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'nullable',
            'class_id' => 'required|array',
            'class_id.*' => 'required|exists:class_sections,id',
        ], [
            'class_id.required' => 'The class field is required.',
            'class_id.*.required' => 'The class field is required.',
            'class_id.*.exists' => 'The selected class is invalid.',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $exam = Exam::with('exam_classes')->find($id);
            $exam->name = $request->name;
            $exam->description = $request->description;
            $exam->term_id = $request->term_id ?: null;
            $exam->save();

            $all_exam_classes_id = ExamClass::whereIn('class_id', $request->class_id)
                ->where('exam_id', $request->edit_id)->pluck('class_id')->toArray();
            $delete_exam_classes = $exam->exam_classes->pluck('class_id')->toArray();
            $exam_classes = [];

            foreach ($request->class_id as $class_id) {
                if (! in_array($class_id, $all_exam_classes_id)) {
                    $exam_classes[] = [
                        'exam_id' => $exam->id,
                        'class_id' => $class_id,
                        'session_year_id' => $exam->session_year_id,
                    ];
                } else {
                    unset($delete_exam_classes[array_search($class_id, $delete_exam_classes)]);
                }
            }
            ExamClass::insert($exam_classes);

            // //Remaining Data in $all_exam_classes_id should be deleted
            ExamClass::whereIn('class_id', $delete_exam_classes)->where('exam_id', $id)->delete();

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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('exam-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $exam = Exam::findOrFail($id);

            // Cascade handled in model
            $exam->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function getExamSubjects($exam_id)
    {
        if (! Auth::user()->can('exam-upload-marks')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        try {
            $teacher_id = Auth::user()->teacher->id;
            $class_section_id = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
            $class_id = ClassSection::whereIn('id', $class_section_id)->pluck('class_id');
            $subjects = ExamTimetable::with('subject')->where('exam_id', $exam_id)->whereIn('class_id', $class_id)->get();
            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $subjects,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function getSubjectByExam($class_id, $exam_id)
    {

        try {
            $exam_timetable = ExamTimetable::with('subject')->where('class_id', $class_id)->where('exam_id', $exam_id)->get();
            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exam_timetable,
            ];
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function deleteTimetable($id)
    {
        try {
            $exam_timetable = ExamTimetable::find($id);
            $exam_timetable->delete();
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

    public function getExamByClass($class_id)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'class_section_id' => 'required|integer|exists:class_sections,id',
                'isPublish' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => true,
                    'message' => $validator->errors()->first(),
                ]);
            }

            $class_section_id = request('class_section_id');

            // Default false
            $isPublish = request()->has('isPublish')
                ? request('isPublish')
                : false;

            $exams = [];
            $session_year_id = getSettings('session_year')['session_year'];

            $teacher_id = Auth::user()->teacher->id;

            // Check whether this class has term configuration
            $hasTerm = ClassSessionConfig::classHasTerm(
                $class_id,
                $session_year_id
            );

            // Verify teacher has access to this class section
            $classTeacherQuery = ClassTeacher::where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $session_year_id)
                ->where('class_section_id', $class_section_id);

            // Teacher term access for this section
            $teacherTermIds = $classTeacherQuery
                ->whereNotNull('term_id')
                ->pluck('term_id')
                ->unique()
                ->toArray();

            $exam_data = Exam::with([
                'exam_classes' => function ($q) use ($class_id) {
                    $q->where('class_id', $class_id);
                },
            ])
                ->with([
                    'timetable' => function ($q) use ($class_id) {
                        $q->where('class_id', $class_id);
                    },
                ])
                ->where('publish', $isPublish)
                ->where('session_year_id', $session_year_id)

                // Term-based filtering
                ->when($hasTerm, function ($query) use ($teacherTermIds) {
                    $query->whereIn('term_id', $teacherTermIds);
                })
                ->get();

            foreach ($exam_data as $data) {

                if (count($data->timetable)) {

                    $starting_date_db = ExamTimetable::select(DB::raw('min(date)'))
                        ->where('exam_id', $data->id)
                        ->where('class_id', $class_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    $starting_date = $starting_date_db['min(date)'];

                    $ending_date_db = ExamTimetable::select(DB::raw('max(date)'))
                        ->where('exam_id', $data->id)
                        ->where('class_id', $class_id)
                        ->where('session_year_id', $session_year_id)
                        ->first();

                    $ending_date = $ending_date_db['max(date)'];

                    $currentTime = Carbon::now();
                    $current_date = date($currentTime->toDateString());

                    if ($current_date >= $starting_date && $current_date <= $ending_date) {
                        $exam_status = '1';
                    } elseif ($current_date < $starting_date) {
                        $exam_status = '0';
                    } else {
                        $exam_status = '2';
                    }

                    // Completed exams only
                    if ($exam_status == 2) {
                        $exams[] = $data;
                    }
                }
            }

            $response = [
                'error' => false,
                'message' => trans('data_fetch_successfully'),
                'data' => $exams,
            ];
        } catch (Throwable $e) {

            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function getClassOptionsForSession(Request $request)
    {
        $sessionYearId = $request->session_year_id;
        $allowedTermMap = [];

        if (Auth::user()->hasRole('Super Admin')) {
            $classes = ClassSection::with([
                'class',
                'section',
                'class.medium',
                'class.streams',
                'streams',
                'class.allSubjects' => fn ($q) => $q
                    ->select('id', 'class_id', 'term_id', 'session_year_id')
                    ->whereNotNull('term_id')
                    ->where('session_year_id', $sessionYearId),
                'class.allSubjects.term:id,name',
            ])->get();

            $allowedTermMap = $classes->mapWithKeys(fn ($cs) => [
                $cs->id => $cs->class->allSubjects
                    ->pluck('term')->filter()->unique('id')
                    ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
                    ->values()->toArray(),
            ])->filter()->toArray();
        } else {
            $teacher_id = Auth::user()->teacher->id;

            $classTeacherRecords = ClassTeacher::with('term:id,name')
                ->where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $sessionYearId)
                ->get();

            $classes = ClassSection::with(['class', 'section', 'class.medium', 'class.streams', 'streams'])
                ->whereIn('id', $classTeacherRecords->pluck('class_section_id')->unique())
                ->get();

            $allowedTermMap = $classTeacherRecords
                ->filter(fn ($r) => ! is_null($r->term_id) && $r->term)
                ->groupBy('class_section_id')
                ->map(
                    fn ($group) => $group
                        ->map(fn ($r) => ['id' => $r->term_id, 'name' => $r->term->name])
                        ->unique('id')->values()->toArray()
                )->toArray();
        }

        $classOptions = $classes->map(fn ($d) => [
            'id' => $d->id,
            'class_id' => $d->class->id,
            'label' => trim(
                $d->class->name.' - '.$d->section->name.' '.
                    $d->class->medium->name.' '.
                    ($d->class->streams->name ?? '')
            ),
        ]);

        return response()->json([
            'classes' => $classOptions,
            'allowedTermMap' => $allowedTermMap,
        ]);
    }

    public function deleteExamClass($exam_id, $class_id)
    {
        if (! Auth::user()->can('exam-create')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        $exam_class = ExamClass::where('exam_id', $exam_id)->where('class_id', $class_id)->first();
        if (! $exam_class) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }

        $exam_class->delete();
        $response = [
            'error' => false,
            'message' => trans('data_delete_successfully'),
        ];

        return response()->json($response);
    }
}
