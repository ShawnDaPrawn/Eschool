<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\GradeLevel;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryGrowthSnapshot;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasteryTopic;
use App\Models\Term;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Term growth snapshots for the current school (Action Plan 3.14).
 *
 * Every mastery record (learner, topic, audience, model version) whose topic
 * grade has a running term, or one that ended in the last CLOSING_DAYS, gets
 * one snapshot row for that term. The start is written once and never
 * changes; the end is refreshed on every run while the term runs, settled on
 * the first run after it ends (end_final) and never written again. Running it
 * twice gives the same rows (idempotent).
 *
 * recent20-v1 is a pure function of the evidence, so both ends are replayed
 * from evidence dated before the term start and up to the term end (or now).
 * Evidence is a rebuilt projection, not a history: a corrected mark is dated
 * at its correction. That is why starts and ends are frozen once written; a
 * start first written mid-term (a backfill) can miss an earlier mark that was
 * corrected during this term.
 * Another model version is read from its stored record: its start is known
 * only when first captured on the term's first day; otherwise it is
 * "unavailable", never zero, and its end stops at the term's last run.
 */
class MasteryGrowth
{
    public const CLOSING_DAYS = 14;

    public const AUDIENCES = ['staff' => ['staff', 'practice'], 'family' => ['family', 'practice'], 'practice' => ['practice']];

    private const END_COLUMNS = ['end_level', 'end_score_bp', 'end_confidence_bp', 'end_evidence_count', 'end_as_of', 'end_final'];

