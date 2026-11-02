<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryGrowthSnapshot;
use App\Models\Growth\MasteryTopic;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryScore;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The mastery impact report (Action Plan 3.14): per term, topic and grade, how
 * many learners rose a level, held, went lower, were first measured, or have
 * no evidence yet, from mastery_growth_snapshots. Counts only: no learner id,
 * name or per-learner row ever leaves this service. Start and end are always
 * compared within one mastery model version.
 */
class MasteryImpact
{
    /** The teacher's view: canonical marks plus practice. */
    public const AUDIENCE = 'staff';

    public const COUNTS = ['rose', 'held', 'lower', 'first_evidence', 'start_unknown', 'no_evidence', 'evidence_at_start', 'evidence_now'];

    /**
     * null: the whole school (current principal, or academic management
     * permission); otherwise the grades of the actor's current grade-head
     * appointments. Anyone else is refused.
     */
    public function scope(User $actor): ?array
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $authority = app(AcademicAuthority::class);
        $fresh = $authority->staff($actor);
        abort_if($fresh->password_change_required, 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('leadership_dashboard') && $gate->allows('mastery'), 403);
        if ($authority->manages($fresh) || $authority->currentLeadership($fresh, 'principal', 0)) {
            return null;
        }
        $today = $authority->today();
        $grades = AcademicLeadership::query()->where('user_id', $fresh->id)->where('kind', 'grade_head')->whereNotNull('grade_level_id')
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('grade_level_id')
            ->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();
        abort_if($grades === [], 403);

