<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeMotivationSettings;
use App\Models\Learning\PracticeNudge;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Notifications\NotificationRules;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Notifications\QuietHours;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Gentle practice nudges (Action Plan 3.13). Both are off until a school
 * switches them on. When a learner who has practised before has not practised
 * for N school days in a row (weekends, breaks and holidays are not counted),
 * a short, warm reminder goes to their guardians and, separately switched,
 * to the learner. Each person gets at most one nudge a week, whichever child
 * it is about, never during their quiet hours (the hourly run tries again
 * later that afternoon), never when they have switched the reminder off, and
 * only on a school day afternoon. The text names no child, count or score.
 */
class PracticeNudges
{
    public const TYPE = 'practice.nudge';

    public const LINK = '/learning/practice';

    /** @return array{guardian: int, learner: int, held: int} */
    public function run(): array
    {
        $counts = ['guardian' => 0, 'learner' => 0, 'held' => 0];
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->refresh()?->isActive(), 403);
        $settings = PracticeMotivationSettings::first();
        $gate = app(ModuleGate::class);
        $gate->forget();
        if ($settings === null || (! $settings->guardian_nudge_enabled && ! $settings->learner_nudge_enabled) || ! $gate->writable('daily_practice')) {
            return $counts;
        }
        $now = CarbonImmutable::now($school->school()->timezone);
        $today = $now->toDateString();
        $from = (int) config('motivation.nudges.local_hour_from', 15);
        $until = (int) config('motivation.nudges.local_hour_until', 19);
        if ($now->hour < $from || $now->hour >= $until || ! (app(SchoolDays::class)->between($today, $today)[$today] ?? false)) {
            return $counts;
        }
        $monday = $now->startOfWeek()->toDateString();
        $after = (int) $settings->nudge_after_school_days;
        $lookback = $now->subDays((int) config('motivation.streak.lookback_days', 366))->toDateString();
        $calendar = app(SchoolDays::class)->between($lookback, $today);
        Students::orderBy('id')->chunkById(100, function ($learners) use ($settings, $monday, $after, $lookback, $today, $calendar, &$counts): void {
            foreach ($learners as $learner) {
                try {
                    app(MotivationAuthority::class)->practising($learner);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    continue;
                }
                if (! $this->lapsed((int) $learner->id, $after, $lookback, $today, $calendar)) {
                    continue;
                }
                if ($settings->guardian_nudge_enabled) {
                    foreach (app(GuardianService::class)->guardianUserIds($learner) as $guardian) {
                        $this->tally($counts, 'guardian', $this->nudge($learner, (int) $guardian, 'guardian', $monday));
                    }
                }
                if ($settings->learner_nudge_enabled) {
                    $this->tally($counts, 'learner', $this->nudge($learner, (int) $learner->user_id, 'learner', $monday));
                }
            }
        });

        return $counts;
    }

    /** Practised before, but not today and not on the last $after school days. */
    private function lapsed(int $learnerId, int $after, string $lookback, string $today, array $calendar): bool
    {
        $last = PracticeDailySet::query()->where('learner_id', $learnerId)->whereNotNull('completed_at')->whereBetween('completed_on', [$lookback, $today])->max('completed_on');
        if ($last === null) {
            return false;
        }
        $last = substr((string) $last, 0, 10);
        $missed = 0;
        foreach ($calendar as $date => $schoolDay) {
            if ($date > $last && $date < $today && $schoolDay) {
                $missed++;
            }
        }

        return $last < $today && $missed >= $after;
    }

    private function tally(array &$counts, string $audience, string $outcome): void
    {
        if ($outcome === 'sent') {
            $counts[$audience]++;
        } elseif ($outcome === 'held') {
            $counts['held']++;
        }
    }

    /** @return 'sent'|'held'|'skipped' */
    private function nudge(Students $learner, int $userId, string $audience, string $monday): string
    {
        $user = User::find($userId);
        $type = self::TYPE;
        if ($user === null || PracticeNudge::where('user_id', $userId)->whereDate('week_start', $monday)->exists()) {
            return 'skipped';
        }
        $rules = app(NotificationRules::class);
        // A person who switched the reminder off on every channel gets nothing, and nothing is recorded.
        if (! collect(NotificationRules::CHANNELS)->contains(fn (string $channel) => $rules->allows($user, $type, $channel))) {
            return 'skipped';
        }
        // Never during quiet hours, on any channel: try again on a later hourly run.
        if (app(QuietHours::class)->holdUntil($userId, $type) !== null) {
            return 'held';
        }
        [$title, $message] = $audience === 'guardian'
            ? [__('A gentle practice reminder'), __('Daily practice is ready in NavigateEd whenever your family has a few minutes. Every short session helps.')]
            : [__('Your practice is ready'), __('Your daily practice is ready when you are. A few minutes is enough to pick up where you left off.')];
        try {
            DB::transaction(function () use ($learner, $userId, $audience, $monday, $type, $title, $message): void {
                $row = PracticeNudge::create(['learner_id' => $learner->id, 'user_id' => $userId, 'audience' => $audience, 'week_start' => $monday]);
                $notifier = app(Notifier::class);
                $notice = $notifier->makeNotice($type, $title, $message, self::LINK);
                $notice->delivery_payload = ['link' => self::LINK, 'motivation_id' => (int) $row->id];
                $notice->save();
                $row->update(['notification_id' => $notice->id]);
                $notifier->assign($notice, [$userId]);
            }, 3);
        } catch (UniqueConstraintViolationException) {
            return 'skipped';
        }

        return 'sent';
    }
}
