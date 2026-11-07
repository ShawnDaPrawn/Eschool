<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Learning\PracticeGoalWeek;
use App\Models\Students;
use App\Models\User;
use App\Services\Growth\GapClosures;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * One learner's own streak, weekly goal and recently secure topics (Action
 * Plan 3.13), for the learner or one of their guardians. It describes this
 * learner only: no other child, class average, position or comparison.
 */
class PracticeMotivation
{
    public function summary(User $actor, int $learnerId): array
    {
        $school = app(CurrentSchool::class)->id();
        app(MotivationAuthority::class)->reader($actor, $learnerId);
        $streak = app(PracticeStreaks::class)->summary($learnerId);
        $goal = app(PracticeGoals::class)->progress($learnerId);
        $secure = app(GapClosures::class)->recent($learnerId);
        // Fresh again after reading: a refusal now wins over what was read.
        app(MotivationAuthority::class)->reader($actor, $learnerId);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return ['learner_id' => $learnerId, 'can_set_goal' => $this->own($actor, $learnerId),
            'streak' => ['current_days' => $streak['current_days'], 'best_days' => $streak['best_days'], 'rest_day_available' => $streak['rest_day_available']],
            'goal' => $goal, 'secure_topics' => $secure];
    }

    public function setGoal(User $actor, int $learnerId, array $input): array
    {
        app(PracticeGoals::class)->set($actor, $learnerId, $input);

        return $this->summary($actor, $learnerId);
    }

    /**
     * The parent digest's practice lines for the school-local week starting
     * $start (Monday): the goal days when the learner reached their goal that
     * week, and their run at the end of the week when it is two days or more.
     * Nothing is returned for a week without either, so the digest never
     * prints a missed goal, a broken run or a zero.
     *
     * @return array{goal_met_days: ?int, streak_days: ?int}
     */
    public function digestWeek(Students $learner, CarbonImmutable $start): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows('daily_practice') || ! app(MotivationAuthority::class)->stageAllows($learner)) {
            return ['goal_met_days' => null, 'streak_days' => null];
        }
        $met = PracticeGoalWeek::where('learner_id', $learner->id)->whereDate('week_start', $start->toDateString())->value('days_target');
        $run = app(PracticeStreaks::class)->summary((int) $learner->id, $start->addDays(6)->toDateString())['current_days'];

        return ['goal_met_days' => $met === null ? null : (int) $met, 'streak_days' => $run >= 2 ? $run : null];
    }

    private function own(User $actor, int $learnerId): bool
    {
        try {
            app(MotivationAuthority::class)->self($actor, $learnerId);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
