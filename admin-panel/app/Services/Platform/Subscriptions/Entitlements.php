<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\Platform\Subscriptions\LearnerSubscriptionMember;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\FeatureFlags;
use App\Services\Platform\RequestContext;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Who may use the paid Mastery features (Action Plan 3.20): the tutor (the
 * modules marked 'paid' in config/modules.php) and the parent insights (a
 * guardian's view of a child's mastery map, learning path, starting point and
 * tutor summary). Daily practice is never asked about here: it stays free for
 * every learner.
 *
 * Nothing is required until the school's platform flag
 * (config subscriptions.flag) is on. Then a learner is entitled, on the
 * school's today, by any of:
 * - the school's licence, paid for and within its period (every learner);
 * - a sponsor's places, paid for and within their period, that the school
 *   named the learner for;
 * - the learner's own subscription (per child or family), paid for and within
 *   its period. A cancelled subscription keeps its paid period.
 *
 * Only this school's rows are read (every model is school-scoped).
 */
class Entitlements
{
    /** @var array<string, mixed> */
    private array $jobCache = [];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** Whether this school asks for an entitlement at all (its subscriptions flag). */
    public function required(): bool
    {
        $schoolId = $this->currentSchool->id();

        return (bool) $this->remember('subscriptions.required.'.$schoolId, fn (): bool => app(FeatureFlags::class)->enabled((string) config('subscriptions.flag'), $schoolId));
    }

    /**
     * @return array{required: bool, entitled: bool, source: ?string, until: ?string}
     *                                                                                source: included (nothing required), school_licence, sponsored or subscription
     */
    public function forLearner(Students $learner): array
    {
        abort_unless((int) $learner->getAttribute('school_id') === $this->currentSchool->id(), 404);
        if (! $this->required()) {
            return ['required' => false, 'entitled' => true, 'source' => 'included', 'until' => null];
        }
        $today = app(AcademicAuthority::class)->today();
        $found = [];
        $licence = LearnerSubscription::query()->where('plan', LearnerSubscription::SCHOOL_LICENCE)->whereIn('status', LearnerSubscription::PAID_STATUSES)
            ->whereDate('current_period_end', '>=', $today)->max('current_period_end');
        if ($licence !== null) {
            $found['school_licence'] = substr((string) $licence, 0, 10);
        }
        $sponsored = LearnerSubscription::query()->where('plan', LearnerSubscription::SPONSORED)->whereIn('status', LearnerSubscription::PAID_STATUSES)
            ->whereDate('current_period_end', '>=', $today)
            ->whereIn('id', LearnerSubscriptionMember::query()->where('learner_id', $learner->id)->whereNull('removed_at')->select('subscription_id'))
            ->max('current_period_end');
        if ($sponsored !== null) {
            $found['sponsored'] = substr((string) $sponsored, 0, 10);
        }
        $own = LearnerSubscription::query()->whereIn('plan', [LearnerSubscription::PER_CHILD, LearnerSubscription::FAMILY])->where('learner_id', $learner->id)
            ->whereIn('status', LearnerSubscription::PAID_STATUSES)->whereDate('current_period_end', '>=', $today)->max('current_period_end');
        if ($own !== null) {
            $found['subscription'] = substr((string) $own, 0, 10);
        }
        if ($found === []) {
            return ['required' => true, 'entitled' => false, 'source' => null, 'until' => null];
        }
        // The cover that lasts longest, then the school's and a sponsor's before the family's own.
        arsort($found);
        $source = (string) array_key_first($found);

        return ['required' => true, 'entitled' => true, 'source' => $source, 'until' => $found[$source]];
    }

    public function entitled(Students $learner): bool
    {
        return $this->forLearner($learner)['entitled'];
    }

    /**
     * For ModuleGate: whether a signed-in learner may use a paid module.
     * Anyone who is not a learner here is not limited by this.
     */
    public function allowsSignedInLearner(User $user): bool
    {
        if (! $this->required()) {
            return true;
        }

        return (bool) $this->remember('subscriptions.learner.'.$this->currentSchool->id().'.'.$user->id, function () use ($user): bool {
            $learner = Students::query()->where('user_id', $user->id)->first();

            return $learner === null || $this->entitled($learner);
        });
    }

    /** The learner's own tutor: 403 without an entitlement. */
    public function assertLearner(Students $learner): void
    {
        abort_unless($this->entitled($learner), 403, __('subscription_needed'));
    }

    /**
     * A guardian's insight into a child (mastery map, learning path, starting
     * point, tutor summary): 403 without the child's entitlement. The learner's
     * own views are not parent insights and are never limited here.
     */
    public function assertInsight(User $actor, Students $learner): void
    {
        if ((int) $learner->user_id === (int) $actor->id) {
            return;
        }
        $this->assertLearner($learner);
    }

    public function forget(): void
    {
        $this->jobCache = [];
        if (RequestContext::inHttpRequest()) {
            foreach (array_keys(request()->attributes->all()) as $key) {
                if (str_starts_with((string) $key, 'subscriptions.')) {
                    request()->attributes->remove($key);
                }
            }
        }
    }

    /** Cached on the HTTP request, or on this object in a job, never longer. */
    private function remember(string $key, callable $read): mixed
    {
        if (RequestContext::inHttpRequest()) {
            if (! request()->attributes->has($key)) {
                request()->attributes->set($key, $read());
            }

            return request()->attributes->get($key);
        }
        if (! array_key_exists($key, $this->jobCache)) {
            $this->jobCache[$key] = $read();
        }

        return $this->jobCache[$key];
    }
}
