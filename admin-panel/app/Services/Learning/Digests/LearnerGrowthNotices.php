<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Learning\LearnerGrowthWeek;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\PrivateNoticeDelivery;
use App\Services\Platform\Notifications\PrivateNoticeScope;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The learner's own "This week you grew in…" (Action Plan 4.6). It goes only
 * to the learner's in-app inbox (no push or email, so the topic names are not
 * shown on a lock screen or in a mailbox), and the inbox shows it only while
 * the learner is still current, guardian-approved and the weekly digest and
 * mastery map are on.
 */
class LearnerGrowthNotices implements PrivateNoticeDelivery, PrivateNoticeScope
{
    public const TYPE = 'growth.weekly';

    public const LINK = '/growth/mastery';

    public function deliver(Students $learner, CarbonImmutable $start): ?LearnerGrowthWeek
    {
        if (! $this->available() || ! app(DigestAuthority::class)->currentLearner($learner)) {
            return null;
        }
        $grew = app(GrowthMovement::class)->grew((int) $learner->id, $start);
        if ($grew === []) {
            return null;
        }

        return DB::transaction(function () use ($learner, $start, $grew): LearnerGrowthWeek {
            $old = LearnerGrowthWeek::where('learner_id', $learner->id)->whereDate('week_start', $start->toDateString())->lockForUpdate()->first();
            if ($old !== null) {
                return $old;
            }
            $topics = array_map(fn ($row) => ['topic_id' => $row['topic_id'], 'name' => $row['name'], 'subject' => $row['subject']], array_slice($grew, 0, (int) config('weekly_digests.growth.max_topics', 5)));
            $week = LearnerGrowthWeek::create(['learner_id' => $learner->id, 'user_id' => $learner->user_id, 'week_start' => $start->toDateString(), 'topics' => $topics,
                'policy_version' => app(GrowthMovement::class)->modelVersion(), 'expires_at' => now()->addWeeks((int) config('weekly_digests.retention_weeks'))]);
            $names = implode(', ', array_map(fn ($row) => mb_substr($row['name'], 0, 30), array_slice($topics, 0, 3)));
            $notifier = app(Notifier::class);
            $notice = $notifier->makeNotice(self::TYPE, __('Your week of growth'), __('This week you grew in :topics. Keep going!', ['topics' => $names]), self::LINK);
            $notice->delivery_payload = ['link' => self::LINK, 'growth_week_id' => (int) $week->id];
            $notice->save();
            $week->notification_id = $notice->id;
            $week->save();
            $notifier->assign($notice, [(int) $learner->user_id]);

            return $week;
        }, 3);
    }

    public function apply(Builder $notifications, User $actor): void
    {
        $weeks = $this->readable($actor);
        if ($weeks === null) {
            $notifications->whereRaw('1 = 0');

            return;
        }
        $notifications->where('type', self::TYPE)->whereIn('id', $weeks->whereNotNull('notification_id')->select('notification_id'));
    }

    public function allows(User $actor, string $type, array $payload): bool
    {
        $id = $payload['growth_week_id'] ?? null;
        if ($type !== self::TYPE || ($payload['link'] ?? null) !== self::LINK || (! is_int($id) && ! is_string($id)) || preg_match('/^[1-9][0-9]*$/D', (string) $id) !== 1) {
            return false;
        }
        try {
            return $this->readable($actor)?->whereKey((int) $id)->exists() ?? false;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** The actor's own unexpired weeks for a learner account that is still current and approved. */
    private function readable(User $actor): ?Builder
    {
        $school = app(CurrentSchool::class);
        if (! $school->isSet() || $school->isAcrossSchools() || $school->isImpersonating() || ! $this->available()) {
            return null;
        }
        $learners = [];
        foreach (Students::where('user_id', $actor->id)->limit(5)->get() as $learner) {
            if (app(DigestAuthority::class)->currentLearner($learner)) {
                $learners[] = (int) $learner->id;
            }
        }
        if ($learners === []) {
            return null;
        }

        return LearnerGrowthWeek::query()->where('user_id', $actor->id)->whereIn('learner_id', $learners)->where('expires_at', '>', now());
    }

    private function available(): bool
    {
        $gate = app(ModuleGate::class);
        $gate->forget();

        return $gate->allows('digest') && $gate->allows('mastery');
    }
}