    public function capture(?string $today = null): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('mastery'), 403, 'The mastery module is disabled.');
        $school = app(CurrentSchool::class)->school();
        abort_if($school === null, 403);
        $timezone = $school->timezone ?: 'Africa/Johannesburg';
        $today ??= app(AcademicAuthority::class)->today();
        $now = CarbonImmutable::now();
        [$termsByGrade, $ambiguous] = $this->terms($today);
        $termIds = [];
        foreach ($termsByGrade as $terms) {
            foreach ($terms as $term) {
                $termIds[(int) $term->id] = true;
            }
        }
        $result = ['terms' => count($termIds), 'ambiguous_terms' => $ambiguous, 'rows' => 0, 'closed' => 0, 'invalid' => 0];
        $topics = MasteryTopic::query()->whereIn('grade_level_id', array_keys(array_filter($termsByGrade)))->get(['id', 'subject_id', 'grade_level_id'])->keyBy('id');
        if ($topics->isEmpty()) {
            return $result;
        }
        $pairs = MasteryRecord::query()->whereIn('topic_id', $topics->keys())->select(['learner_id', 'subject_id'])->distinct()
            ->orderBy('learner_id')->orderBy('subject_id')->get();
        foreach ($pairs as $pair) {
            $counts = $this->learner((int) $pair->learner_id, (int) $pair->subject_id, $topics, $termsByGrade, array_keys($termIds), $today, $now, $timezone);
            foreach ($counts as $key => $count) {
                $result[$key] += $count;
            }
        }

        return $result;
    }

    /**
     * Terms per grade (grade_level_id => Term[]): running today, or ended within
     * CLOSING_DAYS. Overlapping terms for the same grade (refused by the term
     * calendar, possible only in imported data) are skipped as ambiguous.
     */
    private function terms(string $today): array
    {
        $from = CarbonImmutable::parse($today)->subDays(self::CLOSING_DAYS)->toDateString();
        $terms = Term::query()->whereNotNull('start_date')->whereNotNull('end_date')->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $from)->orderBy('start_date')->orderBy('id')->get()
            ->filter(fn (Term $t) => $t->start_date->lte($t->end_date))->values();
        $byGrade = [];
        $ambiguous = [];
        foreach (GradeLevel::query()->pluck('sort_order', 'id') as $gradeId => $sort) {
            $covering = $terms->filter(fn (Term $t) => (int) $t->grade_from <= (int) $sort && (int) $t->grade_to >= (int) $sort)->values();
            $clear = $covering->reject(function (Term $t) use ($covering, &$ambiguous) {
                $overlaps = $covering->contains(fn (Term $o) => $o->id !== $t->id && $o->start_date->lte($t->end_date) && $o->end_date->gte($t->start_date));
                if ($overlaps) {
                    $ambiguous[$t->id] = true;
                }

                return $overlaps;
            });
            $byGrade[(int) $gradeId] = $clear->values()->all();
        }

        return [$byGrade, count($ambiguous)];
    }

    private function learner(int $learnerId, int $subjectId, Collection $topics, array $termsByGrade, array $termIds, string $today, CarbonImmutable $now, string $timezone): array
    {
        $counts = ['rows' => 0, 'closed' => 0, 'invalid' => 0];
        $records = MasteryRecord::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->whereIn('topic_id', $topics->keys())
            ->orderBy('id')->get(['topic_id', 'audience', 'policy_version', 'level', 'score_bp', 'confidence_bp', 'evidence_count']);
        $evidence = MasteryEvidence::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->whereIn('topic_id', $records->pluck('topic_id')->unique())
            ->orderByDesc('observed_at')->orderByDesc('id')->get(['topic_id', 'audience', 'achieved_units', 'maximum_units', 'observed_at'])->groupBy('topic_id');
        $existing = MasteryGrowthSnapshot::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->whereIn('term_id', $termIds)
            ->get(['id', 'term_id', 'topic_id', 'audience', 'policy_version', 'end_final'])
            ->keyBy(fn ($row) => $row->term_id.':'.$row->topic_id.':'.$row->audience.':'.$row->policy_version);
        $rows = [];
        foreach ($records as $record) {
            if (! isset(self::AUDIENCES[$record->audience])) {
                continue;
            }
            $topic = $topics->get($record->topic_id);
            $version = (string) $record->policy_version !== '' ? (string) $record->policy_version : MasteryScore::VERSION;
            $replayable = $version === MasteryScore::VERSION;
            foreach ($termsByGrade[(int) $topic->grade_level_id] ?? [] as $term) {
                $key = $term->id.':'.$record->topic_id.':'.$record->audience.':'.$version;
                $prior = $existing->get($key);
                $starts = $term->start_date->toDateString();
                $ends = $term->end_date->toDateString();
                $ended = $today > $ends;
                if ($prior !== null && $prior->end_final) {
                    // Settled on the first run after the term. Evidence is a rebuilt projection:
                    // a mark corrected later is re-dated at its correction, so replaying again
                    // would drop it from the term rather than describe the term.
                    continue;
                }
                if (! $replayable && $ended) {
                    // A stored record changes after the term: its end stays at the term's last run.
                    if ($prior !== null && ! $prior->end_final) {
                        MasteryGrowthSnapshot::query()->whereKey($prior->id)->update(['end_final' => true]);
                        $counts['closed']++;
                    }

                    continue;
                }
                try {
                    if ($replayable) {
                        $startAt = CarbonImmutable::parse($starts, $timezone)->startOfDay();
                        $endAt = CarbonImmutable::parse($ends, $timezone)->addDay()->startOfDay()->min($now->addSecond());
                        $start = $this->replay($evidence->get($record->topic_id), $record->audience, $startAt) + ['start_basis' => 'replayed'];
                        $end = $this->replay($evidence->get($record->topic_id), $record->audience, $endAt);
                    } else {
                        $stored = ['level' => $record->level, 'score_bp' => $record->score_bp, 'confidence_bp' => $record->confidence_bp, 'evidence_count' => $record->evidence_count];
                        $start = $today === $starts ? $stored + ['start_basis' => 'captured']
                            : ['level' => null, 'score_bp' => null, 'confidence_bp' => null, 'evidence_count' => null, 'start_basis' => 'unavailable'];
                        $end = $stored;
                    }
                } catch (InvalidArgumentException) {
                    $counts['invalid']++;

                    continue;
                }
                $rows[] = ['term_id' => $term->id, 'session_year_id' => $term->session_year_id, 'subject_id' => $subjectId, 'topic_id' => (int) $record->topic_id,
                    'grade_level_id' => (int) $topic->grade_level_id, 'learner_id' => $learnerId, 'audience' => $record->audience, 'policy_version' => $version,
                    'start_basis' => $start['start_basis'], 'start_level' => $start['level'], 'start_score_bp' => $start['score_bp'],
                    'start_confidence_bp' => $start['confidence_bp'], 'start_evidence_count' => $start['evidence_count'],
                    'end_level' => $end['level'], 'end_score_bp' => $end['score_bp'], 'end_confidence_bp' => $end['confidence_bp'],
                    'end_evidence_count' => $end['evidence_count'], 'end_as_of' => min($today, $ends), 'end_final' => $ended, 'captured_at' => $now];
            }
        }
        foreach (array_chunk($rows, 200) as $chunk) {
            // The start columns are written on insert only: a captured start never moves.
            MasteryGrowthSnapshot::query()->upsert($chunk, ['school_id', 'term_id', 'learner_id', 'topic_id', 'audience', 'policy_version'], self::END_COLUMNS);
        }
        $counts['rows'] += count($rows);

        return $counts;
    }

    /** recent20-v1 over the evidence observed before $before, as MasteryRecompute aggregates it. */
    private function replay(?Collection $evidence, string $audience, CarbonImmutable $before): array
    {
        $rows = ($evidence ?? collect())->filter(fn ($row) => in_array($row->audience, self::AUDIENCES[$audience], true) && $row->observed_at->lt($before))
            ->take(20)->map(fn ($row) => ['achieved_units' => $row->achieved_units, 'maximum_units' => $row->maximum_units])->values()->all();
        $score = app(MasteryScore::class)->calculate($rows);

        return ['level' => $score['level'], 'score_bp' => $score['score_bp'], 'confidence_bp' => $score['confidence_bp'], 'evidence_count' => $score['evidence_count']];
    }
}
