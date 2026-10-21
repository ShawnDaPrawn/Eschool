<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AbsenceAlertService;
use App\Services\ResponseService;
use App\Exports\StudentsExport;
use App\Imports\AttendanceImport;
use App\Models\Attendance;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveMaster;
use App\Models\Notification;
use App\Models\Parents;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\Staff;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\UserNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function monthlyView()
    {
        if (! Auth::user()->can('attendance-staff')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];

         $currentSessionYear = SessionYear::find($session_year_id);

        $class_sections = ClassSection::with(
            'class',
            'class.medium',
            'section',
            'class.streams'
        )->get();

        return view('attendance.monthly', compact(
            'class_sections',
            'currentSessionYear'
        ));
    }

    public function getSessionYearMonths(Request $request)
    {
        if (! Auth::user()->can('attendance-staff')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $session_year = SessionYear::FindOrFail($request->session_year_id);
        $start = Carbon::parse($session_year->start_date)->startOfMonth();
        $end = Carbon::parse($session_year->end_date)->startOfMonth();
        $months = [];
        while ($start->lte($end)) {
            $months[] = [
                'value' => $start->format('Y-m'),
                'text' => $start->format('Y-m'),
            ];
            $start->addMonth();

        }

        return response()->json([
            'months' => $months,
        ]);

    }

    public function monthlyAjax(Request $request)
    {
        if (! Auth::user()->can('attendance-staff')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $session_year_id = $request->get('session_year_id');

        if (empty($session_year_id)) {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
        }

        $selected_class_section_id = $request->get('class_section_id');
        $selected_month = $request->get('month', date('Y-m'));

        $students = collect();
        $attendance = [];
        $daysInMonth = 0;
        $holidayDays = [];
        $sundayDays = [];

        if ($selected_class_section_id && $selected_month) {

            $monthStart = Carbon::createFromFormat('Y-m', $selected_month)->startOfMonth();
            $monthEnd = Carbon::createFromFormat('Y-m', $selected_month)->endOfMonth();
            $daysInMonth = (int) $monthEnd->format('j');

            // Collect Sundays
            $day = $monthStart->copy();
            while ($day->lte($monthEnd)) {
                if ($day->isSunday()) {
                    $sundayDays[] = (int) $day->format('j');
                }
                $day->addDay();
            }

            $holidayDays = Holiday::where('session_year_id', $session_year_id)
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->pluck('date')
                ->map(fn ($d) => (int) Carbon::parse($d)->format('j'))
                ->toArray();

            $holidayDays = array_values(array_unique($holidayDays));

            $students = Students::with('user:id,first_name,last_name,image')
                ->whereHas('studentSessions', function ($q) use ($session_year_id, $selected_class_section_id) {
                    $q->where('session_year_id', $session_year_id)
                        ->where('class_section_id', $selected_class_section_id);
                })
                ->orderBy('roll_number')
                ->get();

            $studentIds = $students->pluck('id')->toArray();

            $rawRecords = Attendance::where('session_year_id', $session_year_id)
                ->where('class_section_id', $selected_class_section_id)
                ->whereIn('student_id', $studentIds)
                ->whereIn('type', [0, 1, 4, 5])
                ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->get();

            // type: 0=Absent, 1=Present, 4=First Half, 5=Second Half
            $typeMap = [
                0 => 'A',
                1 => 'P',
                4 => 'HD',
                5 => 'HD',
            ];

            foreach ($rawRecords as $rec) {
                $day = (int) Carbon::parse($rec->date)->format('j');

                if (in_array($day, $sundayDays) || in_array($day, $holidayDays)) {
                    continue;
                }

                $attendance[$rec->student_id][$day] = $typeMap[$rec->type] ?? null;
            }
        }

        $studentsData = $students->map(function ($student) {
            return [
                'id' => $student->id,
                'roll_number' => $student->roll_number,
                'full_name' => trim(($student->user->first_name ?? '').' '.($student->user->last_name ?? '')),
                'image' => $student->user->image ? asset($student->user->image) : null,
            ];
        });

        return response()->json([
            'students' => $studentsData,
            'attendance' => $attendance,
            'days_in_month' => $daysInMonth,
            'holiday_days' => $holidayDays,
            'sunday_days' => $sundayDays,
        ]);
    }

    public function index()
    {
        if (! Auth::user()->can('attendance-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
         $session_year_setting = getSettings('session_year');

         $currentSessionYear = SessionYear::find($session_year_setting['session_year']);
      
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->where('session_year_id', $currentSessionYear->id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();

        $currentSemester = Semester::where('session_year_id', $currentSessionYear->id)->get()->first(function ($semester) {
            return $semester->current;
        });

        $classIdsWithSemesterIncluded = ClassSessionConfig::where('session_year_id', $currentSessionYear->id)
            ->where('include_semesters', true)
            ->pluck('class_id')
            ->all();

        $sessionStartDate = is_string($currentSessionYear->start_date)
            ? date('d-m-Y', strtotime($currentSessionYear->start_date))
            : $currentSessionYear->start_date->format('d-m-Y');

        $sessionEndDate = is_string($currentSessionYear->end_date)
            ? date('d-m-Y', strtotime($currentSessionYear->end_date))
            : $currentSessionYear->end_date->format('d-m-Y');

        $semesterStartDate = $currentSemester
            ? (is_string($currentSemester->start_date)
                ? date('d-m-Y', strtotime($currentSemester->start_date))
                : $currentSemester->start_date->format('d-m-Y'))
            : null;

        $semesterEndDate = $currentSemester
            ? (is_string($currentSemester->end_date)
                ? date('d-m-Y', strtotime($currentSemester->end_date))
                : $currentSemester->end_date->format('d-m-Y'))
            : null;

        $classSectionDateLimits = [];
        foreach ($class_sections as $section) {
            $useSemesterDates = $currentSemester && in_array($section->class_id, $classIdsWithSemesterIncluded);

            $classSectionDateLimits[$section->id] = [
                'start_date' => $useSemesterDates ? $semesterStartDate : $sessionStartDate,
                'end_date' => $useSemesterDates ? $semesterEndDate : $sessionEndDate,
            ];
        }

        // return response()->json($currentSemester);
        return view('attendance.index', compact('class_sections', 'currentSessionYear', 'currentSemester', 'classSectionDateLimits'));
    }

    public function view()
    {
        if (! Auth::user()->can('attendance-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();
          $session_years = SessionYear::orderBy('id', 'ASC')->get();
      
        
        $session_year_setting = getSettings('session_year');
        $currentSessionYearId = $session_year_setting['session_year'];

        return view('attendance.view', compact('class_sections', 'session_years', 'currentSessionYearId'));
    }

    public function getAttendanceData(Request $request)
    {
        if (! Auth::user()->can('attendance-list')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();
        $response = Attendance::select('type')->where(['date' => $date, 'class_section_id' => $request->class_section_id])->pluck('type')->first();

        return response()->json($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('attendance-create') && ! Auth::user()->can('attendance-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'student_id' => 'required|array|min:1',
            'date' => 'required|date_format:d-m-Y',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        try {
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $class_section_id = $request->class_section_id;
            $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();
            $student_ids = $request->student_id;

            // Fetch existing attendance records for this date and class_section
            $existingAttendances = Attendance::where([
                'date' => $date,
                'class_section_id' => $class_section_id,
                'session_year_id' => $session_year_id,
            ])->get()->keyBy('student_id');

            // Approved leaves lock the attendance type for the date (matched by user_id).
            $leaveTypeByUserId = Leave::approvedTypesForDate($date);
            $studentUserIds = Students::whereIn('id', $student_ids)->pluck('user_id', 'id');

            foreach ($student_ids as $student_id) {
                $studentUserId = $studentUserIds[$student_id] ?? null;
                $attendance = $existingAttendances->has($student_id)
                    ? $existingAttendances->get($student_id)
                    : new Attendance;
                $wasAbsent = $attendance->exists && (int) $attendance->type === Attendance::TYPE_ABSENT;

                $attendance->class_section_id = $class_section_id;
                $attendance->student_id = $student_id;
                $attendance->session_year_id = $session_year_id;
                $attendance->date = $date;

                // Handle holiday attendance
                if ($request->has('holiday') && $request->holiday == 3) {
                    $attendance->type = $request->holiday;
                } elseif ($studentUserId !== null && isset($leaveTypeByUserId[$studentUserId])) {
                    // Approved leave overrides whatever was submitted; reject the leave to change it.
                    $attendance->type = Attendance::leaveTypeToType($leaveTypeByUserId[$studentUserId]);
                } else {
                    $typeKey = 'type'.$student_id;
                    $attendance->type = $request->has($typeKey) ? $request->$typeKey : null;
                }

                // Set status to 1 (present/recorded)
                $attendance->status = 1;
                $attendance->save();

                // Checked after save() used to compare the new type with itself, so no alert was ever sent.
                if ((string) $attendance->type === (string) Attendance::TYPE_ABSENT && ! $wasAbsent) {
                    $newlyAbsent[] = $student_id;
                }
            }

            app(AbsenceAlertService::class)->notify($newlyAbsent ?? [], $date, (int) $session_year_id);

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (\Exception $e) {
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        if (! Auth::user()->can('attendance-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = 0;
        $limit = 200;
        $sort = 'roll_number';
        $order = 'ASC';

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

        $class_section_id = $request->class_section_id;
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();
        $session_year = getSettings('session_year');
        $session_year_id = $session_year['session_year'];
        $current_date = Carbon::now()->toDateString();

        // Get existing attendance records for the selected date
        $existingAttendance = Attendance::where([
            'date' => $date,
            'class_section_id' => $class_section_id,
            'session_year_id' => $session_year_id,
        ])->pluck('type', 'student_id')->toArray();

        // Get approved leaves for the selected date, keyed by user_id with the leave-day type.
        $leaveTypeByUserId = Leave::approvedTypesForDate($date);

        // Get students for the class section
        // $sql = Students::with('user')->where('class_section_id', $class_section_id)->whereHas('user', function ($q) {
        //     $q->where('status', 1);
        // });

        $sql = Students::with('user')
            ->whereHas('studentSessions', function ($q) use ($session_year_id, $class_section_id) {
                $q->where('session_year_id', $session_year_id)
                    ->where('class_section_id', $class_section_id);
            });

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->whereRaw("concat(first_name,' ',last_name) LIKE ?", ['%'.$search.'%'])
                            ->orWhere('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        $total = $sql->count();

        // The "student_id" column (data-field="student_id") just displays each row's own id,
        // and "no" is a client-side row counter — neither is a real column on `students`.
        $sortColumnMap = [
            'student_id' => 'id',
            'no' => 'id',
            'roll_no' => 'roll_number',
        ];
        $sql->orderBy($sortColumnMap[$sort] ?? $sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        // Lucide "leave" icon, rendered once (inline SVG) and reused for every on-leave row.
        $leaveIcon = Blade::render('<x-icon name="leave" :size="14" class="attendance-leave-pill__icon" />');

        foreach ($res as $row) {
            $isOnLeave = isset($leaveTypeByUserId[$row->user_id]);
            $existingType = isset($existingAttendance[$row->id]) ? $existingAttendance[$row->id] : null;

            if ($isOnLeave) {
                // Approved leave locks the attendance — no radios. We show a single "On Leave"
                // pill with the leave type; a hidden input still carries the mapped attendance
                // value so it is saved on submit. To change it, reject the leave first.
                $leaveType = Attendance::leaveTypeToType($leaveTypeByUserId[$row->user_id]);
                $leaveTypeLabel = match ($leaveType) {
                    Attendance::TYPE_FIRST_HALF => trans('first_half'),
                    Attendance::TYPE_SECOND_HALF => trans('second_half'),
                    default => trans('full_day'),
                };
                $type = '<input type="hidden" class="type" name="type'.$row->id.'" value="'.$leaveType.'">';
                $type .= '<span class="attendance-leave-pill">'
                    .'<span class="attendance-leave-pill__text">'.trans('on_leave').'</span>'
                    .'<span class="attendance-leave-pill__type">'.$leaveTypeLabel.'</span>'
                    .'</span>';
            } else {
                $type = '<div class="att-toggle-group">'
                    .'<label class="att-toggle att-toggle--present">'
                    // A day without attendance yet starts as present, so teachers only mark who is away.
                    .'<input required type="radio" class="type att-toggle__radio" name="type'.$row->id.'" value="1" '.(in_array($existingType, [1, null], true) ? 'checked' : '').'>'
                    .'<span>'.trans('present').'</span>'
                    .'</label>'
                    .'<label class="att-toggle att-toggle--absent">'
                    .'<input type="radio" class="type att-toggle__radio" name="type'.$row->id.'" value="0" '.($existingType === 0 ? 'checked' : '').'>'
                    .'<span>'.trans('absent').'</span>'
                    .'</label>'
                    .'<label class="att-toggle att-toggle--half">'
                    .'<input type="radio" class="type att-toggle__radio" name="type'.$row->id.'" value="4" '.($existingType === 4 ? 'checked' : '').'>'
                    .'<span>'.trans('first_half_absent').'</span>'
                    .'</label>'
                    .'<label class="att-toggle att-toggle--half">'
                    .'<input type="radio" class="type att-toggle__radio" name="type'.$row->id.'" value="5" '.($existingType === 5 ? 'checked' : '').'>'
                    .'<span>'.trans('second_half_absent').'</span>'
                    .'</label>'
                    .'</div>';
            }

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = "<input type='text' name='student_id[]' class='form-control' readonly value=".$row->id.'>';
            $tempRow['admission_no'] = $row->admission_no;
            $tempRow['roll_no'] = $row->roll_number;
            $tempRow['name'] = $row->user->first_name.' '.$row->user->last_name;
            $tempRow['type'] = $type;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function attendance_show(Request $request)
    {
        if (! Auth::user()->can('attendance-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $offset = 0;
        $limit = 200;
        $sort = 'student_id';
        $order = 'ASC';
        $session_year_id = 0;

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
        if (isset($_GET['session_year_id'])) {
            $session_year_id = $_GET['session_year_id'];
        }

        $class_section_id = $request->class_section_id;
        $attendance_type = $request->attendance_type;
        $date = $request->date ? date('Y-m-d', strtotime($request->date)) : Carbon::now()->toDateString();

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'date' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        $sql = Attendance::where('date', $date)
            ->where('session_year_id', $session_year_id)
            ->where('class_section_id', $class_section_id)
            ->with('student');

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            // Grouped so the search cannot widen the date/class-section filters above.
            $sql->where(function ($query) use ($search) {
                $query->where('id', 'LIKE', "%$search%")
                    ->orwhere('student_id', 'LIKE', "%$search%")
                    ->orWhereHas('student.user', function ($q) use ($search) {
                        $q->whereRaw("concat(users.first_name,' ',users.last_name) LIKE ?", ['%'.$search.'%'])
                            ->orwhere('users.first_name', 'LIKE', "%$search%")
                            ->orwhere('users.last_name', 'LIKE', "%$search%");
                    })
                    ->orWhereHas('student', function ($q) use ($search) {
                        $q->where('admission_no', 'LIKE', "%$search%")
                            ->orwhere('id', 'LIKE', "%$search%")
                            ->orwhere('user_id', 'LIKE', "%$search%")
                            ->orwhere('roll_number', 'LIKE', "%$search%");
                    });
            });
        }
        if (isset($attendance_type) && $attendance_type != '') {
            $sql->where('type', $attendance_type);
        }
        $total = $sql->count();

        $sql->orderBy($sort, $order)->skip($offset)->take($limit);
        $res = $sql->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        foreach ($res as $row) {
            $type = $row->type;
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = $row->student_id;
            $tempRow['user_id'] = $row->student->user_id;
            $tempRow['admission_no'] = $row->student->admission_no;
            $tempRow['roll_no'] = $row->student->roll_number;
            $tempRow['name'] = $row->student->user->first_name.' '.$row->student->user->last_name;
            $tempRow['type'] = match ((int) $type) {
                1 => '<label class="badge badge-info"> '.trans('present').'</label>',
                3 => '<label class="badge badge-success"> '.trans('holiday').'</label>',
                4 => '<label class="badge badge-warning"> '.trans('first_half_absent').'</label>',
                5 => '<label class="badge badge-warning"> '.trans('second_half_absent').'</label>',
                default => '<label class="badge badge-danger"> '.trans('absent').'</label>',
            };
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function createBulkData()
    {
        if (! Auth::user()->can('attendance-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $teacher_id = Auth::user()->teacher->id;
        $session_year_id = getSettings('session_year')['session_year'];
        $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->where('session_year_id', $session_year_id)->pluck('class_section_id');
        $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();

        return view('attendance.add_bulk_data', compact('class_sections'));
    }

    public function storeBulkData(Request $request)
    {
        if (! Auth::user()->can('attendance-create') || ! Auth::user()->can('attendance-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'file' => 'required|mimes:csv,txt',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        try {
            $class_section_id = $request->class_section_id;
            $date = $request->date;
            Excel::import(new AttendanceImport($class_section_id, $date), $request->file);
            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (Exception $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function studentExport(Request $request)
    {
        if (! Auth::user()->can('attendance-list')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'date' => 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        $class_section_id = $request->class_section_id;
        $date = $request->date;

        return Excel::download(new StudentsExport($class_section_id, $date), 'student_list.csv');
    }

    public function attendaceReportIndex()
    {
        if (! Auth::user()->can('attendance-report')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
     $sessionYears = SessionYear::all();
        
        
        
        $settings = getSettings();
         $currentSessionYearId = $settings['session_year'];

         $currentSessionYear = SessionYear::where('id', $currentSessionYearId)->first();

        $user = Auth::user();

        if ($user->hasRole('Teacher')) {
            $teacher_id = Auth::user()->teacher->id;
            $class_section_ids = ClassTeacher::where('class_teacher_id', $teacher_id)->pluck('class_section_id');
            $class_sections = ClassSection::with('class', 'section', 'classTeachers', 'class.streams')->whereIn('id', $class_section_ids)->get();
        } else {
            $class_sections = ClassSection::with('class', 'section', 'streams')->get();
        }

        return view('attendance.report', compact('class_sections', 'sessionYears', 'currentSessionYearId', 'currentSessionYear'));
    }

    public function attendanceReportShow(Request $request)
    {
        if (! Auth::user()->can('attendance-report')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }

        $offset = 0;
        $limit = 200;
        $sort = 'id';
        $order = 'ASC';

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

        $total_days = 0;
        $session_year_id = $request->session_year_id;
        $class_section_id = $request->class_section_id;
        $semester_id = $request->semester_id;

        // Validate required parameters
        if (empty($class_section_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Class section is required',
                'total' => 0,
                'rows' => [],
            ]);
        }

        if (empty($session_year_id)) {
            return response()->json([
                'error' => true,
                'message' => 'Session year is required',
                'total' => 0,
                'rows' => [],
            ]);
        }

         $currentSessionYear = SessionYear::where('id', $session_year_id)->first();
        

        if (! $currentSessionYear) {
            return response()->json([
                'error' => true,
                'message' => 'Session year not found',
                'total' => 0,
                'rows' => [],
            ]);
        }

        $classSection = ClassSection::find($class_section_id);
        if (! $classSection) {
            return response()->json([
                'error' => true,
                'message' => 'Class section not found',
                'total' => 0,
                'rows' => [],
            ]);
        }

        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacher_id = $user->teacher->id;
            $classTeacherQuery = ClassTeacher::where('class_section_id', $class_section_id)
                ->where('class_teacher_id', $teacher_id)
                ->where('session_year_id', $session_year_id);

            if (! empty($semester_id)) {
                $classTeacherQuery->where('semester_id', $semester_id);
            }

            $isTeacherAssigned = $classTeacherQuery->exists();

            if (! $isTeacherAssigned) {
                return response()->json([
                    'error' => true,
                    'message' => 'You are not assigned to this class section',
                    'total' => 0,
                    'rows' => [],
                ]);
            }
        }

        $currentDate = Carbon::now();
        $startDate = Carbon::parse($currentSessionYear->start_date);
        $endDate = Carbon::parse($currentSessionYear->end_date);

        if (! empty($semester_id)) {
            $selectedSemester = Semester::where('id', $semester_id)
                ->where('session_year_id', $session_year_id)
                ->first();

            if ($selectedSemester) {
                $startDate = Carbon::parse($selectedSemester->start_date);
                $endDate = Carbon::parse($selectedSemester->end_date);
            }
        }

        $rangeEndDate = $endDate->lt($currentDate) ? $endDate : $currentDate;

        $leaveMaster = LeaveMaster::where('session_year_id', $currentSessionYear->id)->first();
        $holiday_days = '';

        if ($leaveMaster && $leaveMaster->holiday_days) {
            $holiday_days = $leaveMaster->holiday_days;
        }

        $holiday_days = explode(',', $holiday_days);

        $holiday_date = Attendance::where('session_year_id', $currentSessionYear->id)
            ->where('type', 3)
            ->distinct()
            ->pluck('date')
            ->toArray();

        $holiday = Holiday::where('session_year_id', $currentSessionYear->id)->pluck('date')->toArray();
        $holidays = array_unique(array_merge($holiday_date, $holiday));

        $dateRange = new Collection;
        if ($startDate->lte($rangeEndDate)) {
            for ($date = $startDate->copy(); $date->lte($rangeEndDate); $date->addDay()) {
                $dateRange->push($date->format('Y-m-d'));
            }
        }

        $filteredDateRange = $dateRange->filter(function ($date) use ($holiday_days) {
            $carbonDate = Carbon::parse($date);

            return ! in_array($carbonDate->format('l'), $holiday_days);
        });

        $workingDays = $filteredDateRange->diff($holidays);
        $total_days = $workingDays->count();

        // ---------------------------------------------------------------
        // Query StudentSessions directly instead of Students
        // ---------------------------------------------------------------
        $sql = StudentSessions::with(['student', 'student.user'])
            ->join('students', 'students.id', '=', 'student_sessions.student_id')
            ->select('student_sessions.*')
            ->where('student_sessions.session_year_id', $currentSessionYear->id)
            ->where('student_sessions.status', 1)
            ->where('student_sessions.class_section_id', $class_section_id);

        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(function ($query) use ($search) {
                $query->where('students.id', 'LIKE', "%$search%")
                    ->orWhereHas('student.user', function ($q) use ($search) {
                        $q->whereRaw("concat(first_name,' ',last_name) LIKE ?", ['%'.$search.'%'])
                            ->orWhere('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        $total = $sql->count();

        // Map display/virtual columns sent by bootstrap-table to real `students` columns.
        $sortColumnMap = [
            'student_id' => 'id',
            'roll_no' => 'roll_number',
        ];
        $sort = $sortColumnMap[$sort] ?? $sort;

        // Guard against ordering by columns that don't exist on the students table (e.g. computed rows).
        $sortableColumns = ['id', 'admission_no', 'roll_number'];
        if (! in_array($sort, $sortableColumns, true)) {
            $sort = 'id';
        }

        // Map display/virtual columns sent by bootstrap-table to real `students` columns.
        $sortColumnMap = [
            'student_id' => 'id',
            'roll_no' => 'roll_number',
        ];
        $sort = $sortColumnMap[$sort] ?? $sort;

        // Guard against ordering by columns that don't exist on the students table (e.g. computed rows).
        $sortableColumns = ['id', 'admission_no', 'roll_number'];
        if (! in_array($sort, $sortableColumns, true)) {
            $sort = 'id';
        }

        $sortColumnMap = [
            'id' => 'student_sessions.id',
            'student_id' => 'students.id',
            'admission_no' => 'students.admission_no',
            'roll_no' => 'students.roll_number',
        ];

        $sortColumn = $sortColumnMap[$sort] ?? 'student_sessions.id';

        $res = $sql->orderBy($sortColumn, $order)
            ->skip($offset)
            ->take($limit)
            ->get();

        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;

        foreach ($res as $row) {
            $student = $row->student;

            $tempRow['id'] = $student->id;
            $tempRow['no'] = $no++;
            $tempRow['student_id'] = "<input type='text' name='student_id[]' class='form-control' readonly value=".$student->id.'>';
            $tempRow['admission_no'] = $student->admission_no;
            $tempRow['roll_no'] = $student->roll_number;
            $tempRow['name'] = $student->user->first_name.' '.$student->user->last_name;
            $tempRow['total_days'] = $total_days ?? 0;

            // Count the student's ACTUAL attendance within the selected period (semester
            // date range when a semester is chosen, otherwise the whole session year),
            // bounded to $rangeEndDate so the numerator spans the same window as
            // $total_days (both stop at today for an in-progress period).
            // Deliberately NOT gated by $workingDays: a day marked present/absent counts
            // even if it happens to fall on a holiday-classified date, so these totals stay
            // consistent with the student-report tab (which counts raw attendance records).
            // $workingDays is used only for $total_days (the percentage denominator).
            $attendanceCountsQuery = Attendance::where('student_id', $student->id)
                ->where('session_year_id', $currentSessionYear->id)
                ->where('class_section_id', $class_section_id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $rangeEndDate->format('Y-m-d')]);
            $full_present_days = (clone $attendanceCountsQuery)->where('type', 1)->count();
            $full_absent_days = (clone $attendanceCountsQuery)->where('type', 0)->count();
            // First Half (4) & Second Half (5) count as half a present and half an absent day.
            $half_days = (clone $attendanceCountsQuery)->whereIn('type', [4, 5])->count();

            $present_days = $full_present_days + ($half_days * 0.5);
            $absent_days = $full_absent_days + ($half_days * 0.5);
            $tempRow['present_days'] = $present_days;
            $tempRow['absent_days'] = $absent_days;

            $percentage = $total_days > 0 ? ($present_days / $total_days) * 100 : 0;
            $tempRow['percentage'] = round($percentage, 2);

            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    public function checkStaffLeave(Request $request)
    {
        if (! Auth::user()->can('attendance-staff')) {
            return response()->json(['on_leave' => []]);
        }
        $staffIds = $request->input('staff_ids', []);
        $date = $request->input('date');
        $sessionYearId = $request->input('session_year_id');

        if (empty($staffIds) || empty($date)) {
            return response()->json(['on_leave' => []]);
        }

        $userIds = Staff::whereIn('id', $staffIds)->pluck('user_id')->toArray();

        if (empty($userIds)) {
            $userIds = $staffIds;
        }

        if (empty($userIds)) {
            return response()->json(['on_leave' => []]);
        }

        $leaveQuery = Leave::with('user')
            ->whereIn('user_id', $userIds)
            ->where('status', '1')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->whereNull('deleted_at');

        if ($sessionYearId) {
            $leaveQuery->where('session_year_id', $sessionYearId);
        }

        $onLeave = $leaveQuery->get()->map(function ($leave) {
            return [
                'id' => $leave->user_id,
                'name' => optional($leave->user)->first_name
                    .' '
                    .optional($leave->user)->last_name,
            ];
        });

        return response()->json(['on_leave' => $onLeave]);
    }
}
