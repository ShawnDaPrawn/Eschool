<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\InstallmentFee;
use App\Models\Mediums;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\LegacyAmount;
use App\Services\Platform\Ledger\Money;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * Admin panel: who has paid what: the paid list, receipts, and clearing a payment.
 */
class PaidController extends Controller
{
    public function feesPaidListIndex()
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $classes = ClassSchool::orderByRaw('CONVERT(name, SIGNED) asc')->with('medium', 'sections', 'streams')->get();
        $session_year_all = SessionYear::select('id', 'name', 'default')->get();
        $mediums = Mediums::orderBy('id', 'ASC')->get();

        return response(view('fees.fees_paid', compact('classes', 'mediums', 'session_year_all')));
    }

    public function feesPaidList()
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $offset = 0;
        $limit = 10;
        $sort = 'id';
        $order = 'DESC';

        if (request()->has('offset')) {
            $offset = request()->query('offset');
        }
        if (request()->has('limit')) {
            $limit = request()->query('limit');
        }
        if (request()->has('sort')) {
            $sort = request()->query('sort');
        }
        if (request()->has('order')) {
            $order = request()->query('order');
        }

        $session_year_id = null;
        if (request()->has('session_year_id') && request()->query('session_year_id') !== '') {
            $session_year_id = request()->query('session_year_id');
        }

        // Base query: use StudentSessions so ALL enrolled students appear,
        // regardless of whether they have paid fees yet.
        // This allows admin to record first-time payments for any enrolled student.
        $sessionQuery = StudentSessions::with([
            'student.user:id,first_name,last_name',
            'student.father',
            'student.mother',
            'student.guardian',
            'class_section.class.medium',
            'class_section.class.streams',
            'class_section.section',
        ])->where('session_year_id', $session_year_id);

        // Filter by class
        if (request()->has('class_id') && ! empty(request()->query('class_id'))) {
            $class_id = request()->query('class_id');
            $class_section_ids = ClassSection::where('class_id', $class_id)->pluck('id');
            $sessionQuery->whereIn('class_section_id', $class_section_ids);
        }

        // Filter by payment mode. The mode is recorded on payment_transaction
        // (keyed by student/class/session); fees_paid.mode and
        // fees_paid.payment_transaction_id are left unset, so resolve the matching
        // students through their completed payment transactions instead.
        if (request()->has('mode') && in_array(request()->query('mode'), ['0', '1', '2'], true)) {
            $mode = request()->query('mode');
            $paidStudentIds = PaymentTransaction::where('mode', $mode)
                ->where('payment_status', 1)
                ->when($session_year_id, fn ($q) => $q->where('session_year_id', $session_year_id))
                ->pluck('student_id');
            $sessionQuery->whereIn('student_id', $paidStudentIds);
        }

        // Search by student name or id
        if (request()->has('search') && ! empty(request()->query('search'))) {
            $search = request()->query('search');
            $sessionQuery->whereHas('student', function ($q) use ($search) {
                $q->where('id', 'LIKE', "%$search%")
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('first_name', 'LIKE', "%$search%")
                            ->orWhere('last_name', 'LIKE', "%$search%");
                    });
            });
        }

        // Only show students whose class has fees (compulsory or optional) set up in
        // fees_classes; each row offers both kinds of payment.
        $sessionQuery->whereHas('class_section.class.fees_class', function ($q) use ($session_year_id) {
            $q->where('session_year_id', $session_year_id);
        });

        $total = $sessionQuery->count();
        $sessionQuery->orderBy($sort, $order)->skip($offset)->take($limit);
        $sessionResults = $sessionQuery->get();

        // Build a Students-like result set from StudentSessions for the rest of the loop
        // We load fees_paid per student scoped to the session year for display
        $studentIds = $sessionResults->pluck('student_id');
        $feesPaidMap = FeesPaid::whereIn('student_id', $studentIds)
            ->when($session_year_id, fn ($q) => $q->where('session_year_id', $session_year_id))
            ->with('class', 'session_year', 'payment_transaction')
            ->get()
            ->keyBy('student_id');

        // Re-map session results into a Students model structure expected by the loop below
        $res = $sessionResults->map(function ($ss) use ($feesPaidMap) {
            $student = $ss->student;
            if (! $student) {
                return null;
            }
            // Inject the session-scoped class_section onto the student object
            $student->setRelation('class_section', $ss->class_section);
            // Inject session-scoped fees_paid (may be null = not paid yet)
            $student->setRelation('fees_paid', $feesPaidMap->get($student->id));

            return $student;
        })->filter()->values();
        $bulkData = [];
        $bulkData['total'] = $total;
        $rows = [];
        $tempRow = [];
        $no = 1;
        foreach ($res as $row) {
            $operate = '';
            // Resolve session year context (use a local var to avoid corrupting the outer $session_year_id)
            if (! empty($session_year_id)) {
                $effective_session_year_id = $session_year_id;
                $session_year = SessionYear::where('id', $session_year_id)->first();

            } else {
                // No filter selected: derive session year from fees_paid record if available
                $session_year = $row->fees_paid->session_year ?? null;
                $effective_session_year_id = $session_year?->id;
            }

            $due_date = $session_year->fee_due_date ?? null;
            $is_installaments_enabled = $session_year->include_fee_installments ?? 0;

            $current_date = Carbon::now()->format('d-m-Y');
            $due_charges = 0;
            $base_amount_with_due_charges = 0;

            $payment_transaction = null;

            if ($row->fees_paid) {
                $payment_transaction = PaymentTransaction::where(['student_id' => $row->id, 'class_id' => $row->class_section->class_id, 'session_year_id' => $effective_session_year_id])->latest()->first();
            }

            // Base Amount
            $base_cents = FeesClass::where(['class_id' => $row->class_section->class_id, 'choiceable' => 0, 'session_year_id' => $session_year_id])
                ->selectRaw('SUM('.CentsColumns::sql('amount', 'amount_cents').') as base_amount')->first();
            $base_cents = $base_cents['base_amount'];
            $base_amount = LegacyAmount::fromCents($base_cents);

            // if due charges is applicable
            if ($due_date && strtotime($current_date) > strtotime($due_date)) {
                $due_charges = $session_year->fee_due_charges;
                $base = Money::ofCents((int) $base_cents);
                $base_amount_with_due_charges = LegacyAmount::fromCents($base->plus($base->percent((int) $due_charges))->cents);
            }

            // Get the fees data
            $compulsory_fees = FeesClass::where([
                'class_id' => $row->class_section->class_id,
                'choiceable' => 0,
                'session_year_id' => $session_year_id,
            ])->with('fees_type')->get();

            $choiceable_fees = FeesClass::where([
                'class_id' => $row->class_section->class_id,
                'choiceable' => 1,
                'session_year_id' => $session_year_id,
            ])->with('fees_type')->get();

            $installment_data = InstallmentFee::where('session_year_id', $effective_session_year_id)->get();

            // Get Paid Fees

            $operate = '';
            // check that fees paid is not empty
            if (isset($row->fees_paid) && ! empty($row->fees_paid)) {
                // checks that fees paid's session year matches the current session year then allow to modify the fees payments or else show only clear and pdf option
                if ($row->fees_paid->session_year_id == $effective_session_year_id) {
                    $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu dropdown-menu-left">';
                    $operate .= '<a href="#"class="compulsory-data dropdown-item" data-id='.$row->id.' title="'.trans('compulsory').' '.trans('fees').'" data-toggle="modal" data-target="#compulsoryModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('compulsory').' '.trans('fees').'</a><div class="dropdown-divider"></div>';
                    $operate .= '<a href="#" class="optional-data dropdown-item" data-id='.$row->id.' title="'.trans('optional').' '.trans('fees').'" data-toggle="modal" data-target="#optionalModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('optional').' '.trans('fees').'</a>';
                    $operate .= '</div></div>&nbsp;&nbsp;';
                    $operate .= '<a href='.route('fees.paid.clear.data', $row->fees_paid->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" title="'.trans('clear').'" data-id='.$row->fees_paid->id.'><i class="fa fa-remove"></i></a>&nbsp;&nbsp;';
                    $operate .= '<a href='.route('fees.paid.receipt.pdf', $row->fees_paid->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id='.$row->fees_paid->id.' title="'.trans('generate_pdf').' '.trans('fees').'"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                } else {
                    $operate .= '<a href='.route('fees.paid.clear.data', $row->fees_paid->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" title="'.trans('clear').'" data-id='.$row->fees_paid->id.'><i class="fa fa-remove"></i></a>&nbsp;&nbsp;';
                    $operate .= '<a href='.route('fees.paid.receipt.pdf', $row->fees_paid->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id='.$row->fees_paid->id.' title="'.trans('generate_pdf').' '.trans('fees').'"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
                }
            } else {
                $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu dropdown-menu-left">';
                $operate .= '<a href="#"class="compulsory-data dropdown-item" data-id='.$row->id.' title="'.trans('compulsory').' '.trans('fees').'" data-toggle="modal" data-target="#compulsoryModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('compulsory').' '.trans('fees').'</a><div class="dropdown-divider"></div>';
                $operate .= '<a href="#" class="optional-data dropdown-item" data-id='.$row->id.' title="'.trans('optional').' '.trans('fees').'" data-toggle="modal" data-target="#optionalModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('optional').' '.trans('fees').'</a>';
                $operate .= '</div></div>&nbsp;&nbsp;';
            }

            $tempRow['id'] = null;
            $tempRow['student_id'] = $row->id;
            $tempRow['no'] = $no++;
            $tempRow['father_id'] = $row->father_id;
            $tempRow['mother_id'] = $row->mother_id;
            $tempRow['student_name'] = $row->user->first_name.' '.$row->user->last_name;
            $tempRow['class_id'] = $row->class_section->class_id;
            $tempRow['class_name'] = $row->class_section->class->name.'-'.$row->class_section->section->name.' '.$row->class_section->class->medium->name;
            $tempRow['stream_name'] = $row->class_section->class->streams->name ?? '-';
            $tempRow['compulsory_fees'] = count($compulsory_fees) ? $compulsory_fees : null;
            $tempRow['is_installaments_enabled'] = $is_installaments_enabled;

            $paid_installment_data = PaidInstallmentFee::where(['student_id' => $row->id, 'session_year_id' => $effective_session_year_id, 'status' => 1])->first();
            $tempRow['is_installment_paid'] = $paid_installment_data ? 1 : 0;

            if (isset($installment_data) && ! empty($installment_data)) {
                $tempRow['installment_data'] = [];
                foreach ($installment_data as $data) {
                    // Paid Installment Data
                    $paid_installment = PaidInstallmentFee::where(['student_id' => $row->id, 'installment_fee_id' => $data->id, 'status' => 1])->first();

                    if (strtotime($current_date) >= strtotime($data->due_date)) {
                        $tempRow['installment_data'][] = [
                            'id' => $data->id,
                            'name' => $data->name,
                            'due_date' => $data->due_date,
                            'due_charges' => $data->due_charges,
                            'due_charges_applicable' => 1,
                            'paid' => $paid_installment->status ?? 0,
                            'amount' => $paid_installment->amount ?? '',
                            'paid_id' => $paid_installment ? $paid_installment->id : '',
                            'paid_on' => $paid_installment ? date('d-m-Y', strtotime($paid_installment->date)) : '',
                        ];
                    } else {
                        $tempRow['installment_data'][] = [
                            'id' => $data->id,
                            'name' => $data->name,
                            'due_date' => $data->due_date,
                            'due_charges' => $data->due_charges,
                            'due_charges_applicable' => 0,
                            'paid' => $paid_installment->status ?? 0,
                            'amount' => $paid_installment->amount ?? '',
                            'paid_id' => $paid_installment ? $paid_installment->id : '',
                            'paid_on' => $paid_installment ? date('d-m-Y', strtotime($paid_installment->date ?? '')) : '',
                        ];
                    }
                }
            }
            if (isset($choiceable_fees) && ! empty($choiceable_fees)) {
                $tempRow['choiceable_fees'] = [];
                $paid_choiceable_fees_query = FeesChoiceable::where(['class_id' => $row->class_section->class_id, 'student_id' => $row->id, 'session_year_id' => $effective_session_year_id, 'status' => 1]);
                foreach ($choiceable_fees as $data) {
                    // Clone the Query To Avoid Extra Addition of Where Fees Type ID
                    $paid_choiceable_data = (clone $paid_choiceable_fees_query)->where('fees_type_id', $data->fees_type_id);
                    if ($paid_choiceable_data->count()) {
                        $tempRow['choiceable_fees'][] = [
                            'id' => $data->id,
                            'name' => $data->fees_type->name,
                            'class_id' => $data->class_id,
                            'fees_type_id' => $data->fees_type_id,
                            'choiceable' => $data->choiceable,
                            'amount' => $data->amount,
                            'is_paid' => 1,
                            'paid_id' => $paid_choiceable_data->first()->id,
                            'date' => $paid_choiceable_data->first()->date,
                        ];
                    } else {
                        $tempRow['choiceable_fees'][] = [
                            'id' => $data->id,
                            'name' => $data->fees_type->name,
                            'class_id' => $data->class_id,
                            'fees_type_id' => $data->fees_type_id,
                            'choiceable' => $data->choiceable,
                            'amount' => $data->amount,
                            'is_paid' => 0,
                        ];
                    }
                }
            }
            $tempRow['base_amount'] = $base_amount;
            $tempRow['base_amount_with_due_charges'] = $base_amount_with_due_charges;
            $tempRow['due_charges'] = [
                'date' => $due_date ? date('d-m-Y', strtotime($due_date)) : null,
                'charges' => $charges ?? null,
            ];
            $tempRow['fees_status'] = $row->fees_paid->is_fully_paid ?? null;
            // "Total Fees" shows the configured fee amount from fees_classes (the total a student must pay),
            // not what has been paid so far. This removes the empty column for unpaid students.
            $tempRow['total_fees'] = $base_amount ?? 0;
            // Separately expose what has actually been paid for reference
            $tempRow['fees_paid_amount'] = $row->fees_paid->total_amount ?? null;
            $tempRow['current_date'] = $current_date;
            $tempRow['date'] = $row->fees_paid != null && $row->fees_paid->date != null ? date('d-m-Y', strtotime($row->fees_paid->date)) : '-';
            $tempRow['session_year_name'] = $row->fees_paid->session_year->name ?? null;
            $tempRow['mode'] = $payment_transaction->mode ?? null;
            $tempRow['type_of_fee'] = $payment_transaction->type_of_fee ?? null;
            $tempRow['cheque_no'] = $payment_transaction->cheque_no ?? null;
            $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
            $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
            $tempRow['operate'] = $operate;
            $rows[] = $tempRow;
        }
        $bulkData['rows'] = $rows;

        return response()->json($bulkData);
    }

    // public function feesPaidList()
    // {
    //     if (! Auth::user()->can('fees-paid')) {
    //         $response = [
    //             'message' => trans('no_permission_message'),
    //         ];

    //         return redirect(route('home'))->withErrors($response);
    //     }
    //     $offset = 0;
    //     $limit = 10;
    //     $sort = 'id';
    //     $order = 'DESC';

    //     if (request()->has('offset')) {
    //         $offset = request()->query('offset');
    //     }
    //     if (request()->has('limit')) {
    //         $limit = request()->query('limit');
    //     }
    //     if (request()->has('sort')) {
    //         $sort = request()->query('sort');
    //     }
    //     if (request()->has('order')) {
    //         $order = request()->query('order');
    //     }

    //     $session_year_id = null;
    //     if (request()->has('session_year_id') && request()->query('session_year_id') !== '') {
    //         $session_year_id = request()->query('session_year_id');
    //     }

    //     // Base query: use StudentSessions so ALL enrolled students appear,
    //     // regardless of whether they have paid fees yet.
    //     // This allows admin to record first-time payments for any enrolled student.
    //     $sessionQuery = StudentSessions::with([
    //         'student.user:id,first_name,last_name',
    //         'student.father',
    //         'student.mother',
    //         'student.guardian',
    //         'class_section.class.medium',
    //         'class_section.class.streams',
    //         'class_section.section',
    //     ])->where('session_year_id', $session_year_id);

    //     // Filter by class
    //     if (request()->has('class_id') && ! empty(request()->query('class_id'))) {
    //         $class_id = request()->query('class_id');
    //         $class_section_ids = ClassSection::where('class_id', $class_id)->pluck('id');
    //         $sessionQuery->whereIn('class_section_id', $class_section_ids);
    //     }

    //     // Filter by payment mode. The mode is recorded on payment_transaction
    //     // (keyed by student/class/session); fees_paid.mode and
    //     // fees_paid.payment_transaction_id are left unset, so resolve the matching
    //     // students through their completed payment transactions instead.
    //     if (request()->has('mode') && in_array(request()->query('mode'), ['0', '1', '2'], true)) {
    //         $mode = request()->query('mode');
    //         $paidStudentIds = PaymentTransaction::where('mode', $mode)
    //             ->where('payment_status', 1)
    //             ->when($session_year_id, fn ($q) => $q->where('session_year_id', $session_year_id))
    //             ->pluck('student_id');
    //         $sessionQuery->whereIn('student_id', $paidStudentIds);
    //     }

    //     // Search by student name or id
    //     if (request()->has('search') && ! empty(request()->query('search'))) {
    //         $search = request()->query('search');
    //         $sessionQuery->whereHas('student', function ($q) use ($search) {
    //             $q->where('id', 'LIKE', "%$search%")
    //                 ->orWhereHas('user', function ($q) use ($search) {
    //                     $q->where('first_name', 'LIKE', "%$search%")
    //                         ->orWhere('last_name', 'LIKE', "%$search%");
    //                 });
    //         });
    //     }

    //     // Only show students whose class has compulsory fees configured in fees_classes.
    //     // If a class has no fees set up, those students should not appear in this list.
    //     $sessionQuery->whereHas('class_section.class.fees_class', function ($q) use ($session_year_id) {
    //         $q->where('choiceable', 0)
    //             ->where('session_year_id', $session_year_id);
    //     });

    //     $total = $sessionQuery->count();
    //     $sessionQuery->orderBy($sort, $order)->skip($offset)->take($limit);
    //     $sessionResults = $sessionQuery->get();

    //     // Build a Students-like result set from StudentSessions for the rest of the loop
    //     // We load fees_paid per student scoped to the session year for display
    //     $studentIds = $sessionResults->pluck('student_id');
    //     $feesPaidMap = FeesPaid::whereIn('student_id', $studentIds)
    //         ->when($session_year_id, fn ($q) => $q->where('session_year_id', $session_year_id))
    //         ->with('class', 'session_year', 'payment_transaction')
    //         ->get()
    //         ->keyBy('student_id');

    //     // Re-map session results into a Students model structure expected by the loop below
    //     $res = $sessionResults->map(function ($ss) use ($feesPaidMap) {
    //         $student = $ss->student;
    //         if (! $student) {
    //             return null;
    //         }
    //         // Inject the session-scoped class_section onto the student object
    //         $student->setRelation('class_section', $ss->class_section);
    //         // Inject session-scoped fees_paid (may be null = not paid yet)
    //         $student->setRelation('fees_paid', $feesPaidMap->get($student->id));

    //         return $student;
    //     })->filter()->values();
    //     $bulkData = [];
    //     $bulkData['total'] = $total;
    //     $rows = [];
    //     $tempRow = [];
    //     $no = 1;

    //     if (! empty($cachedsessionyear)) {

    //         $cachedsessionyear = sessionYear::getCachedById($session_year_id);
    //     }
    //     $baseAmount=FeesClass::where('session_year_id',$session_year_id)
    //     ->where('choiceable',0)
    //     ->selectRaw('class_id,sum(amount) as base_amount')
    //     ->groupby('class_id')
    //     ->pluck('base_amount','class_id');

    //     $compulsoryFees= FeesClass ::with('fees_type')
    //     ->where('session_year_id',$session_year_id)
    //     ->where('choiceable',0)
    //     ->get()
    //     ->groupBy('class_id');

    //     $choicableFees = FeesClass :: with('fees_type')
    //     ->where('session_year_id',$session_year_id)
    //     ->where('choiceable',0)
    //     ->get()
    //     ->groupBy('class_id');
    //     $installments = InstallmentFee::where('session_year_id', $session_year_id)->get();

    //         if (! empty($session_year_id)) {
    //             $effective_session_year_id = $session_year_id;
    //             $session_year = SessionYear::where('id', $session_year_id)->first();
    //     }

    //     foreach ($res as $row) {
    //         $operate = '';
    //         if (empty($session_year_id)) {
    //             // No filter selected: derive session year from fees_paid record if available
    //             $session_year = $row->fees_paid->session_year ?? null;
    //             $effective_session_year_id = $session_year?->id;
    //         }

    //         $due_date = $session_year->fee_due_date ?? null;
    //         $is_installaments_enabled = $session_year->include_fee_installments ?? 0;

    //         $current_date = Carbon::now()->format('d-m-Y');
    //         $due_charges = 0;
    //         $base_amount_with_due_charges = 0;

    //         $payment_transaction = null;

    //         if ($row->fees_paid) {
    //             $payment_transaction = PaymentTransaction::where(['student_id' => $row->id, 'class_id' => $row->class_section->class_id, 'session_year_id' => $effective_session_year_id])->latest()->first();
    //         }

    //         // Base Amount
    //         // $base_amount = FeesClass::where(['class_id' => $row->class_section->class_id, 'choiceable' => 0, 'session_year_id' => $session_year_id])
    //         //     ->selectRaw('SUM(amount) as base_amount')->first();
    //         $base_amount=$baseAmount[$row->class_section->class_id] ?? 0;
    //         // $base_amount = $base_amount['base_amount'];

    //         // if due charges is applicable
    //         if ($due_date && strtotime($current_date) > strtotime($due_date)) {
    //             $due_charges = $session_year->fee_due_charges;
    //             $charges = (($due_charges) * ($base_amount) / 100);
    //             $base_amount_with_due_charges = $base_amount + $charges;
    //         }

    //         // Get the fees data
    //         // $compulsory_fees = FeesClass::where([
    //         //     'class_id' => $row->class_section->class_id,
    //         //     'choiceable' => 0,
    //         //     'session_year_id' => $session_year_id,
    //         // ])->with('fees_type')->get();

    //         $compulsory_fees = $compulsoryFees[$row->class_section->class_id] ?? collect();

    //         // $choiceable_fees = FeesClass::where([
    //         //     'class_id' => $row->class_section->class_id,
    //         //     'choiceable' => 1,
    //         //     'session_year_id' => $session_year_id,
    //         // ])->with('fees_type')->get();

    //         $choiceable_fees = $choicableFees[$row->class_section->class_id] ?? collect();

    //         // $installment_data = InstallmentFee::where('session_year_id', $effective_session_year_id)->get();
    //         $installment_data=$installments;

    //         // Get Paid Fees

    //         $operate = '';
    //         // check that fees paid is not empty
    //         if (isset($row->fees_paid) && ! empty($row->fees_paid)) {
    //             // checks that fees paid's session year matches the current session year then allow to modify the fees payments or else show only clear and pdf option
    //             if ($row->fees_paid->session_year_id == $effective_session_year_id) {
    //                 $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu dropdown-menu-left">';
    //                 $operate .= '<a href="#"class="compulsory-data dropdown-item" data-id='.$row->id.' title="'.trans('compulsory').' '.trans('fees').'" data-toggle="modal" data-target="#compulsoryModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('compulsory').' '.trans('fees').'</a><div class="dropdown-divider"></div>';
    //                 $operate .= '<a href="#" class="optional-data dropdown-item" data-id='.$row->id.' title="'.trans('optional').' '.trans('fees').'" data-toggle="modal" data-target="#optionalModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('optional').' '.trans('fees').'</a>';
    //                 $operate .= '</div></div>&nbsp;&nbsp;';
    //                 $operate .= '<a href='.route('fees.paid.clear.data', $row->fees_paid->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" title="'.trans('clear').'" data-id='.$row->fees_paid->id.'><i class="fa fa-remove"></i></a>&nbsp;&nbsp;';
    //                 $operate .= '<a href='.route('fees.paid.receipt.pdf', $row->fees_paid->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id='.$row->fees_paid->id.' title="'.trans('generate_pdf').' '.trans('fees').'"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
    //             } else {
    //                 $operate .= '<a href='.route('fees.paid.clear.data', $row->fees_paid->id).' class="btn btn-xs btn-gradient-danger btn-rounded btn-icon delete-form" title="'.trans('clear').'" data-id='.$row->fees_paid->id.'><i class="fa fa-remove"></i></a>&nbsp;&nbsp;';
    //                 $operate .= '<a href='.route('fees.paid.receipt.pdf', $row->fees_paid->id).' class="btn btn-xs btn-gradient-info btn-rounded btn-icon generate-paid-fees-pdf" target="_blank" data-id='.$row->fees_paid->id.' title="'.trans('generate_pdf').' '.trans('fees').'"><i class="fa fa-file-pdf-o"></i></a>&nbsp;&nbsp;';
    //             }
    //         } else {
    //             $operate = '<div class="dropdown"><button class="btn btn-xs btn-gradient-success btn-rounded btn-icon dropdown-toggle" type="button" data-toggle="dropdown"><i class="fa fa-dollar"></i></button><div class="dropdown-menu dropdown-menu-left">';
    //             $operate .= '<a href="#"class="compulsory-data dropdown-item" data-id='.$row->id.' title="'.trans('compulsory').' '.trans('fees').'" data-toggle="modal" data-target="#compulsoryModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('compulsory').' '.trans('fees').'</a><div class="dropdown-divider"></div>';
    //             $operate .= '<a href="#" class="optional-data dropdown-item" data-id='.$row->id.' title="'.trans('optional').' '.trans('fees').'" data-toggle="modal" data-target="#optionalModal"><i class="fa fa-dollar text-success mr-2"></i>'.trans('optional').' '.trans('fees').'</a>';
    //             $operate .= '</div></div>&nbsp;&nbsp;';
    //         }

    //         $tempRow['id'] = null;
    //         $tempRow['student_id'] = $row->id;
    //         $tempRow['no'] = $no++;
    //         $tempRow['father_id'] = $row->father_id;
    //         $tempRow['mother_id'] = $row->mother_id;
    //         $tempRow['student_name'] = $row->user->first_name.' '.$row->user->last_name;
    //         $tempRow['class_id'] = $row->class_section->class_id;
    //         $tempRow['class_name'] = $row->class_section->class->name.'-'.$row->class_section->section->name.' '.$row->class_section->class->medium->name;
    //         $tempRow['stream_name'] = $row->class_section->class->streams->name ?? '-';
    //         $tempRow['compulsory_fees'] = count($compulsory_fees) ? $compulsory_fees : null;
    //         $tempRow['is_installaments_enabled'] = $is_installaments_enabled;

    //         $paid_installment_data = PaidInstallmentFee::where(['student_id' => $row->id, 'session_year_id' => $effective_session_year_id, 'status' => 1])->first();
    //         $tempRow['is_installment_paid'] = $paid_installment_data ? 1 : 0;

    //         if (isset($installment_data) && ! empty($installment_data)) {
    //             $tempRow['installment_data'] = [];
    //             foreach ($installment_data as $data) {
    //                 // Paid Installment Data
    //                 $paid_installment = PaidInstallmentFee::where(['student_id' => $row->id, 'installment_fee_id' => $data->id, 'status' => 1])->first();

    //                 if (strtotime($current_date) >= strtotime($data->due_date)) {
    //                     $tempRow['installment_data'][] = [
    //                         'id' => $data->id,
    //                         'name' => $data->name,
    //                         'due_date' => $data->due_date,
    //                         'due_charges' => $data->due_charges,
    //                         'due_charges_applicable' => 1,
    //                         'paid' => $paid_installment->status ?? 0,
    //                         'amount' => $paid_installment->amount ?? '',
    //                         'paid_id' => $paid_installment ? $paid_installment->id : '',
    //                         'paid_on' => $paid_installment ? date('d-m-Y', strtotime($paid_installment->date)) : '',
    //                     ];
    //                 } else {
    //                     $tempRow['installment_data'][] = [
    //                         'id' => $data->id,
    //                         'name' => $data->name,
    //                         'due_date' => $data->due_date,
    //                         'due_charges' => $data->due_charges,
    //                         'due_charges_applicable' => 0,
    //                         'paid' => $paid_installment->status ?? 0,
    //                         'amount' => $paid_installment->amount ?? '',
    //                         'paid_id' => $paid_installment ? $paid_installment->id : '',
    //                         'paid_on' => $paid_installment ? date('d-m-Y', strtotime($paid_installment->date ?? '')) : '',
    //                     ];
    //                 }
    //             }
    //         }
    //         if (isset($choiceable_fees) && ! empty($choiceable_fees)) {
    //             $tempRow['choiceable_fees'] = [];
    //             $paid_choiceable_fees_query = FeesChoiceable::where(['class_id' => $row->class_section->class_id, 'student_id' => $row->id, 'session_year_id' => $effective_session_year_id, 'status' => 1]);
    //             foreach ($choiceable_fees as $data) {
    //                 // Clone the Query To Avoid Extra Addition of Where Fees Type ID
    //                 $paid_choiceable_data = (clone $paid_choiceable_fees_query)->where('fees_type_id', $data->fees_type_id);
    //                 if ($paid_choiceable_data->count()) {
    //                     $tempRow['choiceable_fees'][] = [
    //                         'id' => $data->id,
    //                         'name' => $data->fees_type->name,
    //                         'class_id' => $data->class_id,
    //                         'fees_type_id' => $data->fees_type_id,
    //                         'choiceable' => $data->choiceable,
    //                         'amount' => $data->amount,
    //                         'is_paid' => 1,
    //                         'paid_id' => $paid_choiceable_data->first()->id,
    //                         'date' => $paid_choiceable_data->first()->date,
    //                     ];
    //                 } else {
    //                     $tempRow['choiceable_fees'][] = [
    //                         'id' => $data->id,
    //                         'name' => $data->fees_type->name,
    //                         'class_id' => $data->class_id,
    //                         'fees_type_id' => $data->fees_type_id,
    //                         'choiceable' => $data->choiceable,
    //                         'amount' => $data->amount,
    //                         'is_paid' => 0,
    //                     ];
    //                 }
    //             }
    //         }
    //         $tempRow['base_amount'] = $base_amount;
    //         $tempRow['base_amount_with_due_charges'] = $base_amount_with_due_charges;
    //         $tempRow['due_charges'] = [
    //             'date' => $due_date ? date('d-m-Y', strtotime($due_date)) : null,
    //             'charges' => $charges ?? null,
    //         ];
    //         $tempRow['fees_status'] = $row->fees_paid->is_fully_paid ?? null;
    //         // "Total Fees" shows the configured fee amount from fees_classes (the total a student must pay),
    //         // not what has been paid so far. This removes the empty column for unpaid students.
    //         $tempRow['total_fees'] = $base_amount ?? 0;
    //         // Separately expose what has actually been paid for reference
    //         $tempRow['fees_paid_amount'] = $row->fees_paid->total_amount ?? null;
    //         $tempRow['current_date'] = $current_date;
    //         $tempRow['date'] = $row->fees_paid != null && $row->fees_paid->date != null ? date('d-m-Y', strtotime($row->fees_paid->date)) : '-';
    //         $tempRow['session_year_name'] = $row->fees_paid->session_year->name ?? null;
    //         $tempRow['mode'] = $payment_transaction->mode ?? null;
    //         $tempRow['type_of_fee'] = $payment_transaction->type_of_fee ?? null;
    //         $tempRow['cheque_no'] = $payment_transaction->cheque_no ?? null;
    //         $tempRow['created_at'] = convertDateFormat($row->created_at, 'd-m-Y H:i:s');
    //         $tempRow['updated_at'] = convertDateFormat($row->updated_at, 'd-m-Y H:i:s');
    //         $tempRow['operate'] = $operate;
    //         $rows[] = $tempRow;
    //     }
    //     $bulkData['rows'] = $rows;

    //     return response()->json($bulkData);
    // }

    public function clearFeesPaidData($id)
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $fees_paid_data = FeesPaid::find($id);

            // get the ids from fees paid to remove the fees choiced data
            $student_id = $fees_paid_data->student_id;
            $class_id = $fees_paid_data->class_id;
            $session_year_id = $fees_paid_data->session_year_id;

            $fees_paid_data->delete();

            FeesChoiceable::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->delete();
            PaidInstallmentFee::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->delete();
            PaymentTransaction::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->delete();

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

    public function feesPaidReceiptPDF($id)
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $logo = schoolSetting('logo2');
            $logo = public_path('/storage/'.$logo);
            $school_name = config('app.name');
            $school_address = getSettings('school_address');
            $school_address = $school_address['school_address'];
            $currency_symbol = getSettings('currency_symbol');
            if (isset($currency_symbol) && count($currency_symbol)) {
                $currency_symbol = $currency_symbol['currency_symbol'];
            } else {
                $currency_symbol = null;
            }

            // Getting the Fees Paid Data
            $fees_paid = FeesPaid::where('id', $id)->with('student.user:id,first_name,last_name', 'student.class_section', 'session_year')->get()->first();
            // dd($fees_paid->toArray());
            // Variables
            $student_id = $fees_paid->student_id;
            $class_id = $fees_paid->class_id;
            $session_year_id = $fees_paid->session_year_id;

            $optional_fees_type_id = FeesClass::where(['class_id' => $class_id, 'session_year_id' => $session_year_id, 'choiceable' => 1])->pluck('fees_type_id');

            // Paid Installment Data
            $paid_installment = PaidInstallmentFee::where(['student_id' => $student_id, 'class_id' => $class_id,
                'session_year_id' => $session_year_id, 'status' => 1])->with('installment_fee')->get();

            // Fees Choiceable Data
            $fees_choiceable = FeesChoiceable::whereIn('fees_type_id', $optional_fees_type_id)->where(['student_id' => $student_id,
                'class_id' => $class_id,
                'session_year_id' => $session_year_id, 'status' => 1])->with('fees_type')->orderby('id', 'asc')->get();

            // Fees Class Data
            $fees_class = FeesClass::where(['class_id' => $class_id, 'session_year_id' => $session_year_id,
                'choiceable' => 0])->with('fees_type')->get();

            // Session Year Data
            $session_year = SessionYear::where('id', $session_year_id)->first();

            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'school_name', 'fees_paid', 'paid_installment',
                'fees_choiceable', 'currency_symbol', 'school_address', 'fees_class', 'session_year'));

            return $pdf->stream('fees-receipt.pdf');
        } catch (Throwable $e) {
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];

            return response()->json($response);
        }
    }
}
