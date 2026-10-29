<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Financial balance alone does not prove a complete counter authorization receipt. */
final class WalletCounterReconciliation
{
    public function inspect(): array
    {
        $report = ['counter_payment_mismatches' => 0, 'incomplete_counter_orders' => 0];
        if (! Schema::hasTable('wallet_counter_payments')) {
            return $report;
        }
        if (! Schema::hasColumn('vendor_orders', 'wallet_intent_id')) {
            $report['counter_payment_mismatches']++;

            return $report;
        }
        $school = app(CurrentSchool::class)->id();
        $orders = DB::table('vendor_orders')->where('school_id', $school)->where('purchase_channel', 'counter')->get()->keyBy('id');
        $payments = DB::table('wallet_counter_payments')->where('school_id', $school)->get()->keyBy('order_id');
        $intents = DB::table('wallet_payment_intents')->where('school_id', $school)->whereIn('id', $orders->pluck('wallet_intent_id'))->get()->keyBy('id');
        $actions = DB::table('vendor_order_actions')->where('school_id', $school)->whereIn('order_id', $orders->keys())->get()->keyBy('order_id');
        foreach ($orders as $order) {
            $payment = $payments->get($order->id);
            $intent = $intents->get($order->wallet_intent_id);
            $action = $actions->get($order->id);
            if ($payment === null) {
                $report['incomplete_counter_orders']++;

                continue;
            }
            $sound = $intent !== null && $action !== null && $order->sealed_at !== null
                && $payment->intent_id === $intent->id && (int) $payment->vendor_id === (int) $order->vendor_id
                && (int) $intent->vendor_id === (int) $order->vendor_id
                && (int) $intent->wallet_account_id === (int) $order->wallet_account_id
                && (int) $intent->actor_id === (int) $order->actor_id && $intent->currency === $order->currency
                && (int) $intent->amount_cents === (int) $order->amount_cents
                && (int) $intent->net_cents === (int) $order->net_cents && (int) $intent->vat_cents === (int) $order->vat_cents
                && hash_equals($intent->fingerprint, $order->quote_fingerprint)
                && $action->action === 'collected' && (int) $action->actor_id === (int) $payment->actor_id
                && $payment->created_at < $intent->expires_at;
            if (! $sound) {
                $report['counter_payment_mismatches']++;
            }
        }
        foreach ($payments as $payment) {
            if (! $orders->has($payment->order_id)) {
                $report['counter_payment_mismatches']++;
            }
        }

        return $report;
    }

    public static function healthy(array $report): bool
    {
        return $report['counter_payment_mismatches'] === 0 && $report['incomplete_counter_orders'] === 0;
    }
}
