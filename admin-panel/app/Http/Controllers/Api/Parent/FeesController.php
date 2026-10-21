<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Api\Parent\Concerns\ParentAccess;
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
use App\Models\UserNotification;
use App\Services\Payment\PaymentService;
use App\Services\ResponseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Parent app: fees due, payments, receipts and payment transactions. Also
 * serves the student app's get-payment-status route.
 */
class FeesController extends Controller
{
    use ParentAccess;

    // Get Fees Details
    public function getFeesDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $child_id = $this->resolveChild($request)->id;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $current_date = Carbon::now('UTC');
            $studentSession = StudentSessions::where('student_id', $child_id)
                ->where('session_year_id', $session_year_id)->first();
            if (! $studentSession) {
                return ResponseService::errorResponse('No session found for this student in the current session year', null, 113);
            }
            $class_id = $studentSession->class_section->class_id;

            // Total optional fees amount
            $optional_fees_amount = FeesClass::select(DB::raw('SUM(amount) as optional_fees_amount'))
                ->where(['class_id' => $class_id, 'choiceable' => 1, 'session_year_id' => $session_year_id])->first();
            $optional_fees_amount = $optional_fees_amount['optional_fees_amount'];

            // Total optional fees amount
            $compulsory_fees_amount = FeesClass::select(DB::raw('SUM(amount) as compulsory_fees_amount'))
                ->where(['class_id' => $class_id, 'choiceable' => 0, 'session_year_id' => $session_year_id])->first();
            $compulsory_fees_amount = $compulsory_fees_amount['compulsory_fees_amount'];

            // Fees Class Data
            $fees_class = FeesClass::where('class_id', $class_id)->where('session_year_id', $session_year_id)->with('fees_type')->get();

            // arrays for data
            $compulsory_fees_data = [];
            $optional_fees_data = [];
            $installment_data = [];

            $payment_transaction_db = PaymentTransaction::where(['student_id' => $child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id])->latest()->first();

