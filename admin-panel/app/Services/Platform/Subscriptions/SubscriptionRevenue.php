<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\User;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Subscription revenue for one month (SA.8, Build 3): what was collected,
 * monthly recurring revenue and its annual run rate, families, revenue per
 * family and per paying learner, and churn, overall and per school. Integer
 * cents and basis points only; counts, never a person.
 *
 * - Collected: payments received in the month.
 * - Running on the month's last day (or today, for this month): subscriptions
 *   with a recorded payment whose period covers that day and that HQ had not
 *   ended by then. MRR is that payment per month; the annual run rate is MRR x 12.
 *   Built from the immutable payments and the subscription's own trail, so a
 *   later renewal, cancellation or end never changes an earlier month.
 * - Churn: paid subscriptions first cancelled or ended in the month (their
 *   trail), against those running on the month's first day.
 */
class SubscriptionRevenue
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    public function summary(User $actor, string $month): array
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'billing.view'), 403);
        $first = CarbonImmutable::parse($month.'-01');
        $last = $first->endOfMonth()->toDateString();
        $today = PlatformBilling::today();
        $day = $last > $today && $first->toDateString() <= $today ? $today : $last;

        return $this->currentSchool->acrossSchools(function () use ($first, $last, $day, $month): array {
            $collected = DB::table('learner_subscription_payments as p')->join('learner_subscriptions as s', fn ($j) => $j->on('s.school_id', '=', 'p.school_id')->on('s.id', '=', 'p.subscription_id'))
                ->whereBetween('p.received_on', [$first->toDateString(), $last])
                ->groupBy('p.school_id', 's.plan')->selectRaw('p.school_id, s.plan, SUM(p.amount_cents) AS cents, COUNT(*) AS payments')->get();
            $running = $this->running($day);
            $atStart = count($this->running($first->toDateString()));
            $churned = $this->churned($first->startOfDay(), $first->addMonthNoOverflow()->startOfDay());
            $schools = DB::table('schools')->whereIn('id', $collected->pluck('school_id')->merge(collect($running)->pluck('school_id'))->unique()->all())->pluck('name', 'id');

            $plans = array_fill_keys(['per_child', 'family', 'school_licence', 'sponsored'], ['running' => 0, 'mrr_cents' => 0, 'collected_cents' => 0]);
            $perSchool = [];
            $families = [];
            $paying = 0;
            $payingMrr = 0;
            foreach ($running as $row) {
                $monthly = intdiv((int) $row->amount_cents, max(1, (int) $row->period_months));
                $plans[$row->plan]['running']++;
                $plans[$row->plan]['mrr_cents'] += $monthly;
                $school = $this->school($perSchool, (int) $row->school_id, (string) ($schools[$row->school_id] ?? ''));
                $perSchool[$school]['running']++;
                $perSchool[$school]['mrr_cents'] += $monthly;
                if (in_array($row->plan, ['per_child', 'family'], true)) {
                    $families[(int) $row->guardian_user_id] = true;
                    $paying++;
                    $payingMrr += $monthly;
                } elseif ($row->plan === 'sponsored') {
                    $paying += (int) $row->places;
                    $payingMrr += $monthly;
                }
            }
            foreach ($collected as $row) {
                $plans[$row->plan]['collected_cents'] += (int) $row->cents;
                $school = $this->school($perSchool, (int) $row->school_id, (string) ($schools[$row->school_id] ?? ''));
                $perSchool[$school]['collected_cents'] += (int) $row->cents;
            }
            $mrr = array_sum(array_column($plans, 'mrr_cents'));
            $familyMrr = $plans['per_child']['mrr_cents'] + $plans['family']['mrr_cents'];
            usort($perSchool, fn (array $a, array $b): int => [$a['school'], $a['school_id']] <=> [$b['school'], $b['school_id']]);

            return [
                'month' => $month, 'as_of' => $day, 'currency' => SubscriptionPricing::currency(),
                'collected_cents' => array_sum(array_column($plans, 'collected_cents')),
                'mrr_cents' => $mrr, 'arr_cents' => $mrr * 12,
                'running' => array_sum(array_column($plans, 'running')),
                'families' => count($families), 'per_family_cents' => $families === [] ? 0 : intdiv($familyMrr, count($families)),
                'paying_learners' => $paying, 'per_paying_learner_cents' => $paying === 0 ? 0 : intdiv($payingMrr, $paying),
                'churn' => ['cancelled_or_ended' => $churned, 'running_at_start' => $atStart, 'rate_bp' => $atStart === 0 ? 0 : intdiv($churned * 10000, $atStart)],
                'plans' => $plans,
                'schools' => $perSchool,
                'pricing_confirmed' => (bool) config('subscriptions.pricing_confirmed', false),
            ];
        });
    }

    /**
     * Subscriptions running on a day, from the immutable paid periods: a payment
     * whose period covers the day, unless HQ had ended the subscription by then
     * (its own trail). A later renewal or end never rewrites an earlier day, an
     * early renewal never hides the period being used now, and a cancelled
     * subscription runs until its paid period ends. One row per subscription.
     *
     * @return array<int, object{school_id: int, plan: string, amount_cents: int, period_months: int, places: int|null, guardian_user_id: int|null}>
     */
    private function running(string $day): array
    {
        $next = CarbonImmutable::parse($day)->addDay()->startOfDay();
        $rows = DB::table('learner_subscription_payments as p')->join('learner_subscriptions as s', fn ($j) => $j->on('s.school_id', '=', 'p.school_id')->on('s.id', '=', 'p.subscription_id'))
            ->where('p.period_start', '<=', $day)->where('p.period_end', '>=', $day)
            ->whereNotExists(fn ($ended) => $ended->selectRaw('1')->from('learner_subscription_events as e')->whereColumn('e.school_id', 's.school_id')
                ->whereColumn('e.subscription_id', 's.id')->where('e.event', 'ended')->where('e.created_at', '<', $next))
            ->orderBy('p.id')->get(['s.id', 's.school_id', 's.plan', 'p.amount_cents', 's.period_months', 's.places', 's.guardian_user_id']);
        $running = [];
        foreach ($rows as $row) {
            $running[(int) $row->id] ??= $row;
        }

        return $running;
    }

    /**
     * Paid subscriptions first cancelled or ended (their own trail) in the window.
     */
    private function churned(CarbonImmutable $from, CarbonImmutable $until): int
    {
        $stops = DB::table('learner_subscription_events as e')->whereIn('e.event', ['cancelled', 'ended'])->where('e.created_at', '<', $until)
            ->groupBy('e.school_id', 'e.subscription_id')->selectRaw('e.school_id, e.subscription_id, MIN(e.created_at) AS stopped_at')->get();
        $count = 0;
        foreach ($stops as $stop) {
            $stopped = CarbonImmutable::parse((string) $stop->stopped_at);
            if ($stopped->lt($from)) {
                continue;
            }
            $paid = DB::table('learner_subscription_payments')->where('school_id', $stop->school_id)->where('subscription_id', $stop->subscription_id)
                ->where('created_at', '<=', $stopped)->exists();
            $count += $paid ? 1 : 0;
        }

        return $count;
    }

    /** @param  array<int, array<string, mixed>>  $perSchool */
    private function school(array &$perSchool, int $id, string $name): int
    {
        $perSchool[$id] ??= ['school_id' => $id, 'school' => $name, 'running' => 0, 'mrr_cents' => 0, 'collected_cents' => 0];

        return $id;
    }
}
