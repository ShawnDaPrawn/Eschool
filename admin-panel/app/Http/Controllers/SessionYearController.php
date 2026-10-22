<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreSessionYearRequest;
use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\ClassTeacher;
use App\Models\ElectiveSubjectGroup;
use App\Models\Event;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\FeesChoiceable;
use App\Models\FeesPaid;
use App\Models\Grade;
use App\Models\Holiday;
use App\Models\InstallmentFee;
use App\Models\Leave;
use App\Models\LeaveMaster;
use App\Models\Notification;
use App\Models\OnlineExam;
use App\Models\OnlineExamQuestion;
use App\Models\PaymentTransaction;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\StudentSubject;
use App\Models\SubjectTeacher;
use App\Services\ResponseService;
use App\Services\SessionYearService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class SessionYearController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (! Auth::user()->can('session-year-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $sessionYears = SessionYear::select('id', 'name')->orderBy('id', 'desc')->get();
        // Offer to make a new year current straight away when the current one is over.
        $current = SessionYear::find(getSettings('session_year')['session_year'] ?? null);
        $currentYearEnded = ! $current || $current->end_date < date('Y-m-d');

        return view('session_years.index', compact('sessionYears', 'currentYearEnded'));
    }

    /**
     * Make a session year the school's current one (what the apps show).
     */
    public function makeCurrent(int $id, SessionYearService $service)
    {
        if (! Auth::user()->can('session-year-edit')) {
            return response()->json(['error' => true, 'message' => trans('no_permission_message')]);
        }
        $sessionYear = SessionYear::findOrFail($id);
        if ($sessionYear->end_date < date('Y-m-d')) {
            return response()->json(['error' => true, 'message' => trans('session_year_has_ended')]);
        }
        $service->makeCurrent($sessionYear);

        return response()->json([
            'error' => false,
            'message' => trans('session_year_is_now_current', ['name' => $sessionYear->name]),
            'data' => ['id' => $sessionYear->id],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(StoreSessionYearRequest $request, SessionYearService $service)
    {
        try {
            $sessionYear = $service->store($request->validated());

            return response()->json([
                'error' => false,
                'message' => trans('data_store_successfully'),
                'data' => [
                    'id' => $sessionYear->id,
                    'name' => $sessionYear->name,
                ],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
                'data' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function update(Request $request)
    {
        if (! Auth::user()->can('session-year-edit')) {
            $response = [
                'error' => true,
                'message' => trans('no_permission_message'),
            ];

            return response()->json($response);
        }
        $current = SessionYear::find($request->id);
        // A running year's free-app date is usually already past; keeping it must not block other edits.
        $freeAppDateChanged = $current === null
            || ! $request->filled('free_app_use_date')
            || date('Y-m-d', strtotime($request->free_app_use_date)) !== $current->free_app_use_date;
        $request->validate([
            'id' => 'required|exists:session_years,id',
            'name' => ['required', Rule::unique('session_years', 'name')->ignore($request->id)->whereNull('deleted_at')],
            'free_app_use_date' => $freeAppDateChanged ? 'nullable|date|after:today' : 'nullable|date',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'fees_due_date' => 'required|date|after_or_equal:start_date|before_or_equal:end_date',
            'fees_due_charges' => 'required|numeric|min:0|max:100',
            'installment_data.*.name' => 'required_if:edit_include_fee_installments,1',
            'installment_data.*.due_date' => 'required_if:edit_include_fee_installments,1|date|after_or_equal:start_date|before_or_equal:end_date',
            'installment_data.*.due_charges' => 'required_if:edit_include_fee_installments,1|numeric|gt:0',
        ], [
            'name.unique' => trans('session_year_name_already_exists'),
            'installment_data.*.name.required_if' => trans('name_is_required_at_row').' :index',
            'installment_data.*.due_date.required_if' => trans('name_is_required_at_row').' :index',
            'installment_data.*.due_date.date' => trans('due_date_should_be_date_at_row').' :index',
            'installment_data.*.due_date.after_or_equal' => trans('due_date_should_be_after_or_equal_session_year_start_date_at_row').' :index',
            'installment_data.*.due_date.before_or_equal' => trans('due_date_should_be_before_or_equal_session_year_end_date_at_row').' :index',
            'installment_data.*.due_charges.required_if' => trans('due_charges_required_at_row').' :index',
            'installment_data.*.due_charges.numeric' => trans('due_charges_should_be_number_at_row').' :index',
        ]);

        try {
            $session_year = SessionYear::find($request->id);
            $session_year->name = $request->name;
            $session_year->free_app_use_date = isset($request->free_app_use_date) ? date('Y-m-d', strtotime($request->free_app_use_date)) : null;
            $session_year->start_date = date('Y-m-d', strtotime($request->start_date));
            $session_year->end_date = date('Y-m-d', strtotime($request->end_date));
            $session_year->fee_due_date = date('Y-m-d', strtotime($request->fees_due_date));
            $session_year->fee_due_charges = $request->fees_due_charges;

            // If request asks to set this session as default, enforce rules:
            if ($request->has('default')) {
                $today = date('Y-m-d');
                // Prevent setting an already expired session as default
                if (strtotime($session_year->end_date) < strtotime($today)) {
                    return response()->json([
                        'error' => true,
                        'message' => trans('error_occurred'),
                    ]);
                }
                // Unset previous default and set this one
                SessionYear::where('default', 1)->update(['default' => 0]);
                $session_year->default = 1;
            }

            $session_year->save();

            if (isset($request->installment_data) && ! empty($request->installment_data)) {
                foreach ($request->installment_data as $data) {
                    if ($data['id']) {
                        $installment_update = InstallmentFee::findOrFail($data['id']);
                        $installment_update->name = $data['name'];
                        $installment_update->due_date = date('Y-m-d', strtotime($data['due_date']));
                        $installment_update->due_charges = $data['due_charges'];
                        $installment_update->save();
                    } else {
                        $installment_store = new InstallmentFee;
                        $installment_store->name = $data['name'];
                        $installment_store->due_date = date('Y-m-d', strtotime($data['due_date']));
                        $installment_store->due_charges = $data['due_charges'];
                        $installment_store->session_year_id = $request->id;
                        $installment_store->save();
                    }
                }
            }
            $session_year->include_fee_installments = InstallmentFee::where('session_year_id', $session_year->id)->exists() ? 1 : 0;
            $session_year->save();
            $response = [
                'error' => false,
                'message' => trans('data_update_successfully'),
            ];
        } catch (Throwable $e) {
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
     * @return Response
     */
    public function show()
    {
        if (! Auth::user()->can('session-year-list')) {
            $response = [
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

        $default_session_year = getSettings('session_year')['session_year'];

        $sql = SessionYear::with('fee_installments')->where('id', '!=', 0);
        if (isset($_GET['search']) && ! empty($_GET['search'])) {
            $search = $_GET['search'];
            $sql->where(fn ($query) => $query->where('id', 'LIKE', "%$search%")
                ->orwhere('name', 'LIKE', "%$search%")
                ->orwhere('start_date', 'LIKE', "%$search%")
                ->orwhere('end_date', 'LIKE', "%$search%")
                ->orwhere('default', 'LIKE', "%$search%"));
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
        $sessionYearIds = $res->pluck('id')->toArray();
        $syWithStudentSessions = StudentSessions::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithStudentSubjects = StudentSubject::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithClassTeachers = ClassTeacher::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithSubjectTeachers = SubjectTeacher::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithAssignments = Assignment::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithAttendance = Attendance::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithExams = Exam::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithExamResults = ExamResult::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithOnlineExams = OnlineExam::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithOnlineExamQuestions = OnlineExamQuestion::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithFeesPaid = FeesPaid::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithFeesChoiceable = FeesChoiceable::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithPayments = PaymentTransaction::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithInstallments = InstallmentFee::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithGrades = Grade::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithLeaves = Leave::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithLeaveMasters = LeaveMaster::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithAnnouncements = Announcement::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithEvents = Event::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithHolidays = Holiday::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithNotifications = Notification::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();
        $syWithElectiveGroups = ElectiveSubjectGroup::whereIn('session_year_id', $sessionYearIds)->pluck('session_year_id')->unique()->toArray();

        $canEdit = Auth::user()->can('session-year-edit');
        $canDelete = Auth::user()->can('session-year-delete');

        foreach ($res as $row) {
            $operate = '';
            if ($canEdit) {
                $operate .= '<a class="btn btn-xs btn-gradient-primary btn-rounded btn-icon editdata" data-id='.$row->id.' data-url='.url('session-years').' title="Edit" data-toggle="modal" data-target="#editModal"><i class="fa fa-edit"></i></a>&nbsp;&nbsp;';
                if ($row->id != $default_session_year && $row->end_date >= date('Y-m-d')) {
                    $operate .= '<a class="btn btn-xs btn-gradient-success btn-rounded btn-icon make-current-session-year" data-url="'.url('session-years/'.$row->id.'/make-current').'" data-name="'.e($row->name).'" title="'.e(trans('make_current_session_year')).'"><i class="fa fa-check"></i></a>&nbsp;&nbsp;';
                }
            }
            if ($canDelete && $row->id != $default_session_year) {
                $relatedData = [];
                if (in_array($row->id, $syWithStudentSessions)) {
                    $relatedData[] = 'student sessions';
                }
                if (in_array($row->id, $syWithStudentSubjects) || in_array($row->id, $syWithElectiveGroups)) {
                    $relatedData[] = 'student subjects';
                }
                if (in_array($row->id, $syWithClassTeachers) || in_array($row->id, $syWithSubjectTeachers)) {
                    $relatedData[] = 'class & subject teachers';
                }
                if (in_array($row->id, $syWithAssignments)) {
                    $relatedData[] = 'assignments';
                }
                if (in_array($row->id, $syWithAttendance)) {
                    $relatedData[] = 'attendance';
                }
                if (in_array($row->id, $syWithExams) || in_array($row->id, $syWithExamResults)) {
                    $relatedData[] = 'exams';
                }
                if (in_array($row->id, $syWithOnlineExams) || in_array($row->id, $syWithOnlineExamQuestions)) {
                    $relatedData[] = 'online exams';
                }
                if (in_array($row->id, $syWithFeesPaid) || in_array($row->id, $syWithFeesChoiceable) || in_array($row->id, $syWithPayments) || in_array($row->id, $syWithInstallments)) {
                    $relatedData[] = 'fees & payments';
                }
                if (in_array($row->id, $syWithGrades)) {
                    $relatedData[] = 'grades';
                }
                if (in_array($row->id, $syWithLeaves) || in_array($row->id, $syWithLeaveMasters)) {
                    $relatedData[] = 'leaves';
                }
                if (in_array($row->id, $syWithAnnouncements)) {
                    $relatedData[] = 'announcements';
                }
                if (in_array($row->id, $syWithEvents)) {
                    $relatedData[] = 'events';
                }
                if (in_array($row->id, $syWithHolidays)) {
                    $relatedData[] = 'holidays';
                }
                if (in_array($row->id, $syWithNotifications)) {
                    $relatedData[] = 'notifications';
                }

                $operate .= '
                    <a
                        class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-clear-btn Mdeletedata"
                        data-id="'.$row->id.'"
                        data-url="'.url('session-years', $row->id).'"
                        title="Delete"'
                    .(! empty($relatedData) ? " data-related-old='".json_encode($relatedData)."'" : '').
                    '>
                        <i class="fa fa-trash"></i>
                    </a>';
            }

            $data = getSettings('date_formate');

            $tempRow['id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['name'] = $row->name;
            $tempRow['free_app_use_date'] = isset($row->free_app_use_date) ? date($data['date_formate'], strtotime($row->free_app_use_date)) : null;
            $tempRow['default'] = $row->default;
            $tempRow['start_date'] = date($data['date_formate'], strtotime($row->start_date));
            $tempRow['end_date'] = date($data['date_formate'], strtotime($row->end_date));
            $tempRow['fees_due_date'] = date($data['date_formate'], strtotime($row->fee_due_date));
            // Raw ISO dates for reliable JS datepicker boundary parsing (independent of display format)
            $tempRow['start_date_raw'] = date('Y-m-d', strtotime($row->start_date));
            $tempRow['end_date_raw'] = date('Y-m-d', strtotime($row->end_date));
            $tempRow['fees_due_charges'] = $row->fee_due_charges;
            $tempRow['include_fee_installments'] = $row->include_fee_installments;
            $tempRow['fee_installments'] = $row->fee_installments;
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
     * @return Response
     */
    public function edit($id)
    {
        ResponseService::noPermissionThenSendJson('session-year-edit');
        $session_year = SessionYear::find($id);

        return response($session_year);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        if (! Auth::user()->can('session-year-delete')) {
            return response()->json([
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $year = SessionYear::findOrFail($id);
            // The model refuses this too; answer with the readable message instead of the exception's.
            if ((int) $year->default === 1) {
                return response()->json([
                    'error' => true,
                    'message' => trans('default_session_year_cannot_delete'),
                ]);
            }
            $year->delete();

            return response()->json([
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function deleteSpecificData(
        Request $request,
        int $session_year,
        SessionYearService $service
    ) {
        try {

            if (! Auth::user()->can('session-year-delete')) {
                return response()->json([
                    'message' => trans('no_permission_message'),
                ], 403);
            }

            $validated = $request->validate([
                'clear_data' => ['required', 'array'],
                'clear_data.*' => [
                    'required',
                    'string',
                    'in:class_subject,class_teachers,fees_transactions,notifications,timetable,attendance,assignments,exams,grades,announcements,holidays,events,leaves,allowed_leave_days',
                ],
            ]);

            $service->clearSpecificData(
                $session_year,
                $validated['clear_data']
            );

            return response()->json([
                'success' => true,
                'message' => trans('data_delete_successfully'),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'error' => true,
                'message' => $e->validator->errors()->first(),
            ], 422);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ], 500);
        }
    }

    public function deleteInstallmentData($id)
    {
        if (! Auth::user()->can('session-year-delete')) {
            return response()->json([
                'message' => trans('no_permission_message'),
            ]);
        }

        try {
            $installment = InstallmentFee::findOrFail($id);

            /**
             * Cascade deletion handled in model
             */
            $installment->delete();
            if (! InstallmentFee::where('session_year_id', $installment->session_year_id)->exists()) {
                SessionYear::where('id', $installment->session_year_id)->update(['include_fee_installments' => 0]);
            }

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

    public function semesters($id)
    {
        $semesters = Semester::where('session_year_id', $id)
            ->select('id', 'name', 'start_date', 'end_date')
            ->orderBy('start_date', 'ASC')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $semesters,
        ]);
    }

    public function details($id)
    {
        $session = SessionYear::select('id', 'name', 'start_date')
            ->find($id);

        if (! $session) {
            return response()->json([
                'status' => false,
                'message' => 'Session not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $session,
        ]);
    }

    public function setSessionYearToSessionStorage(Request $request)
    {
        $request->validate([
            'session_year_id' => 'required|exists:session_years,id',
        ]);

        session(['session_year' => $request->session_year_id]);

        // The read-only banner's "back to the current year" button is a plain form.
        if (! $request->expectsJson() && ! $request->ajax()) {
            return redirect()->back();
        }

        return response()->json(['success' => true]);
    }
}
