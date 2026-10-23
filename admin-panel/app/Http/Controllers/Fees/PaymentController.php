<?php

declare(strict_types=1);

namespace App\Http\Controllers\Fees;

use App\Http\Controllers\Controller;
use App\Models\FeesChoiceable;
use App\Models\FeesPaid;
use App\Models\Notification;
use App\Models\PaidInstallmentFee;
use App\Models\Parents;
use App\Models\PaymentTransaction;
use App\Models\Students;
use App\Services\Platform\Ledger\LegacyAmount;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Notifications\Notifier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Admin panel: record or remove a student's fee payments (compulsory, optional and installments).
 */
class PaymentController extends Controller
{
    // Pay Optional Fees Offline
    public function optionalFeesPaidStore(Request $request)
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'mode' => 'required|in:0,1',
            'session_year_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }
        $session_year_id = $request->input('session_year_id');

        try {
            DB::beginTransaction();
            $date = date('Y-m-d H:i:s', strtotime($request->date));

            // Resolve the parent for the payment records. father_id/guardian_id are plain nullable
            // integers that can be 0 when unset, so fall back father -> guardian with ?: (not ??,
            // which keeps a literal 0), and bail out early with a clear message when neither exists
            // so the transaction isn't mis-recorded against a non-existent parent (and the payment
            // success notification reaches the parent who actually paid).
            $father_id = Students::where('id', $request->student_id)->pluck('father_id')->first();
            $guardian_id = Students::where('id', $request->student_id)->pluck('guardian_id')->first();

            $parent_id = $father_id ?: $guardian_id;
            if (empty($parent_id) || ! Parents::withTrashed()->whereKey($parent_id)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_parent_or_guardian_assigned'),
                ]);
            }

            $user_id = Parents::where('id', $parent_id)->pluck('user_id')->first();
            // Add Data in Payment Transaction Of Optional Payment Transaction
            $payment_transaction_store = new PaymentTransaction;
            $payment_transaction_store->student_id = $request->student_id;
            $payment_transaction_store->class_id = $request->class_id;
            $payment_transaction_store->parent_id = $parent_id;
            $payment_transaction_store->mode = $request->mode;
            $payment_transaction_store->cheque_no = (isset($request->cheque_no) && ! empty($request->cheque_no) && $request->mode == 1) ? $request->cheque_no : null;
            $payment_transaction_store->type_of_fee = 2;
            $payment_transaction_store->payment_status = 1;
            // Amounts are recorded in cents (the float columns follow).
            $paid = Money::ofCents((int) LegacyAmount::toCents($request->total_amount));
            $payment_transaction_store->total_amount_cents = $paid;
            $payment_transaction_store->session_year_id = $session_year_id;
            $payment_transaction_store->save();

            // Add Data in Array of Optional Fees
            $optional_fees_store = [];
            foreach ($request->optional_fees_type_data as $fees_type_data) {
                if (isset($fees_type_data['id']) && ! empty($fees_type_data['id'])) {
                    $optional_fees_store[] = [
                        'student_id' => $request->student_id,
                        'class_id' => $request->class_id,
                        'fees_type_id' => $fees_type_data['id'],
                        'is_due_charges' => 0,
                        'total_amount' => $fees_type_data['amount'],
                        'total_amount_cents' => LegacyAmount::toCents($fees_type_data['amount']),
                        'session_year_id' => $session_year_id,
                        'date' => $date,
                        'status' => 1,
                        'payment_transaction_id' => $payment_transaction_store->id,
                    ];
                }
            }

            // Add Data in Fees Choiceable Of Optional Payment
            FeesChoiceable::insert($optional_fees_store);

            // Add Data in Fees Paid Of Optional Payment Transaction
            $update_fees_paid_query = FeesPaid::where(['student_id' => $request->student_id, 'class_id' => $request->class_id, 'session_year_id' => $session_year_id]);

            $fees_paid = $update_fees_paid_query->firstOrNew();
            $fees_paid->total_amount_cents = ($fees_paid->money('total_amount') ?? Money::zero())->plus($paid);
            $fees_paid->date = $date;
            if (! $fees_paid->exists) {
                $fees_paid->student_id = $request->student_id;
                $fees_paid->class_id = $request->class_id;
                $fees_paid->is_fully_paid = 0;
                $fees_paid->session_year_id = $session_year_id;
            }
            $fees_paid->save();
            $user[] = $user_id;
            $body = 'Amount :- '.$request->total_amount;
            $type = 'Online';
            $image = null;
            $userinfo = null;

            $notification = new Notification;
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 2;
            $notification->title = 'Payment Success';
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();
            app(Notifier::class)->assign($notification, $user);

            // Saved before the push, so a push failure can't undo the payment.
            DB::commit();

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function feesPaidRemoveChoiceableFees($id)
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $fees_choiceable = FeesChoiceable::find($id);

            // Get Detials
            $student_id = $fees_choiceable->student_id;
            $class_id = $fees_choiceable->class_id;
            $session_year_id = $fees_choiceable->session_year_id;
            $fees_choiceable_amount = $fees_choiceable->money('total_amount') ?? Money::zero();
            $fees_payment_transaction_id = $fees_choiceable->payment_transaction_id;

            DB::beginTransaction();

            // Delete Fees Choiceable Entry
            $fees_choiceable->delete();

            // Check the Payment Transaction ID Is Not Null
            if (isset($fees_payment_transaction_id) && ! empty($fees_payment_transaction_id)) {
                // Get the Payment Transaction Amount
                $fees_transaction_amount = PaymentTransaction::find($fees_payment_transaction_id)?->money('total_amount') ?? Money::zero();

                // Reduce the amount of Deleted Choiceable Fees in Payment Transaction
                $updated_transaction_fees_amount = $fees_transaction_amount->minus($fees_choiceable_amount);

                // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
                if (! $updated_transaction_fees_amount->isZero()) {
                    $fees_transaction_update = PaymentTransaction::find($fees_payment_transaction_id);
                    $fees_transaction_update->total_amount_cents = $updated_transaction_fees_amount;
                    $fees_transaction_update->save();
                } else {
                    $fees_transaction_update = PaymentTransaction::where('id', $fees_payment_transaction_id)->delete();
                }
            }

            // Check the Fees Paid Entry
            $fees_paid_db = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
            // Get the Fees Paid ID
            $fees_paid_id = $fees_paid_db->pluck('id')->first();
            // Get the Fees Paid Amount
            $fees_paid_amount = FeesPaid::find($fees_paid_id)?->money('total_amount') ?? Money::zero();

            // Reduce the amount of Deleted Choiceable Fees in Fees Paid
            $updated_fees_paid_amount = $fees_paid_amount->minus($fees_choiceable_amount);

            // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
            if (! $updated_fees_paid_amount->isZero()) {
                $fees_paid_update = FeesPaid::find($fees_paid_id);
                $fees_paid_update->total_amount_cents = $updated_fees_paid_amount;
                $fees_paid_update->save();
            } else {
                $fees_paid_update = FeesPaid::where('id', $fees_paid_id)->delete();
            }
            DB::commit();

            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }

    public function compulsoryFeesPaidStore(Request $request)
    {
        // dd($request->all());
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'mode' => 'required|in:0,1',
            'session_year_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'error' => true,
                'message' => $validator->errors()->first(),
            ];

            return response()->json($response);
        }

        $session_year_id = $request->input('session_year_id');

        try {
            // The catch below rolls back: the transaction, paid rows and notification go together.
            DB::beginTransaction();
            $date = date('Y-m-d H:i:s', strtotime($request->date));

            // // Enforce sequential installment payment: earlier installments must be paid first
            // if (isset($request->installment_fees) && is_array($request->installment_fees) && count($request->installment_fees) > 0) {
            //     // Fetch all installments for the class/session ordered by due_date/id
            //         ->orderBy('due_date')
            //         ->orderBy('id')
            //         ->get(['id']);
            //     $orderedIds = $allInstallments->pluck('id')->values();

            //     // Determine the lowest-index installment being paid now
            //     $requestedIds = collect($request->installment_fees)->pluck('id')->filter()->values();
            //     if ($requestedIds->count() > 0) {
            //         $minRequestedIndex = $orderedIds->search($requestedIds->min());
            //         // Ensure all installments before this index are already paid
            //         $requiredPriorIds = $orderedIds->slice(0, $minRequestedIndex)->all();
            //         if (!empty($requiredPriorIds)) {
            //                 ->where(['student_id' => $request->student_id, 'class_id' => $request->class_id, 'session_year_id' => $session_year_id, 'status' => 1])
            //                 ->pluck('installment_fee_id')
            //                 ->all();
            //             $missing = array_diff($requiredPriorIds, $unpaidPrior);
            //             if (!empty($missing)) {
            //                 return response()->json([
            //                     'error' => true,
            //                     'message' => trans('please_pay_previous_installments_first')
            //                 ]);
            //             }
            //         }
            //     }
            // }

            // Resolve the parent for the payment records. father_id/guardian_id are plain nullable
            // integers that can be 0 when unset, so fall back father -> guardian with ?: (not ??,
            // which keeps a literal 0). payment_transactions and paid_installment_fees both require a
            // real parents.id (FK), so bail out early with a clear message when none exists instead of
            // hitting a foreign-key violation (and leaving an orphan payment_transactions row behind).
            $father_id = Students::where('id', $request->student_id)->pluck('father_id')->first();
            $guardian_id = Students::where('id', $request->student_id)->pluck('guardian_id')->first();

            $parent_id = $father_id ?: $guardian_id;
            if (empty($parent_id) || ! Parents::withTrashed()->whereKey($parent_id)->exists()) {
                return response()->json([
                    'error' => true,
                    'message' => trans('no_parent_or_guardian_assigned'),
                ]);
            }

            $user_id = Parents::where('id', $parent_id)->pluck('user_id')->first();

            // Add Data in Payment Transaction Of Optional Payment Transaction
            $payment_transaction_store = new PaymentTransaction;
            $payment_transaction_store->student_id = $request->student_id;
            $payment_transaction_store->class_id = $request->class_id;
            $payment_transaction_store->parent_id = $parent_id;
            $payment_transaction_store->mode = $request->mode;
            $payment_transaction_store->cheque_no = (isset($request->cheque_no) &&
                ! empty($request->cheque_no)) ? $request->cheque_no : null;
            $payment_transaction_store->type_of_fee = isset($request->installment_fees) &&
                ! empty($request->installment_fees) ? 1 : 0;
            $payment_transaction_store->payment_status = 1;
            $payment_transaction_store->date = $date;
            // Amounts are recorded in cents (the float columns follow).
            $paid = Money::ofCents((int) LegacyAmount::toCents($request->total_amount));
            $payment_transaction_store->total_amount_cents = $paid;
            $payment_transaction_store->session_year_id = $session_year_id;
            $payment_transaction_store->save();

            $installment_fees_store = [];
            $is_fully_paid = 0;

            if ($request->filled('installment_fees') && is_array($request->installment_fees)) {
                foreach ($request->installment_fees as $data) {
                    if (! empty($data['id'])) {
                        $installment_fees_store[] = [
                            'student_id' => $request->student_id,
                            'parent_id' => $parent_id,
                            'class_id' => $request->class_id,
                            'installment_fee_id' => $data['id'],
                            'amount' => $data['amount'],
                            'amount_cents' => LegacyAmount::toCents($data['amount']),
                            'session_year_id' => $session_year_id,
                            'date' => $date,
                            'status' => 1,
                            'due_charges' => $data['due_charges'] ?? null,
                            'due_charges_cents' => LegacyAmount::toCents($data['due_charges'] ?? null),
                            'payment_transaction_id' => $payment_transaction_store->id,
                        ];
                        $is_fully_paid = $data['fully_paid'] ?? 0;
                    }
                }

                if (! empty($installment_fees_store)) {
                    PaidInstallmentFee::insert($installment_fees_store);
                }
            }

            if ($request->installment_mode == 0) {
                $is_fully_paid = 1;
            }
            // Add Data in Fees Paid Of Optional Payment Transaction
            $update_fees_paid_query = FeesPaid::where([
                'student_id' => $request->student_id,
                'class_id' => $request->class_id,
                'session_year_id' => $session_year_id,
            ]);

            $fees_paid = $update_fees_paid_query->firstOrNew();
            $fees_paid->total_amount_cents = ($fees_paid->money('total_amount') ?? Money::zero())->plus($paid);
            $fees_paid->date = $date;
            $fees_paid->is_fully_paid = $is_fully_paid;

            if ($request->installment_mode == 0) {
                $fees_paid->due_charges = $request->due_charges_whole_year;
            } else {
                $fees_paid->due_charges = null;
            }
            if (! $fees_paid->exists) {
                $fees_paid->student_id = $request->student_id;
                $fees_paid->class_id = $request->class_id;
                $fees_paid->session_year_id = $session_year_id;
            }
            $fees_paid->save();
            $user[] = $user_id;
            $body = 'Amount :- '.$request->total_amount;
            $type = 'Online';
            $image = null;
            $userinfo = null;

            $notification = new Notification;
            $notification->session_year_id = $session_year_id;
            $notification->send_to = 2;
            $notification->title = 'Payment Success';
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();
            app(Notifier::class)->assign($notification, $user);

            // Saved before the push, so a push failure can't undo the payment.
            DB::commit();

            $response = [
                'error' => false,
                'message' => trans('data_store_successfully'),
            ];
        } catch (\Throwable $e) {

            if (Str::contains($e->getMessage(), ['does not exist', 'file_get_contents'])) {
                DB::commit();
                $response = [
                    'error' => false,
                    'message' => 'Data Stored successfully. But App push notification not send.',
                ];
            } else {
                DB::rollBack();
                report($e);
                $response = [
                    'error' => true,
                    'message' => trans('error_occurred'),
                ];
            }
        }

        return response()->json($response);
    }

    public function feesPaidRemoveInstallmentFees($id)
    {
        if (! Auth::user()->can('fees-paid')) {
            $response = [
                'message' => trans('no_permission_message'),
            ];

            return redirect(route('home'))->withErrors($response);
        }
        try {
            $paid_installment_fee_db = PaidInstallmentFee::find($id);

            // Get Detials
            $student_id = $paid_installment_fee_db->student_id;
            $class_id = $paid_installment_fee_db->class_id;
            $session_year_id = $paid_installment_fee_db->session_year_id;
            $installment_fee_amount = $paid_installment_fee_db->money('amount') ?? Money::zero();
            $fees_payment_transaction_id = $paid_installment_fee_db->payment_transaction_id;

            DB::beginTransaction();

            // Delete Fees Installment Paid Entry
            $paid_installment_fee_db->delete();

            // Check the Payment Transaction ID Is Not Null
            if (isset($fees_payment_transaction_id) && ! empty($fees_payment_transaction_id)) {
                // Get the Payment Transaction Amount
                $fees_transaction_amount = PaymentTransaction::find($fees_payment_transaction_id)?->money('total_amount') ?? Money::zero();

                // Reduce the amount of Deleted Choiceable Fees in Payment Transaction
                $updated_transaction_fees_amount = $fees_transaction_amount->minus($installment_fee_amount);

                // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
                if (! $updated_transaction_fees_amount->isZero()) {
                    $fees_transaction_update = PaymentTransaction::find($fees_payment_transaction_id);
                    $fees_transaction_update->total_amount_cents = $updated_transaction_fees_amount;
                    $fees_transaction_update->save();
                } else {
                    $fees_transaction_update = PaymentTransaction::where('id', $fees_payment_transaction_id)->delete();
                }
            }

            // Check the Fees Paid Entry
            $fees_paid_db = FeesPaid::where(['student_id' => $student_id, 'class_id' => $class_id, 'session_year_id' => $session_year_id]);
            // Get the Fees Paid ID
            $fees_paid_id = $fees_paid_db->pluck('id')->first();
            // Get the Fees Paid Amount
            $fees_paid_amount = FeesPaid::find($fees_paid_id)?->money('total_amount') ?? Money::zero();

            // Reduce the amount of Deleted Choiceable Fees in Fees Paid
            $updated_fees_paid_amount = $fees_paid_amount->minus($installment_fee_amount);

            // If Updated Fees Amount is not Zero then update the Total Amount Else Delete the entry
            if (! $updated_fees_paid_amount->isZero()) {
                $fees_paid_update = FeesPaid::find($fees_paid_id);
                $fees_paid_update->total_amount_cents = $updated_fees_paid_amount;
                $fees_paid_update->is_fully_paid = 0;
                $fees_paid_update->save();
            } else {
                $fees_paid_update = FeesPaid::where('id', $fees_paid_id)->delete();
            }
            DB::commit();

            $response = [
                'error' => false,
                'message' => trans('data_delete_successfully'),
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            $response = [
                'error' => true,
                'message' => trans('error_occurred'),
            ];
        }

        return response()->json($response);
    }
}