        return $grades;
    }

    public function available(User $actor): bool
    {
        try {
            $this->scope($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    public function read(User $actor, ?int $termId = null, ?string $version = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $grades = $this->scope($actor);
        $today = app(AcademicAuthority::class)->today();
        $terms = Term::query()->whereNotNull('start_date')->whereNotNull('end_date')->whereDate('start_date', '<=', $today)
            ->orderByDesc('start_date')->orderByDesc('id')->limit(40)->get();
        $term = $termId !== null ? Term::query()->findOrFail($termId)
            : ($terms->first(fn (Term $t) => $t->end_date->toDateString() >= $today) ?? $terms->first());
        $result = ['school_id' => $school, 'scope' => $grades === null ? 'school' : 'grades', 'audience' => self::AUDIENCE,
            'terms' => $terms->map(fn (Term $t) => $this->term($t))->all(), 'term' => $term === null ? null : $this->term($term),
            'versions' => [], 'policy_version' => $version ?? MasteryScore::VERSION, 'grades' => []];
        if ($term === null) {
            return $this->recheck($actor, $school, $grades, $result);
        }
        $base = fn () => MasteryGrowthSnapshot::query()->where('term_id', $term->id)->where('audience', self::AUDIENCE)
            ->when($grades !== null, fn ($q) => $q->whereIn('grade_level_id', $grades));
        $versions = $base()->distinct()->orderBy('policy_version')->pluck('policy_version')->map(fn ($v) => (string) $v)->all();
        $version ??= in_array(MasteryScore::VERSION, $versions, true) || $versions === [] ? MasteryScore::VERSION : $versions[0];
        $result['versions'] = $versions;
        $result['policy_version'] = $version;
        $rows = $base()->where('policy_version', $version)->selectRaw('grade_level_id, subject_id, topic_id,
                SUM(CASE WHEN end_level > start_level THEN 1 ELSE 0 END) AS rose,
                SUM(CASE WHEN end_level = start_level THEN 1 ELSE 0 END) AS held,
                SUM(CASE WHEN end_level < start_level THEN 1 ELSE 0 END) AS lower_level,
                SUM(CASE WHEN end_level IS NOT NULL AND start_level IS NULL AND start_basis <> ? THEN 1 ELSE 0 END) AS first_evidence,
                SUM(CASE WHEN end_level IS NOT NULL AND start_basis = ? THEN 1 ELSE 0 END) AS start_unknown,
                SUM(CASE WHEN start_level IS NOT NULL THEN 1 ELSE 0 END) AS evidence_at_start,
                SUM(CASE WHEN end_level IS NOT NULL THEN 1 ELSE 0 END) AS evidence_now', ['unavailable', 'unavailable'])
            ->groupBy('grade_level_id', 'subject_id', 'topic_id')->get();
        // Learners with evidence now, per topic, and each subject/grade roster of the term:
        // used only to count learners still without evidence, never returned.
        $measured = [];
        foreach ($base()->where('policy_version', $version)->whereNotNull('end_level')->get(['topic_id', 'learner_id']) as $row) {
            $measured[(int) $row->topic_id][(int) $row->learner_id] = true;
        }
        $unmeasured = [];
        foreach ($base()->where('policy_version', $version)->whereNull('end_level')->get(['topic_id', 'learner_id']) as $row) {
            $unmeasured[(int) $row->topic_id][(int) $row->learner_id] = true;
        }
        $eligibleGrades = GradeLevel::query()->whereBetween('sort_order', [$term->grade_from, $term->grade_to])
            ->when($grades !== null, fn ($q) => $q->whereIn('id', $grades))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $rosters = $this->rosters($term, $eligibleGrades);
        // Seed the roster even when a topic has never produced a snapshot. An explicitly
        // absent model version must still remain separate from the available versions.
        if ($versions === [] || in_array($version, $versions, true)) {
            $present = $rows->pluck('topic_id')->all();
            foreach (MasteryTopic::whereIn('grade_level_id', $eligibleGrades)->whereNotIn('id', $present)
                ->whereHas('source', fn ($q) => $q->where('term_id', $term->id)
                    ->whereHas('curriculum', fn ($c) => $c->where('session_year_id', $term->session_year_id)->where('state', 'published')))
                ->get() as $topic) {
                if (isset($rosters[$topic->subject_id.':'.$topic->grade_level_id])) {
                    $rows->push((object) (['grade_level_id' => $topic->grade_level_id, 'subject_id' => $topic->subject_id, 'topic_id' => $topic->id,
                        'lower_level' => 0] + array_fill_keys(self::COUNTS, 0)));
                }
            }
        }
        $topics = MasteryTopic::query()->whereIn('id', $rows->pluck('topic_id'))->pluck('name', 'id');
        $subjects = Subject::query()->whereIn('id', $rows->pluck('subject_id'))->pluck('name', 'id');
        $gradeLevels = GradeLevel::query()->whereIn('id', $rows->pluck('grade_level_id'))->orderBy('sort_order')->get(['id', 'name']);
        foreach ($gradeLevels as $grade) {
            $topicRows = [];
            $totals = array_fill_keys(self::COUNTS, 0);
            foreach ($rows->where('grade_level_id', $grade->id) as $row) {
                $topicId = (int) $row->topic_id;
                $roster = $rosters[(int) $row->subject_id.':'.(int) $grade->id] ?? [];
                $without = array_diff_key($roster + ($unmeasured[$topicId] ?? []), $measured[$topicId] ?? []);
                $counts = ['rose' => (int) $row->rose, 'held' => (int) $row->held, 'lower' => (int) $row->lower_level, 'first_evidence' => (int) $row->first_evidence,
                    'start_unknown' => (int) $row->start_unknown, 'no_evidence' => count($without), 'evidence_at_start' => (int) $row->evidence_at_start,
                    'evidence_now' => (int) $row->evidence_now];
                foreach ($counts as $key => $value) {
                    $totals[$key] += $value;
                }
                $topicRows[] = ['topic_id' => $topicId, 'topic_name' => (string) ($topics[$topicId] ?? ''), 'subject_id' => (int) $row->subject_id,
                    'subject_name' => (string) ($subjects[(int) $row->subject_id] ?? '')] + $counts;
            }
            usort($topicRows, fn ($a, $b) => [$a['subject_name'], $a['topic_name'], $a['topic_id']] <=> [$b['subject_name'], $b['topic_name'], $b['topic_id']]);
            $result['grades'][] = ['grade_id' => (int) $grade->id, 'grade_name' => $grade->name, 'totals' => $totals, 'topics' => $topicRows];
        }

        return $this->recheck($actor, $school, $grades, $result);
    }

    private function term(Term $term): array
    {
        return ['id' => (int) $term->id, 'name' => (string) $term->name, 'starts_on' => $term->start_date->toDateString(), 'ends_on' => $term->end_date->toDateString()];
    }

    /** subject_id:grade_level_id => [learner_id => true] for memberships overlapping the term. */
    private function rosters(Term $term, array $grades): array
    {
        if ($grades === []) {
            return [];
        }
        $rows = TeachingGroupMembership::query()->join('teaching_groups as impact_group', 'impact_group.id', '=', 'teaching_group_memberships.teaching_group_id')
            ->where('impact_group.school_id', app(CurrentSchool::class)->id())->whereNull('impact_group.deleted_at')
            ->where('impact_group.session_year_id', $term->session_year_id)->whereIn('impact_group.grade_level_id', $grades)
            ->whereDate('teaching_group_memberships.starts_on', '<=', $term->end_date->toDateString())
            ->whereDate('teaching_group_memberships.ends_on', '>=', $term->start_date->toDateString())
            ->distinct()->get(['impact_group.subject_id', 'impact_group.grade_level_id', 'teaching_group_memberships.learner_id']);
        $rosters = [];
        foreach ($rows as $row) {
            $rosters[(int) $row->subject_id.':'.(int) $row->grade_level_id][(int) $row->learner_id] = true;
        }

        return $rosters;
    }

    /** Authority is checked again after aggregation, as on the leadership dashboard. */
    private function recheck(User $actor, int $school, ?array $grades, array $result): array
    {
        abort_unless(app(CurrentSchool::class)->id() === $school && $this->scope($actor) === $grades, 403);

        return $result;
    }
}
