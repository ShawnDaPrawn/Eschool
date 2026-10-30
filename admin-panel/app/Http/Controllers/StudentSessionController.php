<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Services\People\Leavers\LeaverState;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class StudentSessionController extends Controller
{
    public function index()
    {
        if (! Auth::user()->can('promote-student-list')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $class_sections = ClassSection::with('class', 'section', 'streams')->get();
        $classes = ClassSchool::get();
        $session_years = SessionYear::orderBy('id', 'ASC')->get();
        // Students are promoted out of the current year, so the list opens on it.
        $current_session_year_id = (int) (getSettings('session_year')['session_year'] ?? 0);

        return view('promote_student.index', compact('class_sections', 'session_years', 'classes', 'current_session_year_id'));
    }

    public function store(Request $request)
    {
        if (! Auth::user()->can('promote-student-edit')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
            ]);
        }

        $validator = Validator::make($request->all(), [
            'class_section_id' => 'required',
            'student_id' => 'required',
            'previous_session_id_for_student' => 'required_if:status,!=,0|different:session_year_id',
        ], [
            'previous_session_id_for_student.different' => trans('promote_session_must_differ'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $previous_session_id_for_student = $request->previous_session_id_for_student;

            $newSessionYear = $request->session_year_id;
            $newClass = (int) $request->new_class_section_id;
            // A promotion into next year is recorded now and takes effect when that year
            // becomes current (SessionYearService::makeCurrent); until then the student
            // keeps their class and their account.
            $takesEffectNow = (int) $newSessionYear === (int) (getSettings('session_year')['session_year'] ?? 0);

            foreach ($request->student_id as $id) {

                $status = $request->input("status$id");   // continue = 1, leave = 0
                $result = $request->input("result$id");   // pass = 1, fail = 0

                $student = Students::find($id);
                $oldClass = $student->class_section_id;

                // as student promoted to new class we will consider it as old student
                $student->is_new_admission = 0;

                // decide promoted or repeated
                $promoted = (! empty($newClass)) && ($newClass != $oldClass);

                // fetch existing session OR prepare new
                $session = StudentSessions::where([
                    'student_id' => $id,
                    'previous_session_year_id' => $previous_session_id_for_student,
                ])->first();

                if (! $session) {
                    $session = new StudentSessions;
                    $session->student_id = $id;
                }
                $wasLeaving = $session->exists && (int) $session->status === 0;
                $session->previous_session_year_id = $previous_session_id_for_student;
                $session->session_year_id = $newSessionYear;

                // set class based on promote/repeat
                if ($promoted) {
                    $session->class_section_id = $newClass;
                } else {
                    $session->class_section_id = $oldClass;
                }

                // set status and result always
                $session->result = $result;
                $session->status = $status;

                // if promoted → update student main table
                if ($promoted && $takesEffectNow) {
                    $student->class_section_id = $newClass;
                }
                $student->save();

                // Leaving the current year: a leaver, read-only until 31 March (Action Plan 1.21).
                // Changing a leave back to continue makes them a current learner again.
                if ($status == 0) {
                    $session->class_section_id = null;
                } elseif ($wasLeaving) {
                    User::whereKey($student->user_id)->update(['status' => 1]);
                    app(LeaverState::class)->cancel($student);
                }
                $session->save();
                if ($status == 0 && $takesEffectNow) {
                    app(LeaverState::class)->begin([$student->id], SessionYear::query()->findOrFail($newSessionYear), midYear: true);
                }
            }

            return response()->json([
                'error' => false,
                'message' => trans('data_update_successfully'),
            ]);
        } catch (Exception $e) {
            report($e);

            return response()->json([
                'error' => true,
                'message' => trans('error_occurred'),
            ]);
        }
    }

    public function getPromoteData(Request $request)
    {
        if (! Auth::user()->can('promote-student-list')) {
            return response()->json(['message' => trans('no_permission_message')]);
        }
        $response = StudentSessions::where(['class_section_id' => $request->class_section_id])->get();

        return response()->json($response);
    }

    public function show(Request $request)
    {
        if (! Auth::user()->can('promote-student-list')) {
            return response()->json(['message' => trans('no_permission_message')]);
        }

        $offset = $request->offset ?? 0;
        $limit = $request->limit ?? 10;
        $sort = $request->sort ?? 'id';
        $order = $request->order ?? 'ASC';

        $classId = $request->class_id;
        $sectionId = $request->section_id;
        $resultStatus = $request->status;
        $current_session_id = $request->session_year_id;

        if (! $classId || ! $sectionId) {
            return ['rows' => [], 'total' => 0];
        }

        $classSection = ClassSection::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->first();

        if (! $classSection) {
            return ['rows' => [], 'total' => 0];
        }

        $class_section_id = $classSection->id;

        $current_session_year = SessionYear::find($current_session_id);

        /*
    |--------------------------------------------------------------------------
    | Base query (StudentSessions = source of truth)
    |--------------------------------------------------------------------------
    */
        $sql = StudentSessions::where('class_section_id', $class_section_id)
            ->where('session_year_id', $current_session_id)
            ->whereHas('student', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->with([
                'student' => function ($q) {
                    $q->whereNull('deleted_at');
                },
                'student.user' => function ($q) {
                    $q->whereNull('deleted_at');
                },
                'student.exam_result' => function ($q) use ($current_session_id, $class_section_id, $classId) {
                    $q->where('session_year_id', $current_session_id)
                        ->where('class_section_id', $class_section_id)
                        ->with([
                            'exam_timetable' => function ($t) use ($classId) {
                                $t->where('class_id', $classId)
                                    ->whereNull('deleted_at');
                            },
                        ]);
                },
                'student.studentSessions' => function ($q) use ($current_session_id) {
                    $q->where('previous_session_year_id', $current_session_id);
                },
            ]);

        /*
    |--------------------------------------------------------------------------
    | Search (via student + user)
    |--------------------------------------------------------------------------
    */
        if (! empty($request->search)) {
            $search = $request->search;

            $sql->whereHas('student', function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orWhere('admission_no', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%")
                            ->orWhere('mobile', 'LIKE', "%$search%");
                    });
            });
        }

        /*
    |--------------------------------------------------------------------------
    | Count before pagination
    |--------------------------------------------------------------------------
    */
        $total = $sql->count();

        /*
    |--------------------------------------------------------------------------
    | Fetch paginated data
    |--------------------------------------------------------------------------
    */
        $sessions = $sql->orderBy($sort, $order)
            ->skip($offset)
            ->take($limit)
            ->get()
            ->unique('student_id')
            ->values();

        /*
    |--------------------------------------------------------------------------
    | ClassSection lookup
    |--------------------------------------------------------------------------
    */
        $classSectionMap = ClassSection::with(['class', 'section'])
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($sessions as $session) {

            $student = $session->student;

            // promotion session (if any)
            $promotionSession = $student?->studentSessions->first();

            /*
        |--------------------------------------------------------------------------
        | Result filter
        |--------------------------------------------------------------------------
        */
            if ($resultStatus !== '') {
                $sessionResult = $promotionSession->result ?? null;

                if ($resultStatus === 'pass' && $sessionResult != 1) {
                    continue;
                }
                if ($resultStatus === 'fail' && $sessionResult !== 0) {
                    continue;
                }
                if ($resultStatus === 'pending' && $sessionResult !== null) {
                    continue;
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Latest exam result
        |--------------------------------------------------------------------------
        */
            $studentResult = $student?->exam_result
                ->sortByDesc(fn ($r) => $r->examTimetable->date ?? null)
                ->first();

            /*
        |--------------------------------------------------------------------------
        | promotedTo
        |--------------------------------------------------------------------------
        */
            $promotedTo = __('pending');

            if ($promotionSession) {
                if ($promotionSession->status == 0) {
                    $promotedTo = '-';
                } else {
                    $cid = $promotionSession->class_section_id;
                    if ($cid && isset($classSectionMap[$cid])) {
                        $promotedTo = $classSectionMap[$cid]->name;
                    }
                }
            }

            /*
        |--------------------------------------------------------------------------
        | Update button
        |--------------------------------------------------------------------------
        */
            $updateBtn = '<a href="javascript:void(0)"
                class="btn btn-xs border border-dark rounded btn-secondary bg-transparent text-dark promote-student"
                data-class-id="'.$class_section_id.'"
                data-student-id="'.$student?->id.'"
                data-toggle="modal" data-target="#promoteModal">
                '.__('update').'
            </a>';

            /*
        |--------------------------------------------------------------------------
        | Row
        |--------------------------------------------------------------------------
        */
            $rows[] = [
                'id' => $student?->id,
                'student_id' => "<input type='text' name='student_id[]' class='form-control' readonly value='{$student?->id}'>",
                'roll_number' => $student?->roll_number,
                'admission_no' => $student?->admission_no,
                'name' => $student?->user->first_name.' '.$student?->user->last_name,
                'studentResult' => $studentResult,
                'continueStatus' => $promotionSession->status ?? null,
                'resultStatus' => $promotionSession->result ?? null,
                'promotedTo' => $promotedTo,
                'updateBtn' => $updateBtn,
            ];
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
            'current_session_year' => $current_session_year,
        ]);
    }
}
