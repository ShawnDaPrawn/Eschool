<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Parents;
use App\Models\PaymentTransaction;
use App\Models\UserNotification;
use App\Services\Payment\FeesPaymentService;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Throwable;
use UnexpectedValueException;

/**
 * Payment gateway webhooks.
 *
 * Each one checks the gateway's signature against the configured secret (and
 * refuses the call when no secret is configured), finds the transaction this
 * app created for that gateway order, checks the amount and currency paid
 * against it, and only then records the payment. What a payment covers comes
 * from the stored transaction and its rows, never from the webhook metadata.
 * Recording is idempotent, so retried or duplicate webhooks are harmless.
 */
class WebhookController extends Controller
{
    //razorpay webhooks
    public function razorpay(Request $request, FeesPaymentService $fees)
    {
        $payload = $request->getContent();
        if (! $this->validHmac('sha256', $payload, PaymentService::webhookSecret('razorpay'), $request->header('X-Razorpay-Signature'))) {
            return $this->reject('Razorpay', 'Invalid signature', 401);
        }

        $data = json_decode($payload);
        $event = $data->event ?? null;
        $payment = $data->payload->payment->entity ?? null;
        if (! in_array($event, ['payment.captured', 'payment.failed'], true) || ! is_object($payment)) {
            return $this->ok('Event ignored');
        }

        $transaction = $this->findTransaction(PaymentService::RAZORPAY, $payment->order_id ?? null);
        if (! $transaction) {
            return $this->unknownTransaction('Razorpay', $payment->order_id ?? null);
        }

        $attributes = ['payment_id' => isset($payment->id) ? (string) $payment->id : null];
        if ($event === 'payment.captured') {
            return $this->paid($fees, $transaction, $payment->amount ?? null, $payment->currency ?? null, $attributes, 'Razorpay');
        }

        return $this->failed($fees, $transaction, $attributes);
    }

    public function stripe(Request $request, FeesPaymentService $fees)
    {
        $secret = PaymentService::webhookSecret('stripe');
        if ($secret === '') {
            return $this->reject('Stripe', 'Webhook secret not configured', 401);
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature', ''), $secret);
        } catch (UnexpectedValueException) {
            return $this->reject('Stripe', 'Invalid payload', 400);
        } catch (SignatureVerificationException) {
            return $this->reject('Stripe', 'Invalid signature', 401);
        }

        if (! in_array($event->type, ['payment_intent.succeeded', 'payment_intent.payment_failed'], true)) {
            return $this->ok('Event ignored');
        }

        $intent = $event->data->object;
        $transaction = $this->findTransaction(PaymentService::STRIPE, $intent->id ?? null);
        if (! $transaction) {
            return $this->unknownTransaction('Stripe', $intent->id ?? null);
        }

        if ($event->type === 'payment_intent.succeeded') {
            return $this->paid($fees, $transaction, $intent->amount_received ?? null, $intent->currency ?? null, [], 'Stripe');
        }

        return $this->failed($fees, $transaction, []);
    }

    public function paystack(Request $request, FeesPaymentService $fees)
    {
        $payload = $request->getContent();
        if (! $this->validHmac('sha512', $payload, PaymentService::webhookSecret('paystack'), $request->header('x-paystack-signature'))) {
            return $this->reject('Paystack', 'Invalid signature', 401);
        }

        $event = json_decode($payload);
        $charge = $event->data ?? null;
        if (($event->event ?? null) !== 'charge.success' || ! is_object($charge)) {
            return $this->ok('Event ignored');
        }

        // The reference is what the transaction was created with (its order_id).
        $transaction = $this->findTransaction(PaymentService::PAYSTACK, $charge->reference ?? null);
        if (! $transaction) {
            return $this->unknownTransaction('Paystack', $charge->reference ?? null);
        }

        return $this->paid($fees, $transaction, $charge->amount ?? null, $charge->currency ?? null, [
            'payment_id' => isset($charge->id) ? (string) $charge->id : null,
        ], 'Paystack');
    }

    public function flutterwave(Request $request, FeesPaymentService $fees)
    {
        // Flutterwave sends the secret hash set on its dashboard in this header.
        $secretHash = PaymentService::webhookSecret('flutterwave');
        $signature = (string) $request->header('verif-hash', '');
        if ($secretHash === '' || $signature === '' || ! hash_equals($secretHash, $signature)) {
            return $this->reject('Flutterwave', 'Invalid signature', 401);
        }

        $data = json_decode($request->getContent());
        $payment = $data->data ?? null;
        $metadata = $data->meta_data ?? ($payment->meta ?? null);
        $payment_transaction_id = $metadata->payment_transaction_id ?? null;
        if (! is_object($payment) || ! is_numeric($payment_transaction_id)) {
            return $this->ok('Event ignored');
        }

        $transaction = PaymentTransaction::where('id', (int) $payment_transaction_id)
            ->where('payment_gateway', PaymentService::FLUTTERWAVE)
            ->first();
        if (! $transaction) {
            return $this->unknownTransaction('Flutterwave', $payment_transaction_id);
        }

        $attributes = ['payment_id' => isset($payment->id) ? (string) $payment->id : null];
        $status = $payment->status ?? null;
        if ($status === 'successful') {
            return $this->paid($fees, $transaction, $payment->amount ?? null, $payment->currency ?? null, $attributes, 'Flutterwave');
        }
        if ($status === 'failed') {
            return $this->failed($fees, $transaction, $attributes);
        }

        return $this->ok('Event ignored');
    }

