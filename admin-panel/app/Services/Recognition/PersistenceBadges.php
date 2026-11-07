<?php

declare(strict_types=1);

namespace App\Services\Recognition;

use App\Models\Learning\PracticeReview;
use App\Models\Recognition\RecognitionBadge;
use App\Models\Recognition\RecognitionBadgeSettings;
use App\Models\Students;
use App\Models\User;
use App\Services\Learning\Digests\DigestAuthority;
use App\Services\Learning\Digests\GrowthMovement;
use App\Services\Learning\Motivation\PracticeStreaks;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Persistence badges (Action Plan 4.6), presented through the awards module
 * as permanent awards with source "badge". Each school switches each badge on
 * in its award settings; both start off. A badge is earned at most once per
 * learner, kind, week (and topic), even after a manager withdraws it.
 * engineering/specs/growth/growth-digest.md.
 */
class PersistenceBadges
{
    public const DEFAULTS = ['practice_days_enabled' => false, 'practice_days_required' => 4, 'comeback_enabled' => false, 'comeback_below_percent' => 50, 'streak_enabled' => false];

    /** At most this many "came back" badges per learner per week. */
    public const COMEBACKS_PER_WEEK = 3;

    public function current(): array
    {
        $row = RecognitionBadgeSettings::first();

        return ($row === null ? self::DEFAULTS : $row->only(array_keys(self::DEFAULTS))) + ['revision' => (int) ($row->revision ?? 0)];
    }

    public function configure(User $actor, array $input): RecognitionBadgeSettings
    {
        $a = app(RecognitionAuthority::class);
        $a->manage($actor, 'awards');
        $d = Validator::make($input, RecognitionRules::for('badges'))->validate();
        foreach (['revision', 'practice_days_required', 'comeback_below_percent'] as $key) {
            $d[$key] = (int) $d[$key];
        }
        foreach (['practice_days_enabled', 'comeback_enabled', 'streak_enabled'] as $key) {
            // The streak badge (Action Plan 3.13) is optional in the form: left out, it keeps its setting.
            if (array_key_exists($key, $d)) {
                $d[$key] = (bool) $d[$key];
            }
        }

        return app(RecognitionOperations::class)->once($actor, 'badges', $d, fn () => $a->manage($actor, 'awards'), function () use ($actor, $d) {
            $row = RecognitionBadgeSettings::lockForUpdate()->first() ?? new RecognitionBadgeSettings(['revision' => 0]);
            abort_unless((int) $row->revision === $d['revision'], 409, 'The badge settings changed. Compare the current settings.');
            $on = $d['practice_days_enabled'] || $d['comeback_enabled'] || ($d['streak_enabled'] ?? (bool) $row->streak_enabled);
            $row->fill(array_intersect_key($d, self::DEFAULTS) + ['switched_on_by' => $on ? (int) $actor->id : $row->switched_on_by, 'revision' => (int) $row->revision + 1])->save();
            Audit::record('recognition.badges_configured', $row, [], ['revision' => $row->revision] + array_intersect_key($d, self::DEFAULTS));

            return $row;
        }, fn (int $id) => RecognitionBadgeSettings::findOrFail($id));
    }

