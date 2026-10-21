<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\EmailTempalate;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Teacher;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\IdCardService;
use App\Services\MailService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function staffattendanceData(Request $request)
    {
        if (! Auth::user()->can('attendance-staff')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }

        $selectedDate = $request->get('date') ?? $request->get('month') ?? now()->format('Y-m-d');

        $carbonDate = \Carbon\Carbon::parse($selectedDate);
        $sessionYearId = $request->get('session_year_id');

        if (empty($sessionYearId)) {
            $sessionYearId = getSettings('session_year')['session_year'] ?? null;
        }

        $sessionYear = null;
        if ($sessionYearId) {
             $sessionYear = SessionYear::find($sessionYearId);
           
        }

        // Clamp the requested date into the session year's range so an
        // out-of-range date (e.g. leftover from a previous session year
        // selection) can't pull in a month outside it.
        $syStart = $syEnd = null;
        if ($sessionYear) {
            $syStart = \Carbon\Carbon::parse($sessionYear->start_date)->startOfDay();
            $syEnd = \Carbon\Carbon::parse($sessionYear->end_date)->endOfDay();

            if ($carbonDate->lt($syStart)) {
                $carbonDate = $syStart->copy();
            } elseif ($carbonDate->gt($syEnd)) {
                $carbonDate = $syEnd->copy();
            }
        }

        $month = $carbonDate->month;
        $year = $carbonDate->year;
        $daysInMonth = $carbonDate->daysInMonth;

        // Bound the day columns to the session year, so a month sitting at
        // the edge of the session (e.g. session starts mid-June) only shows
        // the days that actually belong to it.
        $firstDay = 1;
        $lastDay = $daysInMonth;
        $rangeStart = $carbonDate->copy()->startOfMonth();
        $rangeEnd = $carbonDate->copy()->endOfMonth();

        if ($syStart && $syStart->year === $year && $syStart->month === $month) {
            $firstDay = $syStart->day;
            $rangeStart = $syStart->copy();
        }
        if ($syEnd && $syEnd->year === $year && $syEnd->month === $month) {
            $lastDay = $syEnd->day;
            $rangeEnd = $syEnd->copy();
        }

        // Attendance is keyed by user_id, so list both staff and teachers.
        $staff = Staff::with('user:id,first_name,last_name,image')->get();
        $teachers = Teacher::with('user:id,first_name,last_name,image')->get();
        $members = $staff->concat($teachers)->filter(fn ($member) => $member->user)->unique('user_id');

        $result = [];

        foreach ($members as $member) {

            $fullName = trim(
                ($member->user->first_name ?? '').' '.
                    ($member->user->last_name ?? '')
            );

            $row = [
                'name' => $fullName,
                'image' => $member->user->image ?? 'default.png',
            ];

            // initialize only the days that fall within the session year
            for ($d = $firstDay; $d <= $lastDay; $d++) {
                $row['day_'.$d] = null;
            }

            $records = StaffAttendance::where('user_id', $member->user_id)
                ->whereBetween('date', [$rangeStart->format('Y-m-d'), $rangeEnd->format('Y-m-d')]);

            if ($sessionYearId) {
                $records->where('session_year_id', $sessionYearId);
            }

            $records = $records->get();

            foreach ($records as $record) {
                $dayNum = \Carbon\Carbon::parse($record->date)->day;
                $row['day_'.$dayNum] = $record->type;
            }

            $result[] = $row;
        }

        return response()->json([
            'data' => $result,
            'days' => range($firstDay, $lastDay),
            'month' => $month,
            'year' => $year,
            'session_year' => $sessionYear ? [
                'id' => $sessionYear->id,
                'start_date' => $sessionYear->start_date,
                'end_date' => $sessionYear->end_date,
            ] : null,
        ]);
    }

    public function staffList()
    {
        if (! Auth::user()->can('attendance-staff')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        return view('staff.demoattendance');
    }

    public function staffListData(Request $request)
    {
        if (! Auth::user()->can('attendance-staff')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }

        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        $carbonDate = \Carbon\Carbon::parse($selectedDate);

        $sessionYearId = $request->get('session_year_id');

        if (empty($sessionYearId)) {
            $sessionYearId = getSettings('session_year')['session_year'] ?? null;
        }

        $sessionYear = null;
        if ($sessionYearId) {
             $sessionYear = SessionYear::find($sessionYearId);
            

        }

        // Clamp the requested date into the session year's range.
        if ($sessionYear) {
            $syStart = \Carbon\Carbon::parse($sessionYear->start_date)->startOfDay();
            $syEnd = \Carbon\Carbon::parse($sessionYear->end_date)->endOfDay();

            if ($carbonDate->lt($syStart)) {
                $carbonDate = $syStart->copy();
            } elseif ($carbonDate->gt($syEnd)) {
                $carbonDate = $syEnd->copy();
            }
        }

        $month = $carbonDate->month;
        $year = $carbonDate->year;
        $day = $carbonDate->day;

        $badgeColors = [
            'present' => 'badge-success',
            'absent' => 'badge-danger',
            'halfday' => 'badge-info',
            'first_half' => 'badge-info',
            'second_half' => 'badge-info',
            'holiday' => 'badge-warning',
            'leave' => 'badge-dark',
        ];

        $attendanceForMonth = function ($query) use ($month, $year, $sessionYearId) {
            $query->select('id', 'user_id', 'date', 'type', 'remark', 'session_year_id')
                ->whereMonth('date', $month)
                ->whereYear('date', $year);

            if ($sessionYearId) {
                $query->where('session_year_id', $sessionYearId);
            }
        };

        // Attendance is keyed by user_id, so list both staff and teachers.
        $staff = Staff::with(['user:id,first_name,last_name,image', 'attendance' => $attendanceForMonth])->get();
        $teachers = Teacher::with(['user:id,first_name,last_name,image', 'attendance' => $attendanceForMonth])->get();

        $members = $staff->concat($teachers)->filter(fn ($member) => $member->user)->unique('user_id');

        $tableData = [];

        foreach ($members as $member) {

            $attendanceByDay = $member->attendance->keyBy(function ($attendance) {
                return \Carbon\Carbon::parse($attendance->date)->day;
            });

            $attendanceRecord = $attendanceByDay->get($day);
            $type = $attendanceRecord->type ?? null;
            $remark = $attendanceRecord->remark ?? null;

            $tableData[] = [
                'id' => $member->user_id,
                'name' => trim(
                    ($member->user->first_name ?? '').' '.
                        ($member->user->last_name ?? '')
                ),
                'image' => $member->user->image ?? 'default.png',
                'status_badge' => $badgeColors[$type] ?? 'badge-secondary',
                'status_text' => $type
                    ? ucfirst(str_replace('_', ' ', $type))
                    : 'Not Marked',
                'remark' => $remark,
            ];
        }
        if ($request->ajax()) {
            return response()->json($tableData);
        }

        return response()->json($tableData);
    }

    public function index()
    {
        if (! Auth::user()->can('staff-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        // Every custom role can be given to staff, not just the newest few.
        $roles = Role::where('custom_role', 1)->orderBy('id', 'DESC')->get();

        return view('staff.index', compact('roles'));
    }

    public function bulkStore(Request $request)
    {
        if (! Auth::user()->can('attendance-staff')) {
            return response()->json(['status' => false, 'message' => trans('no_permission_message')], 403);
        }

        $validated = $request->validate([
            'staff_id' => 'nullable|integer|exists:users,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'integer|exists:users,id',
            'session_year_id' => 'required|integer',
            'date' => 'required|date',
            'type' => 'required|in:present,absent,first_half,second_half,holiday,leave',
            'remark' => 'nullable|string',
        ]);

        foreach ($request->staff_ids as $staff_id) {
            StaffAttendance::updateOrCreate([
                'user_id' => $staff_id,
                'date' => $request->date,
            ], [
                'session_year_id' => $validated['session_year_id'],
                'type' => $validated['type'],
                'remark' => $validated['remark'] ?? 'bulk attendance marked',

            ]);
        }
        $messages = [
            'present' => 'Present marked successfully',
            'absent' => 'Absent marked successfully',
            'first_half' => 'First half marked successfully',
            'second_half' => 'Second half marked successfully',
            'holiday' => 'Holiday marked successfully',
            'leave' => 'Leave marked successfully',
        ];

        return response()->json([
            'success' => true,
            'type' => $validated['type'],
            'message' => $messages[$validated['type']] ?? 'Bulk attendance saved successfully',
        ]);
    }

    private function syncAttendancewithLeave($userId, $date, $type, $sessionYearId, $remark = null)
    {
        $typeMap = [
            'absent' => 'Full',
            'first_half' => 'First Half',
            'second_half' => 'Second Half',
        ];

        $dateFormatted = date('Y-m-d', strtotime($date));

        if (array_key_exists($type, $typeMap)) {
            $leaveType = $typeMap[$type];

            $leaveDetail = LeaveDetail::whereDate('date', $dateFormatted)
                ->whereHas('leave', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->first();

            if ($leaveDetail) {
                $leaveDetail->update(['type' => $leaveType]);
                $leaveDetail->leave()->update(['status' => '1']);
            } else {
                $leaveMaster = LeaveMaster::where('session_year_id', $sessionYearId)->first();

                $leave = Leave::create([
                    'user_id' => $userId,
                    'leave_master_id' => $leaveMaster?->id,
                    'reason' => $remark ?? 'Marked as '.str_replace('_', ' ', $type).' in attendance',
                    'from_date' => $dateFormatted,
                    'to_date' => $dateFormatted,
                    'status' => '1', // Approved
                    'session_year_id' => $sessionYearId,
                ]);

                LeaveDetail::create([
                    'leave_id' => $leave->id,
                    'date' => $dateFormatted,
                    'type' => $leaveType,
                ]);
            }

        } elseif (in_array($type, ['present', 'holiday'])) { // Fixed typo 'presnet'
            $leaveDetail = LeaveDetail::whereDate('date', $dateFormatted)
                ->whereHas('leave', function ($query) use ($userId) {
                    $query->where('user_id', $userId); // Fixed variable name to $userId
                })
                ->first();

            if ($leaveDetail) {
                $leave = $leaveDetail->leave;
                $leaveDetail->delete();

                // Delete the parent leave if no day details are left
                if ($leave && $leave->leave_detail()->count() === 0) {
                    $leave->delete();
                }
            }
        }
    }

    public function storeAttendance(Request $request)
    {
        if (! Auth::user()->can('attendance-staff')) {
            return response()->json(['status' => false, 'message' => trans('no_permission_message')], 403);
        }

        $validated = $request->validate([
            'staff_id' => 'nullable|integer|exists:users,id',
            'staff_ids' => 'nullable|array',
            'staff_ids.*' => 'integer|exists:users,id',
            'session_year_id' => 'required|integer',
            'date' => 'required|date',
            'type' => 'required|in:present,absent,first_half,second_half,holiday,leave',
            'remark' => 'nullable|string',
        ]);

        if (! empty($validated['staff_ids'])) {

            DB::transaction(function () use ($validated) {

                $attendanceData = [];

                foreach ($validated['staff_ids'] as $staffId) {
                    $attendanceData[] = [
                        'user_id' => $staffId,
                        'date' => $validated['date'],
                        'session_year_id' => $validated['session_year_id'],
                        'type' => $validated['type'],
                        'remark' => $validated['remark'] ?? 'Bulk attendance marked',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                StaffAttendance::upsert(
                    $attendanceData,
                    ['user_id', 'date'],
                    ['session_year_id', 'type', 'remark', 'updated_at']
                );

                // Still needs optimization if possible
                foreach ($validated['staff_ids'] as $staffId) {
                    $this->syncAttendancewithLeave(
                        $staffId,
                        $validated['date'],
                        $validated['type'],
                        $validated['session_year_id'],
                        $validated['remark'] ?? 'Bulk attendance marked'
                    );
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Bulk attendance saved successfully.',
            ]);
        }

        try {
            DB::transaction(function () use ($validated) {
                StaffAttendance::updateOrCreate(
                    [
                        'user_id' => $validated['staff_id'],
                        'date' => $validated['date'],
                    ],
                    [
                        'session_year_id' => $validated['session_year_id'],
                        'type' => $validated['type'],
                        'remark' => $validated['remark'],
                    ]
                );

                $this->syncAttendancewithLeave(
                    $validated['staff_id'],
                    $validated['date'],
                    $validated['type'],
                    $validated['session_year_id'],
                    $validated['remark']
                );
            });
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => trans('error_occurred'),
            ], 500);
        }

        return response()->json([
            'status' => true,
            'message' => 'Attendance saved successfully',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! Auth::user()->can('staff-create')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $validator = Validator::make($request->all(), [
            'role_id' => ['required', 'numeric', $this->assignableRoleRule()],
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email',
            'gender' => 'required',
            'mobile' => 'required|numeric|regex:/^[0-9]{7,16}$/',
            'image' => 'mimes:jpeg,png,jpg|image|max:2048',
            'dob' => 'required|date',
            'address' => 'nullable',

        ], [
            'mobile.regex' => __('The mobile number must be a length of 7 to 15 digits.'),
        ]);

        if ($validator->fails()) {
            ResponseService::validationError($validator->errors()->first());
        }
        try {
            $role = Role::findOrFail($request->role_id);

            $check_user = User::where('email', $request->email)->onlyTrashed();
            if ($check_user->count()) {
                $user_exists = $check_user->first();
                DB::table('users')->where('id', $user_exists->id)->update(['deleted_at' => null]);

                $user = User::findOrFail($user_exists->id);

                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    // made file name with combination of current time
                    $file_name = time().'-'.$image->getClientOriginalName();
                    // made file path to store in database
                    $file_path = 'staff/'.$file_name;
                    // resized image
                    resizeImage($image);

                    // stored image to storage/public/teachers folder
                    $destinationPath = storage_path('app/public/staff');
                    $image->move($destinationPath, $file_name);

                    $user->image = $file_path;
                } else {
                    $user->image = '';
                }

                $staff_plain_text_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob)));
                $user->password = Hash::make($staff_plain_text_password);

                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->gender = $request->gender;
                $user->mobile = $request->mobile;
                $user->dob = date('Y-m-d', strtotime($request->dob));
                $user->current_address = $request->address;
                $user->save();

                $check_staff = DB::table('staffs')->where('user_id', $user->id)->whereNotNull('deleted_at');
                if ($check_staff->count()) {
                    $staff_exists = $check_staff->first();
                    DB::table('staffs')->where('id', $staff_exists->id)->update(['deleted_at' => null]);
                    $staff = Staff::findOrFail($staff_exists->id);
                    $staff->user_id = $user->id;
                    $staff->update();
                }

                // A restored account must not keep the roles it had before it
                // was deleted (it could have been a Super Admin).
                $user->syncRoles($role);

                $school_name = getSettings('school_name');
                // $data = [
                //     'subject' => 'Welcome to '.$school_name['school_name'],
                //     'name' => $request->first_name.' '.$request->last_name,
                //     'email' => $request->email,
                //     'password' => $staff_plain_text_password,
                //     'school_name' => $school_name['school_name'],
                //     'role' => $role->name,

                // ];

                // MailService::sendWithFallback('staff.email', $data, function ($message) use ($data) {
                //     $message->to($data['email'])->subject($data['subject']);
                // });
                $AcceptTemplate = EmailTempalate::where('type', 'staff_registration')->value('content');

                $applink = Settings::whereIn('type', ['teacher_app_link', 'teacher_ios_app_link'])
                    ->pluck('message', 'type')
                    ->toArray();

                $android_app = $applink['teacher_app_link'] ?? '';
                $ios_app = $applink['teacher_ios_app_link'] ?? '';

                $school_name = getSettings('school_name');
                $school_contact = getSettings('school_contact'); // adjust key name to match your Settings table
                $school_email = getSettings('school_email');
                $support_email = getSettings('support_email');
                $support_contact = getSettings('support_contact');
                $url = url('/');

                $data = [
                    'subject' => 'Welcome to '.$school_name['school_name'],
                    'name' => (string) $request->first_name.' '.$request->last_name,
                    'email' => (string) $request->email,
                    'password' => (string) $staff_plain_text_password,
                    'role' => (string) $role->name,
                    'type' => 'staff_registration',
                    'school_name' => (string) $school_name['school_name'],
                    'school_contact' => (string) $school_contact['school_contact'] ?? '',
                    'school_email' => (string) $school_email['school_email'] ?? '',
                    'support_email' => (string) $support_email['support_email'] ?? '',
                    'support_contact' => (string) $support_contact['support_contact'] ?? '',
                    'android_app' => (string) $android_app,
                    'ios_app' => (string) $ios_app,
                    'url' => (string) $url,
                ];

                $htmlcontent = EmailTemplateService::render((string) $AcceptTemplate, $data);

                $emailPayload = array_merge($data, [
                    'body' => $htmlcontent,
                ]);

                MailService::sendWithFallback('staff.email', $emailPayload, function ($message) use ($emailPayload) {
                    $message->to($emailPayload['email'])->subject($emailPayload['subject']);
                });
            } else {
                $user = new User;

                if ($request->hasFile('image')) {
                    $image = $request->file('image');
                    // made file name with combination of current time
                    $file_name = time().'-'.$image->getClientOriginalName();
                    // made file path to store in database
                    $file_path = 'staff/'.$file_name;
                    // resized image
                    resizeImage($image);

                    // stored image to storage/public/teachers folder
                    $destinationPath = storage_path('app/public/staff');
                    $image->move($destinationPath, $file_name);

                    $user->image = $file_path;
                } else {
                    $user->image = '';
                }

                $staff_plain_text_password = str_replace('-', '', date('d-m-Y', strtotime($request->dob)));
                $user->password = Hash::make($staff_plain_text_password);

                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->email = $request->email;
                $user->gender = $request->gender;
                $user->mobile = $request->mobile;
                $user->dob = date('Y-m-d', strtotime($request->dob));
                $user->current_address = $request->address;
                $user->save();

                $user->assignRole($role);

                $staff = new Staff;
                $staff->user_id = $user->id;
                $staff->save();

                $school_name = getSettings('school_name');
                $school_email = getSettings('school_email');
                $school_contact = getSettings('school_contact');

                // $stafftemplate = EmailTempalate::where('type', 'staff_registration')->first();
                $stafftemplate = EmailTempalate::where('type', 'staff_registration')->value('content');

                // $data = [
                //     'subject' => 'Welcome to ' . $school_name['school_name'],
                //     'name' => $request->first_name . ' ' . $request->last_name,
                //     'email' => $request->email,
                //     'password' => $staff_plain_text_password,
                //     'school_name' => $school_name['school_name'],
                //     'role' => $role->name,
                //     'body' => $stafftemplate,

                // ];

                // MailService::sendWithFallback('staff.email', $data, function ($message) use ($data) {
                //     $message->to($data['email'])->subject($data['subject']);
                // });
                $AcceptTemplate = EmailTempalate::where('type', 'staff_registration')->value('content');

                $applink = Settings::whereIn('type', ['teacher_app_link', 'teacher_ios_app_link'])
                    ->pluck('message', 'type')
                    ->toArray();

                $android_app = $applink['teacher_app_link'] ?? '';
                $ios_app = $applink['teacher_ios_app_link'] ?? '';

                $school_name = getSettings('school_name');
                $school_contact = getSettings('school_contact');
                $school_email = getSettings('school_email');
                $support_email = getSettings('support_email');
                $support_contact = getSettings('support_contact');
                $url = url('/');

                $data = [
                    'subject' => 'Welcome to '.$school_name['school_name'],
                    'name' => (string) $request->first_name.' '.$request->last_name,
                    'email' => (string) $request->email,
                    'password' => (string) $staff_plain_text_password,
                    'role' => (string) $role->name,
                    'type' => 'staff_registration',
                    'school_name' => (string) $school_name['school_name'],
                    'school_contact' => (string) ($school_contact['school_contact'] ?? ''),
                    'school_email' => (string) ($school_email['school_email'] ?? ''),
                    'support_email' => (string) ($support_email['support_email'] ?? ''),
                    'support_contact' => (string) ($support_contact['support_contact'] ?? ''),
                    'android_app' => (string) $android_app,
                    'ios_app' => (string) $ios_app,
                    'url' => (string) $url,
                ];

                $htmlcontent = EmailTemplateService::render((string) $AcceptTemplate, $data);

                $emailPayload = array_merge($data, [
                    'body' => $htmlcontent,
                ]);

                MailService::sendWithFallback('staff.email', $emailPayload, function ($message) use ($emailPayload) {
                    $message->to($emailPayload['email'])->subject($emailPayload['subject']);
                });
            }
            ResponseService::successResponse(trans('data_store_successfully'));
        } catch (\Throwable $e) {
            ResponseService::errorResponse(trans('error_occurred'), null, null, $e);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show()
    {
        if (! Auth::user()->can('staff-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $offset = request('offset', 0);
        $limit = request('limit', 10);
        $sort = request('sort', 'id');
        $order = request('order', 'ASC');
        $search = request('search');

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

        $sql = Staff::with('user', 'user.roles');
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where('id', 'LIKE', "%$search%")
                ->orwhere('user_id', 'LIKE', "%$search%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'LIKE', "%$search%")
                        ->orwhere('last_name', 'LIKE', "%$search%")
                        ->orwhere('gender', 'LIKE', "%$search%")
                        ->orwhere('email', 'LIKE', "%$search%")
                        // ->orwhere('dob', 'LIKE', "%" . date('Y-m-d', strtotime($search)) . "%")
                        ->orwhere('current_address', 'LIKE', "%$search%");
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
        foreach ($res as $row) {
            $operate = '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon edit-data" data-id='.$row->id.' data-url='.url('staff').' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
            $operate .= '<a class="btn btn-xs btn-gradient-danger btn-rounded btn-icon deletedata" data-id='.$row->id.' data-user_id='.$row->user_id.' data-url='.url('staff', $row->user_id).' title="Delete"><i class="fa fa-trash"></i></a>';

            $data = getSettings('date_formate');
            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['user_id'] = $row->user_id;
            $tempRow['role_id'] = $row->user->roles->pluck('id')->implode(', ');
            $tempRow['roles'] = $row->user->roles->pluck('name')->implode(', ');
            $tempRow['first_name'] = $row->user->first_name;
            $tempRow['last_name'] = $row->user->last_name;
            $tempRow['gender'] = $row->user->gender;
            $tempRow['address'] = $row->user->current_address;
            $tempRow['email'] = $row->user->email;
            $tempRow['dob'] = date($data['date_formate'], strtotime($row->user->dob));
            $tempRow['mobile'] = $row->user->mobile;
            $tempRow['image'] = $row->user->image;
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }

        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // dd($request->all());
        if (! Auth::user()->can('staff-edit')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'role_id' => ['required', 'numeric', $this->assignableRoleRule()],
            'user_id' => 'required|integer',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email,'.$request->user_id,
            'gender' => 'required',
            'mobile' => 'required|numeric|regex:/^[0-9]{7,16}$/',
            'image' => 'mimes:jpeg,png,jpg|image|max:2048',
            'dob' => 'required',
            'address' => 'nullable',

        ], [
            'mobile.regex' => __('The mobile number must be a length of 7 to 15 digits.'),
        ]);

        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        $user = User::find($request->user_id);
        if (! $user || ! $this->canManageStaffUser($user)) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        try {
            $role = Role::findOrFail($request->role_id);

            if ($request->hasFile('image')) {

                if (Storage::disk('public')->exists($user->getRawOriginal('image'))) {
                    Storage::disk('public')->delete($user->getRawOriginal('image'));
                }

                $image = $request->file('image');
                // made file name with combination of current time
                $file_name = time().'-'.$image->getClientOriginalName();
                // made file path to store in database
                $file_path = 'staff/'.$file_name;
                // resized image
                resizeImage($image);

                // stored image to storage/public/teachers folder
                $destinationPath = storage_path('app/public/staff');
                $image->move($destinationPath, $file_name);

                $user->image = $file_path;
            }

            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->gender = $request->gender;
            $user->mobile = $request->mobile;
            $user->dob = date('Y-m-d', strtotime($request->dob));
            $user->current_address = $request->address;
            $user->save();

            $user->syncRoles($role);
            ResponseService::successResponse(trans('data_store_successfully'));
        } catch (\Throwable $e) {
            ResponseService::errorResponse(trans('error_occurred'), null, null, $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('staff-delete')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $user = User::find($id);
        if (! $user || $user->id === Auth::id() || ! $this->canManageStaffUser($user)) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }
        try {
            if (Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }
            $user->delete();

            $staff = Staff::where('user_id', $id);
            $staff->delete();
            -ResponseService::successResponse(trans('data_delete_successfully'));
        } catch (\Throwable $e) {
            ResponseService::errorResponse(trans('error_occurred'), null, null, $e);
        }
    }

    public function generateStaffId(Request $request)
    {
        if (! Auth::user()->canAny(['staff-list', 'generate-id-card'])) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        // /generate_teacher_id opens with the Teacher tab selected; /generate_staff_id with Staff.
        // Both serve the same picker — the dropdown just switches which set the table lists.
        $defaultType = $request->routeIs('generate_teacher_id.index') ? 'teacher' : 'staff';

        return view('staff.generate_staff_id', compact('defaultType'));
    }

    /**
     * Unified bootstrap-table data source for the ID-card picker. Lists Teachers or generic
     * Staff (per the `type` query param) with a single, checkbox-friendly schema keyed by the
     * underlying user id — which is exactly what generateStaffIdCard()/IdCardService expect.
     */
    public function staffIdCardList(Request $request)
    {
        if (! Auth::user()->canAny(['staff-list', 'generate-id-card'])) {
            return response()->json(['message' => trans('no_permission_message')]);
        }

        $offset = (int) $request->input('offset', 0);
        $limit = (int) $request->input('limit', 10);
        $sort = $request->input('sort', 'id');
        $order = $request->input('order', 'DESC');
        $search = $request->input('search');
        $rawType = $request->input('type', '');
        $type = in_array($rawType, ['teacher', 'staff'], true) ? $rawType : 'all';
        $dateFormat = getSettings('date_formate')['date_formate'] ?? 'd-m-Y';

        $applySearch = function ($q) use ($search) {
            if (! empty($search)) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('first_name', 'LIKE', "%$search%")
                        ->orWhere('last_name', 'LIKE', "%$search%")
                        ->orWhere('email', 'LIKE', "%$search%")
                        ->orWhere('mobile', 'LIKE', "%$search%")
                        ->orWhere('gender', 'LIKE', "%$search%");
                });
            }
        };

        if ($type === 'all') {
            $teacherQuery = Teacher::with('user');
            $staffQuery = Staff::with('user', 'user.roles');
            $applySearch($teacherQuery);
            $applySearch($staffQuery);

            $teachers = $teacherQuery->get()->filter(fn ($r) => $r->user)->map(fn ($r) => [
                'id' => $r->user_id,
                'name' => $r->user->first_name.' '.$r->user->last_name,
                'image' => $r->user->image,
                'designation' => trans('teacher'),
                'email' => $r->user->email,
                'mobile' => $r->user->mobile,
                'gender' => $r->user->gender,
                'dob' => $r->user->dob ? date($dateFormat, strtotime($r->user->dob)) : '',
                '_sort_key' => $r->$sort ?? $r->id,
            ]);

            $staffs = $staffQuery->get()->filter(fn ($r) => $r->user)->map(fn ($r) => [
                'id' => $r->user_id,
                'name' => $r->user->first_name.' '.$r->user->last_name,
                'image' => $r->user->image,
                'designation' => $r->user->roles->pluck('name')->implode(', '),
                'email' => $r->user->email,
                'mobile' => $r->user->mobile,
                'gender' => $r->user->gender,
                'dob' => $r->user->dob ? date($dateFormat, strtotime($r->user->dob)) : '',
                '_sort_key' => $r->$sort ?? $r->id,
            ]);

            $merged = $teachers->concat($staffs)->sortBy('_sort_key', SORT_REGULAR, $order === 'DESC')->values();
            $total = $merged->count();
            $paged = $merged->slice($offset, $limit)->values();

            $rows = [];
            $no = $offset + 1;
            foreach ($paged as $row) {
                $rows[] = array_merge(array_diff_key($row, ['_sort_key' => null]), ['no' => $no++]);
            }

            return response()->json(['total' => $total, 'rows' => $rows]);
        }

        $sql = $type === 'teacher'
            ? Teacher::with('user')
            : Staff::with('user', 'user.roles');

        $applySearch($sql);

        $total = $sql->count();
        $res = $sql->orderBy($sort, $order)->skip($offset)->take($limit)->get();

        $rows = [];
        $no = $offset + 1;
        foreach ($res as $row) {
            if (! $row->user) {
                continue;
            }

            $rows[] = [
                'id' => $row->user_id,
                'no' => $no++,
                'name' => $row->user->first_name.' '.$row->user->last_name,
                'image' => $row->user->image,
                'designation' => $type === 'teacher'
                    ? trans('teacher')
                    : $row->user->roles->pluck('name')->implode(', '),
                'email' => $row->user->email,
                'mobile' => $row->user->mobile,
                'gender' => $row->user->gender,
                'dob' => $row->user->dob ? date($dateFormat, strtotime($row->user->dob)) : '',
            ];
        }

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function generateStaffIdCard(Request $request, IdCardService $idCardService)
    {
        if (! Auth::user()->canAny(['staff-list', 'generate-id-card'])) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }

        $ids = array_filter(explode(',', (string) $request->user_id));

        try {
            return $idCardService->buildStaffPdf($ids)->stream('staff_id_card.pdf');
        } catch (RuntimeException $e) {
            return redirect()->route('id_card_setting.index')->with('error', $e->getMessage());
        }
    }

    /**
     * Staff can only be given the custom roles the staff form offers, never
     * Super Admin or a built-in role (Teacher, Parent, Student).
     */
    private function assignableRoleRule(): \Illuminate\Validation\Rules\Exists
    {
        return Rule::exists('roles', 'id')
            ->where('custom_role', 1)
            ->whereNot('name', 'Super Admin');
    }

    /**
     * The staff edit/delete actions only apply to staff members, and only a
     * Super Admin may change a Super Admin account.
     */
    private function canManageStaffUser(User $user): bool
    {
        if ($user->hasRole('Super Admin') && ! Auth::user()->hasRole('Super Admin')) {
            return false;
        }

        return Staff::where('user_id', $user->id)->exists();
    }
}