            if ($payment_transaction_db) {
                if ($payment_transaction_db->date && $payment_transaction_db->payment_status == 2) {
                    $datetime = Carbon::parse($payment_transaction_db->date);
                    $time = $datetime->format('H:i:s');
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
                    $paid_optional_data = FeesChoiceable::where(['student_id' => $child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'fees_type_id' => $data['fees_type_id'], 'status' => 1])->first();
                    $optional_fees_data[] = [
                        'id' => $data->fees_type_id,
                        'name' => $data->fees_type->name,
                        'amount' => $data->amount,
                        'is_paid' => $paid_optional_data->status ?? 0,
                        'paid_date' => $paid_optional_data->date ?? null,
                    ];
                } else {
                    $is_fully_paid_data = FeesPaid::where(['student_id' => $child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'is_fully_paid' => 1]);
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
                $paid_charges_due = FeesChoiceable::where(['student_id' => $child_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id, 'is_due_charges' => 1]);
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
                    $paid_installment_data = PaidInstallmentFee::where([
                        'student_id' => $child_id,
                        'class_id' => $class_id,
                        'session_year_id' => $session_year_id,
                        'installment_fee_id' => $data['id'],
                        'status' => 1,
                    ])->first();

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
            ], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Store Fees Transaction
    public function storeFeesTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
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
            $child_id = $this->resolveChild($request)->id;

            $parent_id = Auth::user()->parent->id;
            $email = Auth::user()->email;
            $mobile = Auth::user()->mobile ?? '';
            $name = Auth::user()->full_name;
            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            // What is being paid, its amount, due charges and whether it completes
            // the compulsory fees are all worked out on the server
            // (FeesPaymentService); the app's amount must match.
            $feesPaymentService = app(\App\Services\Payment\FeesPaymentService::class);
            try {
                $plan = $feesPaymentService->prepare(
                    (int) $child_id,
                    (int) $session_year_id,
                    (int) $request->type_of_fee,
                    (array) ($request->optional_fees_data ?? []),
                    (array) ($request->installment_data ?? []),
                    $request->amount
                );
            } catch (\Illuminate\Validation\ValidationException $e) {
                return ResponseService::validationError($e->validator->errors()->first());
            }

            DB::beginTransaction();
            $pending = $feesPaymentService->createPendingTransaction($plan, $parent_id, (int) $request->payment_method);
            $payment_transaction_db = $pending['transaction'];

            $paymentIntent = PaymentService::create($request->payment_method)->createAndFormatPaymentIntent(
                $plan['total_amount'],
                $feesPaymentService->gatewayMetadata($plan, $pending, $parent_id, ['email' => $email, 'name' => $name, 'mobile' => $mobile])
            );

            $payment_transaction_db->order_id = $paymentIntent['id'];
            $payment_transaction_db->save();
            DB::commit();

            $payment_gateway_details = [
                ...$paymentIntent,
                'payment_transaction_id' => $payment_transaction_db->id,
            ];
            return ResponseService::successResponse('Fees Transaction Stored Successfully', [], ['payment_gateway_details' => $payment_gateway_details], 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // add the transaction data in transaction table
    public function storeFees(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|integer',
            'payment_id' => 'nullable|string|max:191',
            'payment_signature' => 'nullable|string|max:191',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Only a pending or succeeded transaction of one of this parent's children. A
            // succeeded one is allowed because the gateway webhook often settles the
            // transaction before the app calls this; the app reports a failed payment
            // when this errors.
            $transaction_db = PaymentTransaction::whereKey((int) $request->transaction_id)
                ->whereIn('student_id', $this->parentChildIds($request))
                ->whereIn('payment_status', [1, 2]) // 0 failed, 1 succeeded, 2 pending
                ->first();
            if (! $transaction_db) {
                return ResponseService::errorResponse('No Payment Transaction Found', null, 103);
            }

            // The gateway's ids are recorded once; the webhook's (or the first) values stay.
            if ($request->payment_id && empty($transaction_db->payment_id)) {
                $transaction_db->payment_id = $request->payment_id;
            }
            if ($request->payment_signature && empty($transaction_db->payment_signature)) {
                $transaction_db->payment_signature = $request->payment_signature;
            }
            $transaction_db->save();
            return ResponseService::successResponse('Data Updated Successfully', null, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // get the fees paid list
    public function feesPaidList(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => 'required',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $fees_paid = FeesPaid::where(['student_id' => $this->resolveChild($request)->id])->with('session_year:id,name', 'class.medium')->get();
            return ResponseService::successResponse('Fees Paid List Fetched Successfully', $fees_paid, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    // Generate The Reciept
    public function feesPaidReceiptPDF(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fees_paid_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            $parent = Auth::user();
            $parent_name = $parent->first_name.' '.$parent->last_name;
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

            // Getting the Fees Paid Data (only for one of this parent's children)
            $fees_paid = FeesPaid::whereKey((int) $request->fees_paid_id)
                ->whereIn('student_id', $this->parentChildIds($request))
                ->with('student.user:id,first_name,last_name', 'class', 'session_year')->first();

            // Check That Fees Paid Data Exists Or Not, and is for this parent's child
            if (! $fees_paid || ! $parent->parent->hasChild($fees_paid->student_id)) {
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
            return ResponseService::successResponse('Fees Paid Receipt PDF Fetched Successfully', null, ['pdf' => base64_encode($output)], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getFeesPaymentTransactions(Request $request)
    {
        try {

            // Outside the CheckChild group: an optional child_id must be one of the parent's children.
            $child_id = $request->filled('child_id')
                ? [$this->resolveChild($request)->id]
                : $this->parentChildIds($request);
            $fees_payment_transactions = PaymentTransaction::whereIn('student_id', $child_id)
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
        $validator = Validator::make($request->all(), [
            'payment_transaction_id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Only a still-pending transaction of one of this parent's children; a payment
            // the gateway has already settled is never turned into a failure here.
            $update_status = PaymentTransaction::whereKey((int) $request->payment_transaction_id)
                ->whereIn('student_id', $this->parentChildIds($request))
                ->where('payment_status', 2) // 0 failed, 1 succeeded, 2 pending
                ->first();
            if (! $update_status) {
                return ResponseService::errorResponse('No Payment Transaction Found', null, 103);
            }
            $total_amount = $update_status->total_amount;
            $update_status->payment_status = 0;
            $update_status->save();

            $parent_id = Auth::user()->parent->id;
            $user = Parents::where('id', $parent_id)->pluck('user_id');
            $body = 'Amount :- '.$total_amount;
            $type = 'online';
            $image = null;
            $userinfo = null;

            $session_year = getSettings('session_year');
            $session_year_id = $session_year['session_year'];

            $notification = new Notification;
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 2;
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
            return ResponseService::successResponse('Data Updated Successfully', null, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    public function getPaymentStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_intent_id' => ['required', 'string', 'max:191', 'regex:/^pi_[A-Za-z0-9_]+$/'],
        ]);
        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->first());
        }
        try {
            // Shared by /api/parent and /api/student: only a Stripe payment the caller
            // started (for one of their children, or for themselves as a student).
            $ownsPayment = PaymentTransaction::where('order_id', $request->payment_intent_id)
                ->whereIn('student_id', $this->payerStudentIds($request))
                ->exists();
            if (! $ownsPayment) {
                return ResponseService::errorResponse('No Payment Transaction Found', null, 103);
            }

            if (empty(getSettings('stripe_status')['stripe_status'] ?? null)) {
                return ResponseService::errorResponse('Stripe payments are not enabled', null, 103);
            }

            // The app compares this with Stripe's own status ("succeeded").
            $paymentIntent = PaymentService::create(2)->retrievePaymentIntent($request->payment_intent_id);
            $data = $paymentIntent['payment_gateway_response']->status;
            return ResponseService::successResponse('Data Fetched Successfully', $data, [], 200);
        } catch (Throwable $e) {
            return ResponseService::errorResponse('error_occurred', null, 103, $e);
        }
    }

    /**
     * Ids of the students whose payments the caller may see: a student's own id on
     * the student API (get-payment-status is shared), otherwise the parent's children.
     *
     * @return list<int>
     */
    private function payerStudentIds(Request $request): array
    {
        $user = $request->user();
        if ($request->is('api/student/*')) {
            return ($user?->hasRole('Student') && $user->student) ? [(int) $user->student->id] : [];
        }

        return $this->parentChildIds($request);
    }
}
