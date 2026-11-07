<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Learning\PracticeGoal;
use App\Models\Learning\PracticeGoalWeek;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Learning\Digests\DigestAuthority;
use App\Services\Learning\PracticeOperations;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

/**
 * A learner's own weekly practice goal (Action Plan 3.13): practise on N days
 * a week, N from 1 to 7. Only the learner sets it; their guardians may see
 * it. When the week's practice days reach the goal, the learner gets one
 * gentle celebration that week. A week that falls short is never reported
 * as a failure.
 */
class PracticeGoals
{
    public const TYPE = 'practice.goal_met';

    public const LINK = '/learning/practice';

    /** @return array{days_per_week: ?int, practised_days_this_week: int, met: bool, week_start: string, revision: int} */
    public function progress(int $learnerId): array
    {
        $today = app(AcademicAuthority::class)->today();
        $monday = CarbonImmutable::parse($today)->startOfWeek()->toDateString();
        $goal = PracticeGoal::where('learner_id', $learnerId)->first();
        $days = app(PracticeStreaks::class)->daysInWeek($learnerId, $monday, $today);

        $target = $goal?->days_per_week;

        return ['days_per_week' => $target, 'practised_days_this_week' => $days, 'met' => $target !== null && $days >= $target,
            'week_start' => $monday, 'revision' => (int) ($goal->revision ?? 0)];
    }

    public function set(User $actor, int $learnerId, array $input): array
    {
        $min = (int) config('motivation.goal.min_days', 1);
        $max = (int) config('motivation.goal.max_days', 7);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'days_per_week' => ['present', 'nullable', 'integer', 'between:'.$min.','.$max]])->validate();
        $authorize = fn () => app(MotivationAuthority::class)->self($actor, $learnerId, true);
        $authorize();

        return app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learnerId, 'kind' => 'practice_goal'], $authorize, function () use ($actor, $learnerId, $data): array {
            $goal = PracticeGoal::where('learner_id', $learnerId)->lockForUpdate()->first();
            abort_unless((int) ($goal->revision ?? 0) === (int) $data['revision'], 409, 'Your goal changed. Reload it before changing it again.');
            // The row is kept when a goal is cleared (days_per_week null), so its
            // revision only ever increases and a stale tab always gets 409.
            // Every accepted write, clearing an already-cleared goal included, bumps it.
            $goal ??= new PracticeGoal(['learner_id' => $learnerId, 'revision' => 0]);
            $days = $data['days_per_week'] === null ? null : (int) $data['days_per_week'];
            $goal->fill(['days_per_week' => $days, 'set_by' => (int) $actor->id, 'revision' => (int) $goal->revision + 1])->save();
            Audit::record($days === null ? 'practice.goal_cleared' : 'practice.goal_set', $goal, [], ['learner_id' => $learnerId, 'days_per_week' => $days, 'revision' => $goal->revision]);
            if ($days !== null) {
                $this->celebrate(Students::findOrFail($learnerId));
            }

            return ['learner_id' => $learnerId, 'days_per_week' => $data['days_per_week'] === null ? null : (int) $data['days_per_week']];
        });
    }

    /** Once a week, when this week's practice days first reach the learner's goal. */
    public function celebrate(Students $learner): ?PracticeGoalWeek
    {
        $goal = PracticeGoal::where('learner_id', $learner->id)->first();
        // A cleared goal (days_per_week null) is no goal.
        if ($goal === null || $goal->days_per_week === null) {
            return null;
        }
        $today = app(AcademicAuthority::class)->today();
        $monday = CarbonImmutable::parse($today)->startOfWeek()->toDateString();
        $old = PracticeGoalWeek::where('learner_id', $learner->id)->whereDate('week_start', $monday)->first();
        if ($old !== null || app(PracticeStreaks::class)->daysInWeek((int) $learner->id, $monday, $today) < $goal->days_per_week) {
            return $old;
        }
        if (! app(DigestAuthority::class)->currentLearner($learner) || ! app(MotivationAuthority::class)->stageAllows($learner)) {
            return null;
        }
        $week = PracticeGoalWeek::create(['learner_id' => $learner->id, 'user_id' => $learner->user_id, 'week_start' => $monday, 'days_target' => $goal->days_per_week]);
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::TYPE, __('Weekly goal reached'), __('You reached your practice goal for this week. Well done for keeping at it!'), self::LINK);
        $notice->delivery_payload = ['link' => self::LINK, 'motivation_id' => (int) $week->id];
        $notice->save();
        $week->update(['notification_id' => $notice->id]);
        $notifier->assign($notice, [(int) $learner->user_id]);

        return $week;
    }
}
