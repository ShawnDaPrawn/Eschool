<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Subscriptions\LearnerSubscription;
use App\Models\Platform\Subscriptions\LearnerSubscriptionMember;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A sponsor's places at a school (Action Plan 3.20): HQ records the
 * sponsorship and its payment; the school's Super Admin names the learners
 * who hold the places, and may move a place to another learner. Only this
 * school's sponsorships and learners are ever read or written.
 */
class SponsoredPlaces
{
    public function __construct(private readonly CurrentSchool $currentSchool) {}

    public function index(User $actor): array
    {
        $fresh = $this->actor($actor);
        $rows = LearnerSubscription::query()->where('plan', LearnerSubscription::SPONSORED)->orderByDesc('id')->limit(100)->get();
        $members = LearnerSubscriptionMember::query()->whereIn('subscription_id', $rows->pluck('id'))->whereNull('removed_at')->orderBy('id')->get()->groupBy('subscription_id');
        $learners = Students::query()->with('user')->whereIn('id', $members->flatten()->pluck('learner_id')->all())->get()->keyBy('id');

        return ['school_id' => $this->currentSchool->id(), 'reader_id' => (int) $fresh->id, 'sponsorships' => $rows->map(fn (LearnerSubscription $s): array => $this->present($s, $members->get($s->id, collect()), $learners))->values()->all()];
    }

    /** Full retained browser history, with the same school authority as the API. */
    public function page(User $actor): LengthAwarePaginator
    {
        $this->actor($actor);
        $page = LearnerSubscription::query()->where('plan', LearnerSubscription::SPONSORED)->orderByDesc('id')->paginate(50);
        $members = LearnerSubscriptionMember::query()->whereIn('subscription_id', $page->pluck('id'))->whereNull('removed_at')->orderBy('id')->get()->groupBy('subscription_id');
        $learners = Students::query()->with('user')->whereIn('id', $members->flatten()->pluck('learner_id')->all())->get()->keyBy('id');

        return $page->through(fn (LearnerSubscription $subscription): array => $this->present($subscription, $members->get($subscription->id, collect()), $learners));
    }

    public function show(User $actor, int $id): array
    {
        $fresh = $this->actor($actor);
        $subscription = LearnerSubscription::query()->where('plan', LearnerSubscription::SPONSORED)->findOrFail($id);

        return $this->one($fresh, $subscription)['sponsorship'];
    }

    public function add(User $actor, int $subscriptionId, int $learnerId): array
    {
        $fresh = $this->actor($actor);

        return DB::transaction(function () use ($fresh, $subscriptionId, $learnerId): array {
            $locked = $this->lock($subscriptionId);
            abort_unless(in_array($locked->status, [LearnerSubscription::REQUESTED, LearnerSubscription::ACTIVE], true), 409, __('This sponsorship is no longer running.'));
            $learner = Students::query()->find($learnerId);
            abort_if($learner === null, 404);
            $current = LearnerSubscriptionMember::query()->where('subscription_id', $locked->id)->whereNull('removed_at')->lockForUpdate()->get();
            if (! $current->contains('learner_id', (int) $learner->id)) {
                abort_if($current->count() >= (int) $locked->places, 409, __('Every sponsored place is taken. Remove a learner first.'));
                LearnerSubscriptionMember::query()->create(['subscription_id' => $locked->id, 'learner_id' => $learner->id, 'added_by' => $fresh->id, 'added_at' => now()]);
                app(LearnerSubscriptions::class)->event($locked, 'member_added', 'school', (int) $fresh->id, null, ['learner_id' => (int) $learner->id]);
                app(Entitlements::class)->forget();
            }

            return $this->one($fresh, $locked);
        }, 3);
    }

    public function remove(User $actor, int $subscriptionId, int $learnerId): array
    {
        $fresh = $this->actor($actor);

        return DB::transaction(function () use ($fresh, $subscriptionId, $learnerId): array {
            $locked = $this->lock($subscriptionId);
            $member = LearnerSubscriptionMember::query()->where('subscription_id', $locked->id)->where('learner_id', $learnerId)->whereNull('removed_at')->lockForUpdate()->first();
            if ($member !== null) {
                $member->forceFill(['removed_at' => now(), 'removed_by' => $fresh->id])->save();
                app(LearnerSubscriptions::class)->event($locked, 'member_removed', 'school', (int) $fresh->id, null, ['learner_id' => $learnerId]);
                app(Entitlements::class)->forget();
            }

            return $this->one($fresh, $locked);
        }, 3);
    }

    /** The school's Super Admin, never platform staff, in a live school. */
    private function actor(User $actor): User
    {
        $school = $this->currentSchool;
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->isActive(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::query()->where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        abort_unless(in_array('Super Admin', app(ProfilePolicy::class)->roles($fresh), true), 403);

        return $fresh;
    }

    private function lock(int $subscriptionId): LearnerSubscription
    {
        $locked = LearnerSubscription::query()->whereKey($subscriptionId)->where('plan', LearnerSubscription::SPONSORED)->lockForUpdate()->first();
        abort_if($locked === null, 404);

        return $locked;
    }

    private function one(User $fresh, LearnerSubscription $s): array
    {
        $members = LearnerSubscriptionMember::query()->where('subscription_id', $s->id)->whereNull('removed_at')->orderBy('id')->get();
        $learners = Students::query()->with('user')->whereIn('id', $members->pluck('learner_id'))->get()->keyBy('id');

        return ['school_id' => $this->currentSchool->id(), 'reader_id' => (int) $fresh->id, 'sponsorship' => $this->present($s->refresh(), $members, $learners)];
    }

    /**
     * @param  Collection<int, LearnerSubscriptionMember>  $members
     * @param  Collection<int, Students>  $learners
     */
    private function present(LearnerSubscription $s, Collection $members, Collection $learners): array
    {
        $base = app(LearnerSubscriptions::class)->present($s);
        $rows = [];
        foreach ($members as $m) {
            $learner = $learners->get($m->learner_id);
            $rows[] = ['learner_id' => (int) $m->learner_id, 'name' => LearnerSubscriptions::name($learner?->user), 'added_on' => $m->added_at->toDateString()];
        }

        return ['id' => $base['id'], 'reference' => $base['reference'], 'sponsor_name' => (string) $s->sponsor_name, 'places' => (int) $s->places,
            'status' => $base['status'], 'effective_status' => $base['effective_status'], 'current_period_end' => $base['current_period_end'], 'learners' => $rows];
    }
}