    /** The hourly weekly job: after the school-local Monday hour, award last week's badges. */
    public function monday(): array
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->refresh()?->isActive(), 403);
        $today = CarbonImmutable::now($school->school()->timezone);
        if (! $today->isMonday() || $today->hour < (int) config('weekly_digests.local_hour')) {
            return ['awarded' => 0];
        }

        return $this->week($today->startOfWeek()->subWeek()->startOfDay());
    }

    public function week(CarbonImmutable $start): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        $settings = RecognitionBadgeSettings::first();
        if (! $gate->writable('awards') || $settings === null || $settings->switched_on_by === null || (! $settings->practice_days_enabled && ! $settings->comeback_enabled && ! $settings->streak_enabled)) {
            return ['awarded' => 0];
        }
        $presenter = User::find($settings->switched_on_by);
        $timezone = app(CurrentSchool::class)->school()->timezone;
        $start = CarbonImmutable::parse($start->toDateString(), $timezone)->startOfDay();
        abort_unless($start->isMonday() && $start->addDays(6)->lt(CarbonImmutable::now($timezone)->startOfDay()), 422);
        if ($presenter === null) {
            return ['awarded' => 0];
        }
        $practice = ($settings->practice_days_enabled || $settings->streak_enabled) && $gate->allows('daily_practice');
        $awarded = 0;
        Students::orderBy('id')->chunkById(100, function ($learners) use ($settings, $presenter, $start, $practice, &$awarded): void {
            foreach ($learners as $learner) {
                if (! app(DigestAuthority::class)->currentLearner($learner)) {
                    continue;
                }
                try {
                    $awarded += $this->learner($settings, $presenter, $learner, $start, $practice);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    // No current placement or year: nothing is awarded this week.
                }
            }
        });

        return ['awarded' => $awarded];
    }

    private function learner(RecognitionBadgeSettings $settings, User $presenter, Students $learner, CarbonImmutable $start, bool $practice): int
    {
        $week = $start->toDateString();
        $awarded = 0;
        if ($practice && $settings->practice_days_enabled) {
            $days = PracticeReview::where('learner_id', $learner->id)->whereBetween('reviewed_on', [$week, $start->addDays(6)->toDateString()])->distinct()->count('reviewed_on');
            if ($days >= $settings->practice_days_required) {
                $awarded += (int) $this->award($presenter, $learner, 'practice_days', $start, null,
                    __('Kept practising on :days days in one week', ['days' => $days]),
                    __('Earned automatically for practising on at least :required different days in the week of :week.', ['required' => $settings->practice_days_required, 'week' => $week]));
            }
        }
        if ($practice && $settings->streak_enabled) {
            $awarded += $this->streaks($presenter, $learner, $start);
        }
        if ($settings->comeback_enabled) {
            $below = $settings->comeback_below_percent * 100;
            $comebacks = array_values(array_filter(app(GrowthMovement::class)->grew((int) $learner->id, $start), fn ($topic) => $topic['before_bp'] < $below));
            foreach (array_slice($comebacks, 0, self::COMEBACKS_PER_WEEK) as $topic) {
                $awarded += (int) $this->award($presenter, $learner, 'comeback', $start, $topic['topic_id'],
                    __('Came back to :topic and grew', ['topic' => $topic['name']]),
                    __('Earned automatically for returning to a topic after an earlier low score and improving, in the week of :week.', ['week' => $week]));
            }
        }

        return $awarded;
    }

    /**
     * Streak milestones (Action Plan 3.13): each milestone a run reached during
     * this week, once per learner and milestone each school year, counted the
     * kind way (PracticeStreaks). It names the run's length, never a place.
     */
    private function streaks(User $presenter, Students $learner, CarbonImmutable $start): int
    {
        $peak = app(PracticeStreaks::class)->summary((int) $learner->id, $start->addDays(6)->toDateString(), $start->toDateString())['peak_days'];
        $year = (int) schoolSetting('session_year');
        $awarded = 0;
        foreach ((array) config('motivation.streak.milestones', []) as $days) {
            if ($peak < (int) $days) {
                continue;
            }
            $awarded += (int) $this->award($presenter, $learner, 'streak', $start, null,
                __('Kept a practice run going for :days days', ['days' => (int) $days]),
                __('Earned automatically for a practice run of :days days. Weekends, holidays and one rest day a week never break a run.', ['days' => (int) $days]),
                hash('sha256', implode(':', ['badge', 'streak', $learner->id, $year, (int) $days])));
        }

        return $awarded;
    }

    private function award(User $presenter, Students $learner, string $kind, CarbonImmutable $start, ?int $topicId, string $title, string $criteria, ?string $key = null): bool
    {
        $key ??= hash('sha256', implode(':', ['badge', $kind, $learner->id, $start->toDateString(), $topicId ?? 0]));
        if (RecognitionBadge::where('dedupe_key', $key)->exists()) {
            return false;
        }
        $authority = app(RecognitionAuthority::class);
        $year = $authority->currentYear();
        $on = $start->addDays(6)->toDateString();
        if ($on < substr((string) $year->start_date, 0, 10)) {
            return false;
        }
        $place = $authority->placement((int) $learner->id, (int) $year->id, $on);
        try {
            return DB::transaction(function () use ($presenter, $learner, $kind, $start, $topicId, $key, $year, $place, $on, $title, $criteria): bool {
                // The ledger row comes first: a concurrent run fails on its unique key before presenting.
                $badge = RecognitionBadge::create(['learner_id' => $learner->id, 'kind' => $kind, 'week_start' => $start->toDateString(), 'topic_id' => $topicId, 'dedupe_key' => $key]);
                $row = app(AwardPresentations::class)->badge($learner, $year, $place, $presenter, $on, $title, $criteria);
                $badge->update(['award_presentation_id' => $row->id]);

                return true;
            }, 3);
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
