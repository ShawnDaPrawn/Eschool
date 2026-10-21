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
 * Payment gateway webhooks. Stripe is the only gateway; Razorpay, Paystack and
 * Flutterwave were removed in Phase 0 and their webhook URLs answer 404.
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
