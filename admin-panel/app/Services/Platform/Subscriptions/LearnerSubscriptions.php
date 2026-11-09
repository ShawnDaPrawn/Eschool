<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\Parents;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\Platform\Subscriptions\LearnerSubscriptionEvent;
use App\Models\Platform\Subscriptions\LearnerSubscriptionPayment;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Learner subscriptions (Action Plan 3.20): what a guardian sees and does for
 * their own children at this school, and the one place a paid period is
 * recorded (by HQ Finance from an EFT, or by a gateway notice).
 *
 * A guardian asks for a learning plan for one child; the first child is on
 * the per-child price and each further child of the same guardian at the same
 * school on the family price. The price is fixed when asked for. The
 * subscription waits as "requested" until a period is paid, and the
 * reference it carries is what the family quotes on their EFT. Cancelling
 * keeps what was paid for until the period ends.
 *
 * A paid period is recorded once per bank line or gateway charge across every
 * school (platform_bank_receipts), for exactly one period's price.
 * engineering/specs/platform/learner-subscriptions.md.
 */
class LearnerSubscriptions
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    // ---- the guardian ----

    /** Plans, each of the guardian's children here with their entitlement, and the guardian's own subscriptions. */
    public function overview(User $actor): array
    {
        $fresh = $this->guardianActor($actor);
        $parent = (int) (Parents::query()->where('user_id', $fresh->id)->value('id') ?? 0);
        $children = app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->with('user')->orderBy('id')->limit(51)->get();
        abort_if($children->count() > 50, 422, __('Too many children to show at once. Contact your school.'));
        $rows = [];
        foreach ($children as $child) {
            /** @var Students $child */
            if (! app(GuardianService::class)->guardianUserIds($child)->contains((int) $fresh->id)) {
                continue;
            }
            $rows[] = $this->child($fresh, $child);
        }
        $mine = LearnerSubscription::query()->where('guardian_user_id', $fresh->id)->orderByDesc('id')->limit(100)->get();

        return $this->envelope($fresh) + [
            'children' => $rows,
            'subscriptions' => $mine->map(fn (LearnerSubscription $s): array => $this->present($s, $fresh))->values()->all(),
        ];
    }

    /** One of the guardian's children: the plans and that child's entitlement. */
    public function learner(User $actor, int $learnerId): array
    {
        [$fresh, $learner] = $this->guardianOf($actor, $learnerId);

        return $this->envelope($fresh) + ['child' => $this->child($fresh, $learner)];
    }

    /** Ask for a learning plan for one child. Replaying the same operation_id returns the same subscription. */
    public function request(User $actor, int $learnerId, string $operationId): array
    {
        abort_unless(Str::isUuid($operationId), 422);
        [$fresh, $learner] = $this->guardianOf($actor, $learnerId, true);
        $result = DB::transaction(function () use ($fresh, $learner, $operationId): array {
            // One guardian at a time: the family price depends on their other children.
            User::query()->whereKey($fresh->id)->lockForUpdate()->first();
            $replay = LearnerSubscription::query()->where('guardian_user_id', $fresh->id)->where('request_key', $operationId)->lockForUpdate()->first();
            if ($replay !== null) {
                abort_unless((int) $replay->learner_id === (int) $learner->id, 409, __('This request was already used for another child.'));

                return [$replay, false];
            }
            $locked = Students::query()->whereKey($learner->id)->lockForUpdate()->firstOrFail();
            abort_unless(app(ApprovalService::class)->approved($locked), 409, __('Approve your child\'s account first.'));
            $entitlements = app(Entitlements::class);
            $entitlements->forget();
            abort_unless($entitlements->required(), 409, __('Learning plans are included at your child\'s school, so there is nothing to pay.'));
            $cover = $entitlements->forLearner($locked);
            abort_if($cover['entitled'] && in_array($cover['source'], ['school_licence', 'sponsored'], true), 409, __('Your child already has a learning plan through the school.'));
            $open = LearnerSubscription::query()->where('learner_id', $locked->id)->whereIn('status', [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE])->lockForUpdate()->exists();
            abort_if($open, 409, __('Your child already has a learning plan or a request waiting for payment.'));
            $plan = $this->nextPlan((int) $fresh->id, (int) $locked->id);
            $subscription = LearnerSubscription::query()->create([
                'reference' => $this->nextReference(), 'plan' => $plan, 'learner_id' => $locked->id, 'guardian_user_id' => $fresh->id,
                'status' => LearnerSubscription::REQUESTED, 'price_cents' => SubscriptionPricing::personal($plan), 'currency' => SubscriptionPricing::currency(),
                'period_months' => SubscriptionPricing::periodMonths(), 'provider' => 'manual', 'request_key' => $operationId, 'requested_by' => $fresh->id,
            ]);
            $this->event($subscription, 'requested', 'guardian', (int) $fresh->id, null, ['learner_id' => (int) $locked->id, 'plan' => $plan]);

            return [$subscription, true];
        }, 3);

        return $this->envelope($fresh) + ['created' => $result[1], 'subscription' => $this->present($result[0]->refresh(), $fresh)];
    }

    /**
     * Cancel the guardian's own subscription. A request not yet paid ends at
     * once; a paid one keeps its paid period. Replaying the same operation_id
     * changes nothing.
     */
    public function cancel(User $actor, int $subscriptionId, string $operationId): array
    {
        abort_unless(Str::isUuid($operationId), 422);
        $fresh = $this->guardianActor($actor);
        $subscription = DB::transaction(function () use ($fresh, $subscriptionId, $operationId): LearnerSubscription {
            $locked = LearnerSubscription::query()->whereKey($subscriptionId)->where('guardian_user_id', $fresh->id)->lockForUpdate()->first();
            abort_if($locked === null, 404);
            $learner = Students::query()->findOrFail((int) $locked->learner_id);
            abort_unless(app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id), 404);
            if ($locked->cancel_key === $operationId) {
                return $locked;
            }
            abort_unless(in_array($locked->status, [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE], true), 409, __('This learning plan is no longer running.'));
            $locked->forceFill(['status' => LearnerSubscription::CANCELLED, 'cancelled_at' => now(), 'cancelled_by' => $fresh->id, 'cancel_key' => $operationId])->save();
            $this->event($locked, 'cancelled', 'guardian', (int) $fresh->id, null, ['paid_until' => $locked->current_period_end?->toDateString()]);

            return $locked;
        }, 3);
        app(Entitlements::class)->forget();

        return $this->envelope($fresh) + ['subscription' => $this->present($subscription->refresh(), $fresh)];
    }

    // ---- paid periods (HQ Finance or a gateway) ----

    /**
     * Record one paid period of a subscription. Call inside the school's
     * transaction with the subscription locked. $payment: method (eft or
     * gateway), provider, source_reference, amount_cents, received_on.
     *
     * @param  array{method: string, provider: string, source_reference: string, amount_cents: int, received_on: string, provider_ref?: ?string}  $payment
     */
    public function recordPayment(LearnerSubscription $locked, array $payment, ?User $actor, string $actorKind): LearnerSubscriptionPayment
    {
        abort_unless(DB::transactionLevel() > 0, 500);
        abort_unless(in_array($locked->status, [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE], true), 409, __('Only a requested or running subscription can be paid for.'));
        if ($payment['amount_cents'] !== (int) $locked->price_cents) {
            abort(422, __('The amount must be exactly one period: :amount.', ['amount' => SubscriptionPricing::rands((int) $locked->price_cents)]));
        }
        $reference = self::normaliseReference($payment['source_reference']);
        abort_if($reference === '', 422, __('Give the bank reference.'));
        $this->assertReceiptFree($payment['method'], $reference);
        $today = app(AcademicAuthority::class)->today();
        $renewal = $locked->status === LearnerSubscription::ACTIVE && $locked->current_period_end !== null && $locked->current_period_end->toDateString() >= $today;
        $start = $renewal ? CarbonImmutable::parse($locked->current_period_end->toDateString())->addDay() : CarbonImmutable::parse($today);
        $end = $start->addMonthsNoOverflow((int) $locked->period_months)->subDay();
        try {
            $row = LearnerSubscriptionPayment::query()->create([
                'subscription_id' => $locked->id, 'method' => $payment['method'], 'provider' => $payment['provider'], 'source_reference' => $reference,
                'amount_cents' => $payment['amount_cents'], 'currency' => $locked->currency, 'received_on' => $payment['received_on'],
                'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(), 'recorded_by' => $actor?->id, 'created_at' => now(),
            ]);
        } catch (QueryException $e) {
            // Another request registered the same bank line between the check and the insert.
            if (in_array((string) ($e->errorInfo[1] ?? ''), ['1062', '1644'], true)) {
                abort(409, __('This bank line or charge is already recorded.'));
            }
            throw $e;
        }
        $first = $locked->status === LearnerSubscription::REQUESTED || $locked->current_period_end === null;
        $locked->forceFill([
            'status' => LearnerSubscription::ACTIVE, 'current_period_start' => $start->toDateString(), 'current_period_end' => $end->toDateString(),
            'activated_at' => $locked->activated_at ?? now(), 'provider' => $payment['provider'],
            'provider_ref' => $payment['provider_ref'] ?? $locked->provider_ref,
        ])->save();
        $this->event($locked, $first ? 'activated' : 'renewed', $actorKind, $actor?->id, $payment['amount_cents'], [
            'payment_id' => (int) $row->id, 'method' => $payment['method'], 'source_reference' => $reference,
            'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
        ]);
        app(Entitlements::class)->forget();

        return $row;
    }

    /** Upper case, single spaces, as the receipt registry stores it. */
    public static function normaliseReference(string $reference): string
    {
        return (string) preg_replace('/\s+/', ' ', strtoupper(trim($reference)));
    }

    /**
     * One bank line, one receipt, across every school: refused when the
     * registry already holds it, or when it stands as a school's (or a school
     * group's) invoice payment to Navigate Ed.
     */
    public function assertReceiptFree(string $method, string $reference): void
    {
        $used = DB::table('platform_bank_receipts')->where('method', $method)->where('source_reference', $reference)->lockForUpdate()->exists();
        if (! $used && $method === 'eft') {
            // The same bank account takes school invoice payments (SA.8 Build 1); those may be re-recorded after a reversal, so they are checked, not registered.
            // Current, locking reads; the registry's insert trigger checks the same in the database.
            $normal = "REGEXP_REPLACE(UPPER(TRIM(bank_reference)), '[[:space:]]+', ' ') = ?";
            $used = DB::table('platform_payments')->whereNull('reversed_at')->whereRaw($normal, [$reference])->sharedLock()->exists()
                || DB::table('school_group_payments')->whereNull('reversed_at')->whereRaw($normal, [$reference])->sharedLock()->exists();
        }
        abort_if($used, 409, __('This bank line or charge is already recorded.'));
    }

    public function event(LearnerSubscription $subscription, string $event, string $actorKind, ?int $actorId, ?int $amountCents, array $detail = []): void
    {
        LearnerSubscriptionEvent::query()->create([
            'subscription_id' => $subscription->id, 'event' => $event, 'actor_kind' => $actorKind, 'actor_user_id' => $actorId,
            'status_after' => $subscription->status, 'amount_cents' => $amountCents, 'detail' => $detail === [] ? null : $detail, 'created_at' => now(),
        ]);
    }

    /** The next subscription reference, NES-000001, under a row lock; never repeated. */
    public function nextReference(): string
    {
        $name = 'learner_subscription_reference';
        DB::table('platform_counters')->insertOrIgnore(['name' => $name, 'value' => 0]);
        $next = (int) DB::table('platform_counters')->where('name', $name)->lockForUpdate()->value('value') + 1;
        DB::table('platform_counters')->where('name', $name)->update(['value' => $next]);

        return strtoupper((string) config('subscriptions.reference_prefix', 'NES')).'-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /**
     * What a subscription looks like to the guardian who asked for it, or to
     * HQ (no names either way). effective_status adds "lapsed": paid periods
     * have run out without a renewal.
     */
    public function present(LearnerSubscription $s, ?User $guardian = null): array
    {
        $today = app(AcademicAuthority::class)->today();

        return [
            'id' => (int) $s->id, 'reference' => (string) $s->reference, 'plan' => (string) $s->plan, 'status' => (string) $s->status,
            'effective_status' => self::effectiveStatus($s, $today), 'learner_id' => $s->learner_id === null ? null : (int) $s->learner_id,
            'price_cents' => (int) $s->price_cents, 'currency' => (string) $s->currency, 'period_months' => (int) $s->period_months,
            'current_period_end' => $s->current_period_end?->toDateString(), 'requested_on' => $s->created_at?->toDateString(),
            'can_cancel' => $guardian !== null && (int) $s->guardian_user_id === (int) $guardian->id && in_array($s->status, [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE], true),
        ];
    }

    public static function effectiveStatus(LearnerSubscription $s, string $today): string
    {
        if (in_array($s->status, LearnerSubscription::PAID_STATUSES, true) && ($s->current_period_end === null || $s->current_period_end->toDateString() < $today)) {
            return $s->status === LearnerSubscription::ACTIVE ? 'lapsed' : 'ended';
        }

        return (string) $s->status;
    }

    // ---- authority ----

    /** An active Parent here, never platform staff, in a live school. */
    private function guardianActor(User $actor): User
    {
        $school = $this->currentSchool;
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::query()->where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 403);

        return $fresh;
    }

    /** @return array{0: User, 1: Students} the guardian and their own child at this school (404 otherwise) */
    private function guardianOf(User $actor, int $learnerId, bool $write = false): array
    {
        $fresh = $this->guardianActor($actor);
        $learner = Students::query()->find($learnerId);
        abort_if($learner === null, 404);
        abort_unless(app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id), 404);
        if ($write) {
            app(LeaverState::class)->guardFamilyWrite($learner, $fresh);
        }

        return [$fresh, $learner];
    }

    private function child(User $guardian, Students $learner): array
    {
        // The child's running plan (whoever asked for it), else this guardian's own cancelled one.
        $shown = LearnerSubscription::query()->where('learner_id', $learner->id)->whereIn('status', [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE])->orderByDesc('id')->first()
            ?? LearnerSubscription::query()->where('learner_id', $learner->id)->where('guardian_user_id', $guardian->id)->where('status', LearnerSubscription::CANCELLED)->orderByDesc('id')->first();
        $name = self::name($learner->user);

        return [
            'learner_id' => (int) $learner->id, 'name' => $name, 'approved' => app(ApprovalService::class)->approved($learner),
            'entitlement' => app(Entitlements::class)->forLearner($learner),
            'next_plan' => $this->nextPlan((int) $guardian->id, (int) $learner->id),
            'subscription' => $shown === null ? null : $this->present($shown, $guardian),
        ];
    }

    /** A learner's own name, for their guardian or their school. */
    public static function name(?Model $user): string
    {
        return $user === null ? '' : trim((string) $user->getAttribute('first_name').' '.(string) $user->getAttribute('last_name'));
    }

    private function envelope(User $fresh): array
    {
        return [
            'school_id' => $this->currentSchool->id(), 'reader_id' => (int) $fresh->id,
            'required' => app(Entitlements::class)->required(), 'pricing_confirmed' => (bool) config('subscriptions.pricing_confirmed', false),
            'currency' => SubscriptionPricing::currency(), 'plans' => SubscriptionPricing::guardianPlans(),
            'payment_instructions' => (string) config('subscriptions.eft_instructions', ''),
        ];
    }

    /** per_child for the guardian's first child with a plan here, family for each further one. */
    private function nextPlan(int $guardianId, int $learnerId): string
    {
        $today = app(AcademicAuthority::class)->today();
        $others = LearnerSubscription::query()->where('guardian_user_id', $guardianId)->where('learner_id', '<>', $learnerId)
            ->where(fn ($q) => $q->whereIn('status', [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE])
                ->orWhere(fn ($paid) => $paid->where('status', LearnerSubscription::CANCELLED)->whereDate('current_period_end', '>=', $today)))
            ->exists();

        return $others ? LearnerSubscription::FAMILY : LearnerSubscription::PER_CHILD;
    }
}
