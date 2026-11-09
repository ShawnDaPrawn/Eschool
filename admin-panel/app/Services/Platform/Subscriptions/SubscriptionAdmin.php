<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * HQ's side of learner subscriptions (Action Plan 3.20, SA.8 Build 3), at
 * /saas/subscriptions: every school's subscriptions by reference, plan and
 * status (never a learner's or guardian's name), recording the EFT that
 * pays a period, declining a request nobody paid, ending a subscription, and
 * setting up a school licence or a sponsor's places.
 *
 * billing.view reads; billing.manage changes (Owner, Finance). Each change
 * runs inside the school it belongs to, under the school's lock, and is
 * audited in the platform audit log and the subscription's own trail.
 */
class SubscriptionAdmin
{
    public const STATUSES = ['requested', 'active', 'lapsed', 'cancelled', 'ended', 'declined'];

    public function __construct(
        private readonly CurrentSchool $currentSchool,
        private readonly PlatformBilling $billing,
        private readonly LearnerSubscriptions $subscriptions,
    ) {}

    public static function paymentRules(): array
    {
        return [
            'amount' => ['required', 'string', 'regex:/^\d{1,8}(\.\d{1,2})?$/D'],
            'received_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.PlatformBilling::today()],
            'bank_reference' => ['required', 'string', 'min:2', 'max:96', 'regex:/^[A-Za-z0-9][A-Za-z0-9 ._:\/#-]{1,95}$/D'],
        ];
    }

