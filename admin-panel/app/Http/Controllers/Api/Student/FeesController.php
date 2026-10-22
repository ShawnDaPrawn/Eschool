<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Api\Student\Concerns\StudentAccess;
use App\Http\Controllers\Controller;
use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\InstallmentFee;
use App\Models\Notification;
use App\Models\PaidInstallmentFee;
use App\Models\Parents;
use App\Models\PaymentTransaction;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Payment\FeesPaymentService;
use App\Services\Payment\PaymentService;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\LegacyAmount;
use App\Services\Platform\Ledger\Money;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Student app: fees due, payments, receipts and payment transactions.
 */
class FeesController extends Controller
{
    use StudentAccess;

    // Get Fees Details
    public function getFeesDetails(Request $request)
    {
        try {
            $student = $request->user()->student;

            $current_date = Carbon::now('UTC');

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $studentSession = StudentSessions::where('student_id', $student->id)
                ->where('session_year_id', $session_year_id)->first();
            if (! $studentSession) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }
            $class_id = $studentSession->class_section->class_id;

            // Total optional fees amount
            $optional_fees_amount = FeesClass::select(DB::raw('SUM('.CentsColumns::sql('amount', 'amount_cents').') as optional_fees_amount'))->where(['class_id' => $class_id, 'choiceable' => 1, 'session_year_id' => $session_year_id])->first();
            $optional_fees_amount = LegacyAmount::fromCents($optional_fees_amount['optional_fees_amount']);

            // Total optional fees amount
            $compulsory_fees_amount = FeesClass::select(DB::raw('SUM('.CentsColumns::sql('amount', 'amount_cents').') as compulsory_fees_amount'))->where(['class_id' => $class_id, 'choiceable' => 0, 'session_year_id' => $session_year_id])->first();
            $compulsory_fees_amount = LegacyAmount::fromCents($compulsory_fees_amount['compulsory_fees_amount']);

            // Fees Class Data
            $fees_class = FeesClass::where('class_id', $class_id)->where('session_year_id', $session_year_id)->with('fees_type')->get();

            // arrays for data
            $compulsory_fees_data = [];
            $optional_fees_data = [];
            $installment_data = [];

            $payment_transaction_db = PaymentTransaction::where(['student_id' => $student->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->latest()->first();

            if ($payment_transaction_db) {
                if ($payment_transaction_db->date && $payment_transaction_db->payment_status == 2) {
                    $datetime = Carbon::parse($payment_transaction_db->date);
                    // $time = $datetime->format('H:i:s');
                    $addHour = $datetime->copy()->addHour();
                    if (Carbon::now()->gt($addHour)) {
                        $payment_transaction_db->payment_status = 0;
                        $payment_transaction_db->save();
                    }
                }
                $payment_transaction_status = $payment_transaction_db->payment_status;
            }
            // Get the Compulsory Fees Data and Optional Fees Data
            foreach ($fees_class as $data) {
                if ($data->choiceable == 1) {
                    $paid_optional_data = FeesChoiceable::where(['student_id' => $student->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'fees_type_id' => $data['fees_type_id'], 'status' => 1])->first();
                    $optional_fees_data[] = [
                        'id' => $data->fees_type_id,
                        'name' => $data->fees_type->name,
                        'amount' => $data->amount,
                        'is_paid' => $paid_optional_data->status ?? 0,
                        'paid_date' => $paid_optional_data->date ?? null,
                    ];
                } else {
                    $is_fully_paid_data = FeesPaid::where(['student_id' => $student->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'is_fully_paid' => 1]);
                    $compulsory_fees_data[] = [
                        'id' => $data->fees_type_id,
                        'name' => $data->fees_type->name,
                        'amount' => $data->amount,
                        'is_paid' => ! empty($is_fully_paid_data->count()) ? 1 : 0,
                        'paid_on' => ! empty($is_fully_paid_data->count()) ? ((isset($is_fully_paid_data->first()->date) && ! empty($is_fully_paid_data->first()->date)) ? date('Y-m-d', strtotime($is_fully_paid_data->first()->date)) : null) : '',
                    ];
                }
            }

            // Checking for Due Charges Paid with Fully Compulsory Amount and Add it to Compulsory Fees Data Array
            if (isset($compulsory_fees_data) && ! empty($compulsory_fees_data)) {
                $paid_charges_due = FeesChoiceable::where(['student_id' => $student->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'is_due_charges' => 1]);
                if ($paid_charges_due->count()) {
                    array_push($compulsory_fees_data, [
                        'id' => '',
                        'name' => 'Due Charges',
                        'amount' => $paid_charges_due->first()->total_amount,
                        'is_paid' => 1,
                    ]);
                }
            }

            // check the installments data for current session year
            $installment_db = InstallmentFee::where('session_year_id', $session_year_id);
            if ($installment_db->count()) {
                $installment_db_data = $installment_db->get();
                foreach ($installment_db_data as $data) {
                    $paid_installment_data = PaidInstallmentFee::where(['student_id' => $student->id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'installment_fee_id' => $data['id'], 'status' => 1])->first();
                    $installment_data[] = [
                        'id' => $data->id,
                        'name' => $data->name,
                        'due_date' => date('Y-m-d', strtotime($data->due_date)),
                        'due_charges' => $data->due_charges,
                        'is_paid' => $paid_installment_data->status ?? 0,
                        'paid_date' => $paid_installment_data->date ?? null,
                        'paid_due_charges' => ! empty($paid_installment_data) ? number_format($paid_installment_data->due_charges ?? 0, 2) : '',
                    ];
                }
            }
            // Due Date And Due Charges From Session Year For Fully pay Compulsory Amount (Without Installments)
            $session_year_data = SessionYear::where('id', $session_year_id)->first();

            $due_date = date('Y-m-d', strtotime($session_year_data->fee_due_date));
            $due_charges = $session_year_data->fee_due_charges;

            return ResponseService::successResponse('Fees Details Fetched Successfully', null, [
                'compulsory_fees_data' => $compulsory_fees_data ?? [''],
                'optional_fees_data' => $optional_fees_data ?? [''],
                'installment_data' => $installment_data ?? (object) null,
                'compulsory_fees_total' => $compulsory_fees_amount ?? 0,
                'optional_fees_total' => $optional_fees_amount ?? 0,
                'compulsory_due_date' => $due_date,
                'compulsory_due_charges' => $due_charges,
                'current_date' => $current_date,
                'is_fee_pending' => (isset($payment_transaction_status) && $payment_transaction_status == 2) ? 1 : 0,
            ]);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Store Fees Transaction
    public function storeFeesTransaction(Request $request, FeesPaymentService $feesPaymentService)
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:1,2,3,4',
            'amount' => 'required|numeric',
            'type_of_fee' => 'required|in:0,1,2',
            // Still sent by the app; the server works it out itself.
            'is_fully_paid' => 'nullable|in:0,1',
            'optional_fees_data' => 'nullable|array',
            'installment_data' => 'nullable|array',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        // Only a payment gateway the school has switched on. Razorpay (1), Paystack (3)
        // and Flutterwave (4) were removed, so they are never available.
        $gatewayStatusSetting = [PaymentService::STRIPE => 'stripe_status'][(int) $request->payment_method] ?? null;
        if ($gatewayStatusSetting === null || empty(getSettings($gatewayStatusSetting)[$gatewayStatusSetting] ?? null)) {
            return ResponseService::validationError('The selected payment method is not available.');
        }
        try {
            $student = $request->user()->student;
            $mobile = Auth::user()->mobile ?? '';
            $name = Auth::user()->full_name;
            $father_id = $student->father_id;
            // $mother_id = $student->mother_id;
            $guardian_id = $student->guardian_id;
            $email = Parents::where('id', $father_id)->pluck('email')->first();
            if (empty($email)) {
                $email = Parents::where('id', $guardian_id)->pluck('email')->first();
            }
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];
            $parent_id = $student->father_id ?: $student->guardian_id;

            // What is being paid, its amount, due charges and whether it completes
            // the compulsory fees are all worked out here; the app's amount must match.
            try {
                $plan = $feesPaymentService->prepare(
                    (int) $student->id,
                    (int) $session_year_id,
                    (int) $request->type_of_fee,
                    (array) ($request->optional_fees_data ?? []),
                    (array) ($request->installment_data ?? []),
                    $request->amount
                );
            } catch (ValidationException $e) {
                return ResponseService::validationError($e->validator->errors()->first());
            }

            DB::beginTransaction();
            $pending = $feesPaymentService->createPendingTransaction($plan, $parent_id, (int) $request->payment_method);
            $payment_transaction_db = $pending['transaction'];

            $paymentIntent = PaymentService::create($request->payment_method)->createAndFormatPaymentIntent(
                Money::ofCents($plan['total_amount_cents']),
                $feesPaymentService->gatewayMetadata($plan, $pending, $parent_id, ['email' => $email, 'name' => $name, 'mobile' => $mobile])
            );

            $payment_transaction_db->order_id = $paymentIntent['id'];
            $payment_transaction_db->save();

            $payment_gateway_details = [
                ...$paymentIntent,
                'payment_transaction_id' => $payment_transaction_db->id,
            ];
            DB::commit();

            return ResponseService::successResponse('data_store_successfully', null, [
                'payment_gateway_details' => $payment_gateway_details,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();

            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // add the transaction data in transaction table
    public function storeFees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required',
            'payment_id' => 'nullable',
            'payment_signature' => 'nullable',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Updating the student's own pending Payment Transaction. This only
            // notes the gateway ids; payments are recorded from the webhooks.
            $transaction_db = PaymentTransaction::where('id', $request->transaction_id)
                ->where('student_id', $request->user()->student->id)
                ->where('payment_status', 2)
                ->first();
            if (! $transaction_db) {
                return ResponseService::errorResponse('Invalid transaction', null, 103);
            }
            if ($request->payment_id && empty($transaction_db->payment_id)) {
                $transaction_db->payment_id = $request->payment_id;
            }
            if ($request->payment_signature && empty($transaction_db->payment_signature)) {
                $transaction_db->payment_signature = $request->payment_signature;
            }
            $transaction_db->save();

            return ResponseService::successResponse('data_update_successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // get the fees paid list
    public function feesPaidList(Request $request)
    {
        try {
            $student = $request->user()->student;

            $session_year_id = getSettings('session_year')['session_year'];

            $fees_paid = FeesPaid::where(['student_id' => $student->id, 'session_year_id' => $session_year_id])->with('session_year:id,name', 'class.medium')->get();

            return ResponseService::successResponse('Fees Paid List Fetched Successfully', $fees_paid);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Generate The Reciept
    public function feesPaidReceiptPDF(Request $request)
    {
        try {
            $student = $request->user()->student;

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

            // The receipt the app asked for (fees_paid_id), else this session year's latest.
            // It used to be the student's first fees_paids row from any year.
            $fees_paid = FeesPaid::where('student_id', $student->id)
                ->when(
                    $request->filled('fees_paid_id'),
                    fn ($q) => $q->whereKey((int) $request->fees_paid_id),
                    fn ($q) => $q->orderByRaw('session_year_id = ? desc', [getSettings('session_year')['session_year'] ?? 0])->latest('id')
                )
                ->with('student.user:id,first_name,last_name', 'class', 'session_year')
                ->first();

            // Check That Fees Paid Data Exists Or Not
            if (! $fees_paid) {
                return ResponseService::errorResponse('No Fees Paid Found', null, 103);
            }

            // Variables
            $student_id = $fees_paid->student_id;
            $class_id = $fees_paid->class_id;
            $session_year_id = $fees_paid->session_year_id;

            // Paid Installment Data
            $paid_installment = PaidInstallmentFee::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'status' => 1])->with('installment_fee')->get();

            // Fees Choiceable Data
            $optional_fees_type_id = FeesClass::where(['class_id' => $class_id, 'choiceable' => 1])->pluck('fees_type_id');
            $fees_choiceable = FeesChoiceable::whereIn('fees_type_id', $optional_fees_type_id)->where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'status' => 1])->with('fees_type')->orderby('id', 'asc')->get();

            // Fees Class Data
            $fees_class = FeesClass::where(['class_id' => $class_id, 'choiceable' => 0])->with('fees_type')->get();

            // Session Year Data
            $session_year = SessionYear::where('id', $session_year_id)->first();

            // Load the HTML
            $pdf = Pdf::loadView('fees.fees_receipt', compact('logo', 'school_name', 'fees_paid', 'paid_installment', 'fees_choiceable', 'currency_symbol', 'school_address', 'fees_class', 'session_year'));

            // Get The Output Of PDF
            $output = $pdf->output();

            return ResponseService::successResponse('Fees Paid Receipt PDF Fetched Successfully', null, ['pdf' => base64_encode($output)]);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getFeesPaymentTransactions(Request $request)
    {
        try {

            $student = $request->user()->student;
            // $parent_id = Auth::user()->parent->id;
            // $child_id = Students::where('father_id',$parent_id)->orWhere('mother_id',$parent_id)->orWhere('guardian_id',$parent_id)->pluck('id');
            $fees_payment_transactions = PaymentTransaction::where('student_id', $student->id)
                ->when($request->session_year_id, fn ($q) => $q->where('session_year_id', $request->session_year_id))
                ->with('session_year')->with(['student' => function ($q) {
                    $q->select('id', 'user_id')->with('user:id,first_name,last_name');
                }])->orderBy('id', 'desc')->paginate(15);

            foreach ($fees_payment_transactions as $transaction) {
                $date = $transaction->date;
                if ($date && $transaction->payment_status == 2) {
                    $datetime = Carbon::parse($date);
                    $time = $datetime->format('H:i:s');
                    $addHour = $datetime->copy()->addHour();
                    if (Carbon::now()->gt($addHour)) {
                        $transaction->payment_status = 0;
                        $transaction->save();
                    }
                }
            }
            $fees_payment_transactions = $fees_payment_transactions->toArray();

            return ResponseService::successResponse('Fees Payment Transactions Fetched Successfully', [
                'current_page' => $fees_payment_transactions['current_page'],
                'transaction-data' => $fees_payment_transactions['data'],
                'from' => $fees_payment_transactions['from'],
                'last_page' => $fees_payment_transactions['last_page'],
                'per_page' => $fees_payment_transactions['per_page'],
                'to' => $fees_payment_transactions['to'],
                'total' => $fees_payment_transactions['total'],
            ]);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Make Transaction Fail API
    public function failPaymentTransactionStatus(Request $request)
    {
        try {
            $student = $request->user()->student;

            // Only the student's own transaction, and only while it is pending
            // (a paid transaction can't be marked failed).
            $update_status = PaymentTransaction::where('id', $request->payment_transaction_id)
                ->where('student_id', $student->id)
                ->where('payment_status', 2)
                ->first();
            // Conditional update, so a webhook marking it paid meanwhile wins.
            if (! $update_status || ! PaymentTransaction::whereKey($update_status->id)->where('payment_status', 2)->update(['payment_status' => 0])) {
                return ResponseService::errorResponse('Invalid transaction', null, 103);
            }
            $total_amount = $update_status->total_amount;

            $user[] = $student->user_id;
            $body = 'Amount :- '.$total_amount;
            $type = 'online';
            $image = null;
            $userinfo = null;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $notification = new Notification;
            $notification->send_to = 2;
            $notification->session_year_id = $session_year_id;
            $notification->title = 'Payment Failed';
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();
            foreach ($user as $data) {
                $user_notification = new UserNotification;
                $user_notification->notification_id = $notification->id;
                $user_notification->user_id = $data;
                $user_notification->save();
            }

            sendSimpleNotification($user, 'Payment Failed', $body, $type, $image, $userinfo);

            return ResponseService::successResponse('Data Updated Successfully');
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function sendFeeNotification(Request $request)
    {
        try {
            $student = $request->user()->student;

            $parent = [
                $student->father_id ?? '',
                $student->mother_id ?? '',
                $student->guardian_id ?? '',
            ];

            $user = Parents::whereIn('id', $parent)->pluck('user_id');

            $title = 'Fees are dues';
            $body = $student->user->first_name.' '.$student->user->last_name.' '.'wants to keep learning! Please pay the overdue fees as soon as possible.';
            $type = 'fees-due';
            $image = null;
            $userinfo = (string) $student->id;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $notification = new Notification;
            $notification->send_to = 2;
            $notification->session_year_id = $session_year_id;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            sendSimpleNotification($user, $title, $body, $type, $image, $userinfo);

            return ResponseService::successResponse('Notification Send Successfully', null);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }
}
