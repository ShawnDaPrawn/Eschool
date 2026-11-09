<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Services\Platform\Subscriptions\Gateways\GatewayEvent;
use App\Services\Platform\Subscriptions\Gateways\SubscriptionGateways;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * A payment gateway's notices about learner subscriptions, as posted to
 * POST /webhook/subscriptions/{gateway} (Action Plan 3.20), following the
 * other signed webhooks (Twilio, SES).
 *
 * Acted on only when the gateway is the configured one and its adapter
 * verifies the signature. Each notice is recorded once by its id
 * (subscription_gateway_events), in the same transaction as its effect, so a
 * repeated notice changes nothing. A paid period goes through
 * LearnerSubscriptions::recordPayment, like an EFT: exactly one period's
 * price, one charge one receipt across every school. An unknown reference,
 * or an amount that is not one period, is recorded and ignored (200), so the
 * gateway does not retry it; HQ sees it in the log.
 */
class SubscriptionWebhooks
{
    /**
     * @param  array<string, string>  $headers  lower-case names, first value each
     * @return array{0: int, 1: string} HTTP status and a short message
     */
    public function handle(string $provider, string $rawBody, array $headers): array
    {
        $gateway = app(SubscriptionGateways::class)->resolve($provider);
        if ($gateway === null) {
            return $this->reject(404, 'Not configured');
        }
        $event = $gateway->verify($rawBody, $headers);
        if ($event === null) {
            return $this->reject(401, 'Invalid signature');
        }
        if (! in_array($event->type, [GatewayEvent::PAYMENT_SUCCEEDED, GatewayEvent::SUBSCRIPTION_CANCELLED], true)) {
            return $this->once($provider, $event, null, fn (): string => 'ignored_type');
        }
        $reference = strtoupper(trim($event->reference));
        $schoolId = app(CurrentSchool::class)->acrossSchools(fn () => DB::table('learner_subscriptions')->where('reference', $reference)->value('school_id'));
        if ($schoolId === null) {
            return $this->once($provider, $event, null, fn (): string => 'unknown_reference');
        }

        return app(CurrentSchool::class)->run((int) $schoolId, fn (): array => $this->once($provider, $event, (int) $schoolId, function () use ($provider, $event, $reference): string {
            $locked = LearnerSubscription::query()->where('reference', $reference)->lockForUpdate()->firstOrFail();
            if ($event->type === GatewayEvent::SUBSCRIPTION_CANCELLED) {
                if (! in_array($locked->status, [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE], true)) {
                    return 'nothing_to_cancel';
                }
                $locked->forceFill(['status' => LearnerSubscription::CANCELLED, 'cancelled_at' => now()])->save();
                app(LearnerSubscriptions::class)->event($locked, 'cancelled', 'gateway', null, null, ['provider' => $provider, 'event_id' => $event->id]);
                app(Entitlements::class)->forget();

                return 'cancelled';
            }
            if ($event->chargeId === null || $event->amountCents === null || $event->currency !== $locked->currency || $event->amountCents !== (int) $locked->price_cents) {
                return 'refused_amount';
            }
            try {
                app(LearnerSubscriptions::class)->recordPayment($locked, [
                    'method' => 'gateway', 'provider' => $provider, 'source_reference' => strtoupper($provider.':'.$event->chargeId),
                    'amount_cents' => $event->amountCents, 'received_on' => $event->paidOn ?? now()->toDateString(), 'provider_ref' => $event->providerRef,
                ], null, 'gateway');
            } catch (HttpExceptionInterface $refused) {
                return $refused->getStatusCode() === 409 ? 'refused_duplicate' : 'refused';
            }

            return 'paid';
        }));
    }

    /**
     * Records the notice and its outcome once, with its effect, in one transaction.
     *
     * @param  callable(): string  $apply
     * @return array{0: int, 1: string}
     */
    private function once(string $provider, GatewayEvent $event, ?int $schoolId, callable $apply): array
    {
        return DB::transaction(function () use ($provider, $event, $schoolId, $apply): array {
            $seen = DB::table('subscription_gateway_events')->where('provider', $provider)->where('event_id', $event->id)->lockForUpdate()->exists();
            if ($seen) {
                return [200, 'Already processed'];
            }
            $outcome = $apply();
            DB::table('subscription_gateway_events')->insert(['provider' => $provider, 'event_id' => mb_substr($event->id, 0, 128), 'type' => mb_substr($event->type, 0, 32),
                'reference' => mb_substr(strtoupper(trim($event->reference)), 0, 24) ?: null, 'outcome' => $outcome, 'received_at' => now()]);
            Log::info('subscription_gateway_event', ['provider' => $provider, 'type' => $event->type, 'outcome' => $outcome, 'school_id' => $schoolId]);

            return [200, in_array($outcome, ['paid', 'cancelled'], true) ? 'Recorded' : 'Ignored'];
        }, 3);
    }

    /** @return array{0: int, 1: string} */
    private function reject(int $status, string $reason): array
    {
        Log::warning('subscription_gateway_rejected', ['status' => $status, 'reason' => $reason]);

        return [$status, $reason];
    }
}