    public static function reasonRules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:500']];
    }

    public static function licenceRules(): array
    {
        return ['amount' => ['required', 'string', 'regex:/^\d{1,8}(\.\d{1,2})?$/D']];
    }

    public static function sponsorshipRules(): array
    {
        return [
            'sponsor_name' => ['required', 'string', 'min:2', 'max:120'],
            'places' => ['required', 'integer', 'min:1', 'max:10000'],
            'amount' => ['required', 'string', 'regex:/^\d{1,8}(\.\d{1,2})?$/D'],
        ];
    }

    public static function filterRules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:'.implode(',', self::STATUSES)],
            'plan' => ['nullable', 'string', 'in:'.implode(',', LearnerSubscription::PLANS)],
            'school' => ['nullable', 'integer', 'min:1'],
            'reference' => ['nullable', 'string', 'max:24'],
            'month' => ['nullable', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/D'],
        ];
    }

    /** The HQ page: the month's revenue and the filtered list. */
    public function page(User $actor, array $input, int $page = 1): array
    {
        $this->authorise($actor, 'billing.view');
        $filters = Validator::make($input, self::filterRules())->validate();
        $month = PlatformBilling::month($filters['month'] ?? null);

        return [
            'month' => $month,
            'revenue' => app(SubscriptionRevenue::class)->summary($actor, $month),
            'filters' => $filters,
            'rows' => $this->list($filters, $page),
            'schools' => $this->currentSchool->acrossSchools(fn () => DB::table('schools')->orderBy('name')->get(['id', 'name'])->map(fn ($s): array => ['id' => (int) $s->id, 'name' => (string) $s->name])->all()),
            'can_manage' => app(PlatformStaffAccess::class)->can($actor, 'billing.manage'),
            'pricing_confirmed' => (bool) config('subscriptions.pricing_confirmed', false),
            'defaults' => ['licence_per_learner_cents' => SubscriptionPricing::licencePerLearner(), 'sponsored_place_cents' => SubscriptionPricing::sponsoredPlace()],
            'gateway' => config('subscriptions.gateway'),
        ];
    }

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    private function list(array $filters, int $page): LengthAwarePaginator
    {
        $today = PlatformBilling::today();
        $query = DB::table('learner_subscriptions as s')->join('schools', 'schools.id', '=', 's.school_id')
            ->when($filters['school'] ?? null, fn ($q, $school) => $q->where('s.school_id', (int) $school))
            ->when($filters['plan'] ?? null, fn ($q, $plan) => $q->where('s.plan', $plan))
            ->when($filters['reference'] ?? null, fn ($q, $reference) => $q->where('s.reference', strtoupper(trim((string) $reference))))
            ->when($filters['status'] ?? null, function ($q, $status) use ($today) {
                match ($status) {
                    'lapsed' => $q->where('s.status', 'active')->where('s.current_period_end', '<', $today),
                    'active' => $q->where('s.status', 'active')->where('s.current_period_end', '>=', $today),
                    default => $q->where('s.status', $status),
                };
            })
            ->orderByRaw("FIELD(s.status, 'requested') DESC")->orderByDesc('s.id')
            ->select(['s.id', 's.school_id', 'schools.name as school', 's.reference', 's.plan', 's.status', 's.price_cents', 's.currency', 's.places', 's.sponsor_name',
                's.current_period_end', 's.provider', 's.created_at'])
            ->selectSub(fn ($q) => $q->from('learner_subscription_members as m')->selectRaw('COUNT(*)')->whereColumn('m.school_id', 's.school_id')->whereColumn('m.subscription_id', 's.id')->whereNull('m.removed_at'), 'named');
        $rows = $this->currentSchool->acrossSchools(fn () => $query->paginate(50, ['*'], 'page', max(1, $page)));
        $rows->getCollection()->transform(function ($row) use ($today): array {
            $end = $row->current_period_end === null ? null : substr((string) $row->current_period_end, 0, 10);
            $status = (string) $row->status;
            if (in_array($status, ['active', 'cancelled'], true) && ($end === null || $end < $today)) {
                $status = $status === 'active' ? 'lapsed' : 'ended';
            }

            return ['id' => (int) $row->id, 'school_id' => (int) $row->school_id, 'school' => (string) $row->school, 'reference' => (string) $row->reference,
                'plan' => (string) $row->plan, 'status' => (string) $row->status, 'effective_status' => $status, 'price_cents' => (int) $row->price_cents,
                'currency' => (string) $row->currency, 'places' => $row->places === null ? null : (int) $row->places, 'named' => (int) $row->named,
                'sponsor_name' => $row->sponsor_name, 'current_period_end' => $end, 'provider' => (string) $row->provider,
                'requested_on' => substr((string) $row->created_at, 0, 10)];
        });

        return $rows;
    }

    /**
     * Record the EFT that pays one period: the first (activation) or the next
     * (renewal). The amount must be exactly one period's price; the bank line
     * may be used once, across every school.
     */
    public function recordEft(int $schoolId, int $subscriptionId, array $input, User $actor): int
    {
        $this->authorise($actor, 'billing.manage');
        $data = Validator::make($input, self::paymentRules())->validate();
        $amount = Money::fromDecimalString($data['amount'], SubscriptionPricing::currency())->cents;
        $reference = LearnerSubscriptions::normaliseReference((string) $data['bank_reference']);

        return $this->billing->inSchool($schoolId, function () use ($subscriptionId, $data, $amount, $reference, $actor): int {
            $locked = $this->lock($subscriptionId);
            if (! in_array($locked->status, [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE], true)) {
                throw ValidationException::withMessages(['subscription' => __('Only a requested or running subscription can be paid for.')]);
            }
            if ($amount !== (int) $locked->price_cents) {
                throw ValidationException::withMessages(['amount' => __('The amount must be exactly one period: :amount.', ['amount' => SubscriptionPricing::rands((int) $locked->price_cents)])]);
            }
            try {
                $this->subscriptions->assertReceiptFree('eft', $reference);
            } catch (HttpException) {
                throw ValidationException::withMessages(['bank_reference' => __('This bank line or charge is already recorded.')]);
            }
            $payment = $this->subscriptions->recordPayment($locked, ['method' => 'eft', 'provider' => 'manual', 'source_reference' => $reference,
                'amount_cents' => $amount, 'received_on' => $data['received_on']], $actor, 'hq');
            Audit::record('platform_subscription_payment_recorded', $locked, [], [
                'subscription_id' => (int) $locked->id, 'reference' => $locked->reference, 'payment_id' => (int) $payment->id, 'amount_cents' => $amount,
                'bank_reference' => $reference, 'received_on' => $data['received_on'], 'period_end' => $payment->period_end->toDateString(), 'changed_by' => $actor->id,
            ], (int) $locked->school_id);

            return (int) $payment->id;
        });
    }

    /** A request nobody paid for (or that cannot be matched): closed with a reason. */
    public function decline(int $schoolId, int $subscriptionId, array $input, User $actor): void
    {
        $this->close($schoolId, $subscriptionId, $input, $actor, LearnerSubscription::DECLINED, [LearnerSubscription::REQUESTED]);
    }

    /** End a running subscription at once (a sponsor withdrew, a refund): the entitlement stops today. */
    public function end(int $schoolId, int $subscriptionId, array $input, User $actor): void
    {
        $this->close($schoolId, $subscriptionId, $input, $actor, LearnerSubscription::ENDED, [LearnerSubscription::ACTIVE, LearnerSubscription::CANCELLED]);
    }

    /** A school licence for every learner at the school, at the agreed price per period; waits for its EFT. */
    public function createLicence(int $schoolId, array $input, User $actor): int
    {
        $this->authorise($actor, 'billing.manage');
        $data = Validator::make($input, self::licenceRules())->validate();
        $amount = $this->positive($data['amount']);

        return $this->billing->inSchool($schoolId, function () use ($amount, $actor): int {
            $open = LearnerSubscription::query()->where('plan', LearnerSubscription::SCHOOL_LICENCE)->whereIn('status', [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE])->lockForUpdate()->exists();
            if ($open) {
                throw ValidationException::withMessages(['amount' => __('This school already has a licence, or one waiting for payment.')]);
            }

            return $this->create(['plan' => LearnerSubscription::SCHOOL_LICENCE, 'price_cents' => $amount], $actor);
        });
    }

    /** A sponsor's places at a school, at the agreed price per period for all of them; the school names the learners. */
    public function createSponsorship(int $schoolId, array $input, User $actor): int
    {
        $this->authorise($actor, 'billing.manage');
        $data = Validator::make($input, self::sponsorshipRules())->validate();
        $amount = $this->positive($data['amount']);

        return $this->billing->inSchool($schoolId, fn (): int => $this->create([
            'plan' => LearnerSubscription::SPONSORED, 'price_cents' => $amount, 'places' => (int) $data['places'], 'sponsor_name' => trim((string) $data['sponsor_name']),
        ], $actor));
    }

    public function authorise(User $actor, string $ability): void
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, $ability), 403);
    }

    private function create(array $values, User $actor): int
    {
        $subscription = LearnerSubscription::query()->create($values + [
            'reference' => $this->subscriptions->nextReference(), 'status' => LearnerSubscription::REQUESTED, 'currency' => SubscriptionPricing::currency(),
            'period_months' => SubscriptionPricing::periodMonths(), 'provider' => 'manual', 'requested_by' => $actor->id,
        ]);
        $this->subscriptions->event($subscription, 'requested', 'hq', (int) $actor->id, null, ['plan' => $subscription->plan, 'places' => $subscription->places]);
        Audit::record('platform_subscription_created', $subscription, [], [
            'subscription_id' => (int) $subscription->id, 'reference' => $subscription->reference, 'plan' => $subscription->plan,
            'price_cents' => (int) $subscription->price_cents, 'places' => $subscription->places, 'sponsor_name' => $subscription->sponsor_name, 'changed_by' => $actor->id,
        ], (int) $subscription->school_id);

        return (int) $subscription->id;
    }

    /** @param  list<string>  $from */
    private function close(int $schoolId, int $subscriptionId, array $input, User $actor, string $to, array $from): void
    {
        $this->authorise($actor, 'billing.manage');
        $reason = (string) Validator::make($input, self::reasonRules())->validate()['reason'];
        $this->billing->inSchool($schoolId, function () use ($subscriptionId, $reason, $actor, $to, $from): void {
            $locked = $this->lock($subscriptionId);
            if (! in_array($locked->status, $from, true)) {
                throw ValidationException::withMessages(['subscription' => $to === LearnerSubscription::DECLINED
                    ? __('Only a request waiting for payment can be declined.') : __('Only a running subscription can be ended.')]);
            }
            $before = $locked->status;
            $locked->forceFill(['status' => $to, 'closed_at' => now(), 'closed_by' => $actor->id, 'close_reason' => $reason])->save();
            $this->subscriptions->event($locked, $to, 'hq', (int) $actor->id, null, ['reason' => $reason]);
            Audit::record('platform_subscription_'.$to, $locked, ['status' => $before], [
                'subscription_id' => (int) $locked->id, 'reference' => $locked->reference, 'status' => $to, 'reason' => $reason, 'changed_by' => $actor->id,
            ], (int) $locked->school_id);
        });
        app(Entitlements::class)->forget();
    }

    private function lock(int $subscriptionId): LearnerSubscription
    {
        $locked = LearnerSubscription::query()->whereKey($subscriptionId)->lockForUpdate()->first();
        abort_if($locked === null, 404);

        return $locked;
    }

    private function positive(string $rands): int
    {
        $cents = Money::fromDecimalString($rands, SubscriptionPricing::currency())->cents;
        if ($cents < 1) {
            throw ValidationException::withMessages(['amount' => __('The amount must be more than R 0.00.')]);
        }

        return $cents;
    }
}