    // Landing pages the payment web view is sent back to. They change nothing:
    // payments are only recorded from the signed webhooks above.
    public function paystackSuccessCallback()
    {
        $response = array(
            'error' => false,
            'message' => "Payment Successfully Completed",
            'code' => 200,
        );
        return response()->json($response);
    }

    public function flutterwaveSuccessCallback()
    {
        $response = array(
            'error' => false,
            'message' => "Payment Successfully Completed",
            'code' => 200,
        );
        return response()->json($response);
    }

    private function validHmac(string $algorithm, string $payload, string $secret, $signature): bool
    {
        if ($secret === '' || ! is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac($algorithm, $payload, $secret), $signature);
    }

    private function findTransaction(int $gateway, $order_id): ?PaymentTransaction
    {
        if (! is_string($order_id) || $order_id === '') {
            return null;
        }

        return PaymentTransaction::where('payment_gateway', $gateway)->where('order_id', $order_id)->first();
    }

    /**
     * Record a successful payment once its amount and currency match the
     * transaction we created.
     */
    private function paid(FeesPaymentService $fees, PaymentTransaction $transaction, $amount, $currency, array $attributes, string $gateway): JsonResponse
    {
        $expected_currency = PaymentService::currencyCode((int) $transaction->payment_gateway);
        if (! is_string($currency) || strcasecmp($currency, $expected_currency) !== 0
            || ! PaymentService::amountMatches((int) $transaction->payment_gateway, $expected_currency, $transaction->total_amount, $amount)) {
            Log::error($gateway.' webhook: amount paid does not match the transaction', [
                'payment_transaction_id' => $transaction->id,
                'amount' => $amount,
                'currency' => $currency,
            ]);

            return response()->json(['error' => true, 'message' => 'Amount does not match the transaction'], 400);
        }

        $paid = $fees->markPaid($transaction->id, array_filter($attributes, fn ($value) => $value !== null));
        if ($paid) {
            $this->notifyParents($paid, 'Payment Success');
        }

        return $this->ok($paid ? 'Payment Successfully Completed' : 'Payment already recorded');
    }

    private function failed(FeesPaymentService $fees, PaymentTransaction $transaction, array $attributes): JsonResponse
    {
        $failed = $fees->markFailed($transaction->id, $attributes);
        if ($failed) {
            $this->notifyParents($failed, 'Payment Failed');
        }

        return $this->ok('Payment failure recorded');
    }

    private function notifyParents(PaymentTransaction $transaction, string $title): void
    {
        try {
            $user = Parents::where('id', $transaction->parent_id)->pluck('user_id');
            $body = 'Amount :- '.$transaction->total_amount;
            $type = 'online';

            $notification = new Notification;
            $notification->send_to = 2;
            $notification->session_year_id = getSettings('session_year')['session_year'] ?? $transaction->session_year_id;
            $notification->title = $title;
            $notification->message = $body;
            $notification->type = $type;
            $notification->date = Carbon::now();
            $notification->is_custom = 0;
            $notification->save();

            foreach ($user as $user_id) {
                $user_notification = new UserNotification;
                $user_notification->notification_id = $notification->id;
                $user_notification->user_id = $user_id;
                $user_notification->save();
            }

            sendSimpleNotification($user, $title, $body, $type, null, null);
        } catch (Throwable $e) {
            // The payment is recorded; a failed notification must not undo that.
            Log::error('Payment notification failed: '.$e->getMessage());
        }
    }

    private function ok(string $message): JsonResponse
    {
        return response()->json(['error' => false, 'message' => $message]);
    }

    private function reject(string $gateway, string $message, int $status): JsonResponse
    {
        Log::warning($gateway.' webhook rejected: '.$message);

        return response()->json(['error' => true, 'message' => $message], $status);
    }

    /**
     * Not one of our transactions (e.g. another integration on the same gateway
     * account). Acknowledged so the gateway doesn't keep retrying it.
     */
    private function unknownTransaction(string $gateway, $reference): JsonResponse
    {
        Log::warning($gateway.' webhook: no matching payment transaction', ['reference' => $reference]);

        return $this->ok('Transaction not found');
    }
}
