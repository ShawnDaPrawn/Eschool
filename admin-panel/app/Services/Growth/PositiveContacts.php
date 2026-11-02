<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\LearnerNote;
use App\Models\Holiday;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\Recognition\AwardNomination;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\HousePoint;
use App\Models\SessionYear;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\Attendance\RegisterSettings;
use App\Services\Academics\TermBreaks;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * "No child unseen" (Action Plan 4.5): when did an adult last record a
 * positive contact with a learner? Reads only dates and kinds from
 * non-clinical sources, never note text, reasons or titles. Counselling notes,
 * wellbeing, support plans, safeguarding, health and discipline records are
 * never read. A source whose module is off, or that has no records in the
 * window, is not evidence of "no contact": with no source in use the answer
 * is "unknown". engineering/specs/growth/risk-interventions.md.
 */
class PositiveContacts
{
    public const DEFAULT_SCHOOL_DAYS = 15;

    public const MIN_SCHOOL_DAYS = 5;

    public const MAX_SCHOOL_DAYS = 60;

    public const SETTING = 'unseen_school_days';

    /** Contact kind => the module that must be on for it to count. */
    public const KINDS = ['progress_note' => 'academics', 'award' => 'awards', 'house_points' => 'house_points', 'peer_tutoring' => 'peer_tutoring'];

    /** Only these note visibilities count; counselling notes never do. */
    public const NOTE_VISIBILITIES = ['tutor', 'staff'];

    /** Per-instance memo for one evaluation run (school, year and day in the key). */
    private array $memo = [];

    public function schoolDays(): int
    {
        $value = schoolSetting(self::SETTING);

        return is_numeric($value) && (int) $value >= self::MIN_SCHOOL_DAYS && (int) $value <= self::MAX_SCHOOL_DAYS ? (int) $value : self::DEFAULT_SCHOOL_DAYS;
    }

    /**
     * The school days of the year up to $today, oldest first: the school's
     * register teaching days, without holidays or school-wide term breaks.
     *
     * @return list<string>
     */
    public function calendar(int $yearId, string $today): array
    {
        $year = SessionYear::withTrashed()->findOrFail($yearId);
        $teaching = app(RegisterSettings::class)->get()['teaching_days'];
        $holidays = Holiday::where('session_year_id', $yearId)->pluck('date')->map(fn ($d) => substr((string) $d, 0, 10))->flip();
        $breaks = app(TermBreaks::class)->forYear($yearId);
        $start = CarbonImmutable::parse($year->start_date);
        $end = min(CarbonImmutable::parse($year->end_date)->toDateString(), $today);
        $days = [];
        for ($day = $start; $day->toDateString() <= $end; $day = $day->addDay()) {
            $date = $day->toDateString();
            if (! in_array($day->isoWeekday(), $teaching, true) || $holidays->has($date)) {
                continue;
            }
            foreach ($breaks as $break) {
                if ($date >= $break['start'] && $date <= $break['end']) {
                    continue 2;
                }
            }
            $days[] = $date;
        }

        return $days;
    }

    /** The modules that are on, among the positive-contact sources. */
    public function enabledKinds(): array
    {
        $gate = app(ModuleGate::class);

        return array_keys(array_filter(self::KINDS, fn (string $module) => $gate->allows($module)));
    }

    /**
     * Evaluate learners on $today. Each result:
     * status: unseen | seen | unknown; reason; last_contact_on (this year, or
     * null); school_days_since (null when there is no contact this year);
     * kinds (the kinds recorded for the learner this year).
     *
     * @param  list<int>  $learners
     * @return array{school_days: int, window_start: ?string, sources: list<string>, enabled: list<string>, learners: array<int, array>}
     */
    public function evaluate(array $learners, int $yearId, ?string $today = null): array
    {
        $today ??= app(AcademicAuthority::class)->today();
        $days = $this->schoolDays();
        $enabled = $this->enabledKinds();
        $key = implode(':', [app(CurrentSchool::class)->id(), $yearId, $today, $days, implode(',', $enabled)]);
        [$calendar, $windowStart, $from, $sources] = $this->memo[$key] ??= (function () use ($yearId, $today, $days, $enabled) {
            $calendar = $this->calendar($yearId, $today);
            $windowStart = count($calendar) >= $days ? $calendar[count($calendar) - $days] : null;
            $from = CarbonImmutable::parse(SessionYear::withTrashed()->findOrFail($yearId)->start_date)->toDateString();
            // A source is in use when its module is on and the school recorded
            // at least one contact of that kind in the window.
            $sources = $windowStart === null ? [] : array_values(array_filter($enabled, fn (string $kind) => $this->latest($kind, null, $yearId, $windowStart, $today)->isNotEmpty()));

            return [$calendar, $windowStart, $from, $sources];
        })();
        $latest = [];
        foreach ($enabled as $kind) {
            foreach (array_chunk(array_values(array_unique($learners)), 500) as $chunk) {
                foreach ($this->latest($kind, $chunk, $yearId, $from, $today) as $learner => $date) {
                    $latest[(int) $learner][$kind] = $date;
                }
            }
        }
        $results = [];
        foreach ($learners as $learner) {
            $kinds = $latest[$learner] ?? [];
            $last = $kinds === [] ? null : max($kinds);
            $since = $last === null ? null : count(array_filter($calendar, fn (string $d) => $d > $last));
            $counted = array_intersect_key($kinds, array_flip($sources));
            $recent = $windowStart !== null && $counted !== [] && max($counted) >= $windowStart;
            [$status, $reason] = match (true) {
                $windowStart === null => ['unknown', 'insufficient_evidence'],
                $sources === [] => ['unknown', 'source_unavailable'],
                $recent => ['seen', 'positive_contact_recorded'],
                default => ['unseen', 'no_positive_contact'],
            };
            $results[$learner] = ['status' => $status, 'reason' => $reason, 'last_contact_on' => $last, 'school_days_since' => $since,
                'kinds' => array_values(array_intersect(array_keys(self::KINDS), array_keys($kinds)))];
        }

        return ['school_days' => $days, 'window_start' => $windowStart, 'sources' => $sources, 'enabled' => $enabled, 'learners' => $results];
    }

