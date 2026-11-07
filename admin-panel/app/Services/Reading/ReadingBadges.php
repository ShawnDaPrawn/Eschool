<?php

declare(strict_types=1);

namespace App\Services\Reading;

use App\Models\Reading\ReadingBook;
use App\Models\Reading\ReadingDay;
use App\Models\Reading\ReadingSettings;
use App\Models\Recognition\RecognitionBadge;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Recognition\AwardPresentations;
use App\Services\Recognition\RecognitionAuthority;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Reading badges (Action Plan 4.4), presented through the awards module as
 * permanent awards with source "badge", like the persistence badges: one for
 * finishing a book (once per book) and one for reading on enough different
 * days in a week (once per week). Each school switches each one on; both
 * start off, and nothing is given while the awards module is off. A badge is
 * about the learner's own reading only: there are no rankings or comparisons.
 * The recognition_badges ledger makes each one once only, even after a
 * manager withdraws it.
 */
class ReadingBadges
{
    public const FINISHED = 'reading_finished';

    public const DAYS = 'reading_days';

    /** @return list<string> titles of the badges earned just now */
    public function after(Students $learner, ReadingBook $book, bool $finished, bool $newDay, string $today): array
    {
        if (! $finished && ! $newDay) {
            return [];
        }
        $settings = ReadingSettings::query()->first();
        $gate = app(ModuleGate::class);
        if ($settings === null || $settings->switched_on_by === null || (! $settings->finished_badge && ! $settings->days_badge) || ! $gate->writable('awards')) {
            return [];
        }
        $presenter = User::query()->find($settings->switched_on_by);
        if ($presenter === null) {
            return [];
        }
        $day = CarbonImmutable::parse($today);
        $week = $day->startOfWeek();
        $earned = [];
        if ($finished && $settings->finished_badge) {
            $title = __('Finished reading :title', ['title' => $book->title]);
            if ($this->award($presenter, $learner, self::FINISHED, $week, $today, 'book:'.$book->id, $title,
                __('Earned automatically for reading to the end of :title in the reading library.', ['title' => $book->title]))) {
                $earned[] = $title;
            }
        }
        if ($newDay && $settings->days_badge) {
            $days = ReadingDay::query()->where('learner_id', $learner->id)->whereBetween('read_on', [$week->toDateString(), $today])
                ->where('seconds', '>=', (int) config('reading.reading_day_seconds'))->count();
            if ($days >= $settings->days_required) {
                $title = __('Read on :days days in one week', ['days' => $days]);
                if ($this->award($presenter, $learner, self::DAYS, $week, $today, 'week', $title,
                    __('Earned automatically for reading on at least :required different days in the week of :week.', ['required' => $settings->days_required, 'week' => $week->toDateString()]))) {
                    $earned[] = $title;
                }
            }
        }

        return $earned;
    }

    private function award(User $presenter, Students $learner, string $kind, CarbonImmutable $week, string $on, string $subject, string $title, string $criteria): bool
    {
        $key = hash('sha256', implode(':', ['reading', $kind, $learner->id, $kind === self::DAYS ? $week->toDateString() : $subject]));
        if (RecognitionBadge::query()->where('dedupe_key', $key)->exists()) {
            return false;
        }
        try {
            $authority = app(RecognitionAuthority::class);
            $year = $authority->currentYear();
            $place = $authority->placement((int) $learner->id, (int) $year->id, $on);

            return DB::transaction(function () use ($presenter, $learner, $kind, $week, $key, $year, $place, $on, $title, $criteria): bool {
                // The ledger row comes first: a concurrent request fails on its unique key before presenting.
                $badge = RecognitionBadge::query()->create(['learner_id' => $learner->id, 'kind' => $kind, 'week_start' => $week->toDateString(), 'topic_id' => null, 'dedupe_key' => $key]);
                $row = app(AwardPresentations::class)->badge($learner, $year, $place, $presenter, $on, $title, $criteria);
                $badge->update(['award_presentation_id' => $row->id]);

                return true;
            }, 3);
        } catch (UniqueConstraintViolationException|HttpExceptionInterface|ModelNotFoundException) {
            // Already given, or no current year or placement: nothing is given now.
            return false;
        }
    }
}