    /** The early-warning observation for one learner (RiskRecompute). */
    public function observed(int $learner, int $yearId, string $today): array
    {
        $result = $this->evaluate([$learner], $yearId, $today);
        $row = $result['learners'][$learner];
        $known = $row['status'] !== 'unknown';

        return ['score' => $known ? ($row['status'] === 'unseen' ? 100 : 0) : null, 'signal_count' => $known ? $row['school_days_since'] : null,
            'reason_code' => $row['reason'], 'evidence' => ['school_days' => $result['school_days'], 'window_start' => $result['window_start'],
                'sources' => $result['sources'], 'kinds' => $row['kinds'], 'last_contact_on' => $row['last_contact_on']], 'status' => $row['status']];
    }

    /**
     * The latest contact date of one kind per learner, from $from to $to
     * (school-local dates). With $learners null, any learner: used only to
     * ask whether the school uses the source at all.
     *
     * @return Collection<int, string>
     */
    private function latest(string $kind, ?array $learners, int $yearId, string $from, string $to): Collection
    {
        $zone = app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg';
        $utc = config('app.timezone', 'UTC');
        $local = fn ($value) => CarbonImmutable::parse((string) $value, $utc)->setTimezone($zone)->toDateString();
        // Timestamps are stored in the app timezone; the window is in school time.
        $fromAt = CarbonImmutable::parse($from.' 00:00:00', $zone)->setTimezone($utc)->format('Y-m-d H:i:s');
        $toAt = CarbonImmutable::parse($to.' 23:59:59', $zone)->setTimezone($utc)->format('Y-m-d H:i:s');
        $limit = fn ($q, string $column) => $learners === null ? $q->limit(1) : $q->whereIn($column, $learners);
        $dates = match ($kind) {
            'progress_note' => $limit(LearnerNote::where('session_year_id', $yearId)->whereIn('visibility', self::NOTE_VISIBILITIES)->whereNull('archived_at')
                ->whereBetween('created_at', [$fromAt, $toAt])->select('learner_id', 'created_at'), 'learner_id')->get()
                ->map(fn ($r) => [(int) $r->learner_id, $local($r->getRawOriginal('created_at'))]),
            'award' => $limit(AwardNomination::where('session_year_id', $yearId)->whereIn('state', ['pending', 'approved'])
                ->whereBetween('created_at', [$fromAt, $toAt])->select('learner_id', 'created_at'), 'learner_id')->get()
                ->map(fn ($r) => [(int) $r->learner_id, $local($r->getRawOriginal('created_at'))])
                ->concat($limit(AwardPresentation::where('session_year_id', $yearId)->where('state', 'active')->whereBetween('presented_on', [$from, $to])
                    ->select('learner_id', 'presented_on'), 'learner_id')->get()->map(fn ($r) => [(int) $r->learner_id, $r->presented_on->toDateString()])),
            // Only awarded points count: never a deduction, a reversal or a reversed entry.
            'house_points' => $limit(HousePoint::where('session_year_id', $yearId)->where('points', '>', 0)->whereNull('reverses_id')
                ->whereNotIn('id', HousePoint::whereNotNull('reverses_id')->select('reverses_id'))->whereBetween('on_date', [$from, $to])
                ->select('learner_id', 'on_date'), 'learner_id')->get()->map(fn ($r) => [(int) $r->learner_id, $r->on_date->toDateString()]),
            // A completed, adult-supervised session counts for the tutor and the tutee.
            'peer_tutoring' => PeerTutoringSession::where('session_year_id', $yearId)->where('status', 'completed')
                ->whereBetween('starts_at', [$fromAt, $toAt])
                ->when($learners === null, fn ($q) => $q->limit(1), fn ($q) => $q->where(fn ($w) => $w->whereIn('tutor_learner_id', $learners)->orWhereIn('tutee_learner_id', $learners)))
                ->select('tutor_learner_id', 'tutee_learner_id', 'starts_at')->get()
                ->flatMap(fn ($r) => [[(int) $r->tutor_learner_id, $local($r->getRawOriginal('starts_at'))], [(int) $r->tutee_learner_id, $local($r->getRawOriginal('starts_at'))]]),
            default => throw new \InvalidArgumentException('Unknown positive-contact kind.'),
        };

        return collect($dates)->filter(fn (array $pair) => $pair[1] >= $from && $pair[1] <= $to && ($learners === null || in_array($pair[0], $learners, true)))
            ->groupBy(0)->map(fn (Collection $rows) => (string) $rows->max(1));
    }
}
