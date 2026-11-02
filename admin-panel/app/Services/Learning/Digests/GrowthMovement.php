<?php

declare(strict_types=1);

namespace App\Services\Learning\Digests;

use App\Models\Academics\CurriculumTopic;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasteryTopic;
use App\Models\Subject;
use App\Services\Academics\MarkDefinitions;
use App\Services\Growth\MasteryScore;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

/**
 * Action Plan 4.6: "This week you grew in…" and the help-at-home block.
 *
 * Growth is a rise only: there is no fall, ranking or comparison with another
 * child here. It uses only the evidence the family may see (released marks
 * when the school opted in, otherwise practice), never staff-only marks, and
 * compares like with like: both ends of the week are scored with the model
 * version the learner's current mastery record uses. Topics on another model
 * version are left out rather than compared across versions.
 * engineering/specs/growth/growth-digest.md.
 */
class GrowthMovement
{
    /** The mastery model version both ends of the week are scored with. */
    public function modelVersion(): string
    {
        return MasteryScore::VERSION;
    }

    /** The mastery audience a learner and their family may see, or null when the mastery map is off. */
    public function audience(): ?string
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows('mastery')) {
            return null;
        }

        return app(TopicGraph::class)->settings()['family_marks_enabled'] ? 'family' : 'practice';
    }

    /**
     * Topics whose mastery level or score rose during the school-local week starting $start.
     *
     * @return list<array{topic_id: int, name: string, subject: string, before_bp: int, after_bp: int}>
     */
    public function grew(int $learnerId, CarbonImmutable $start): array
    {
        $audience = $this->audience();
        if ($audience === null) {
            return [];
        }
        $sources = $audience === 'practice' ? ['practice'] : [$audience, 'practice'];
        [$from, $until] = $this->window($start);
        $topicIds = MasteryEvidence::query()->where('learner_id', $learnerId)->whereIn('audience', $sources)->whereBetween('observed_at', [$from, $until])
            ->whereIn('topic_id', MasteryRecord::query()->where('learner_id', $learnerId)->where('audience', $audience)->where('policy_version', $this->modelVersion())->select('topic_id'))
            ->distinct()->orderBy('topic_id')->limit(201)->pluck('topic_id')->map(fn ($id) => (int) $id)->all();
        abort_if(count($topicIds) > 200, 422, 'This weekly growth needs a smaller topic batch.');
        $topics = MasteryTopic::query()->whereIn('id', $topicIds)->get()->keyBy('id');
        $subjects = Subject::withTrashed()->whereIn('id', $topics->pluck('subject_id'))->pluck('name', 'id');
        $minimum = (int) config('weekly_digests.growth.min_score_rise_bp', 500);
        $grew = [];
        foreach ($topicIds as $topicId) {
            $topic = $topics->get($topicId);
            if ($topic === null) {
                continue;
            }
            $before = $this->score($learnerId, $topicId, $sources, '<', $from);
            $after = $this->score($learnerId, $topicId, $sources, '<=', $until);
            // Unknown is not level zero: a first piece of evidence is not growth.
            if ($before['level'] === null || $after['level'] === null) {
                continue;
            }
            if ($after['level'] > $before['level'] || $minimum <= $after['score_bp'] - $before['score_bp']) {
                $grew[] = ['topic_id' => $topicId, 'name' => mb_substr((string) $topic->name, 0, 60), 'subject' => mb_substr((string) $subjects->get($topic->subject_id), 0, 60),
                    'before_bp' => (int) $before['score_bp'], 'after_bp' => (int) $after['score_bp']];
            }
        }
        usort($grew, fn ($a, $b) => [$a['subject'], $a['name'], $a['topic_id']] <=> [$b['subject'], $b['name'], $b['topic_id']]);

        return $grew;
    }

    /**
     * Topics in published curriculum plans that the learner's current teaching
     * groups reach in the coming week (the week after $start).
     *
     * @return list<array{subject: string, topic: string}>
     */
    public function helpAtHome(int $learnerId, CarbonImmutable $start): array
    {
        $timezone = app(CurrentSchool::class)->school()->timezone;
        $monday = CarbonImmutable::parse($start->toDateString(), $timezone)->startOfDay()->addWeek();
        $sunday = $monday->addDays(6);
        $groupIds = TeachingGroupMembership::query()->where('learner_id', $learnerId)->whereDate('starts_on', '<=', $monday->toDateString())->whereDate('ends_on', '>=', $monday->toDateString())
            ->whereHas('group', fn ($q) => $q->where('active', true))->pluck('teaching_group_id');
        $items = [];
        foreach (TeachingGroup::query()->whereIn('id', $groupIds)->orderBy('id')->limit(50)->get() as $group) {
            $subject = mb_substr((string) Subject::withTrashed()->find($group->subject_id)?->name, 0, 60);
            foreach (app(MarkDefinitions::class)->plans($group) as $plan) {
                $term = $plan->term;
                if ($term?->start_date === null) {
                    continue;
                }
                $termStart = CarbonImmutable::parse($term->start_date->toDateString(), $timezone)->startOfDay();
                if ($sunday->lt($termStart) || ($term->end_date !== null && $monday->gt(CarbonImmutable::parse($term->end_date->toDateString(), $timezone)))) {
                    continue;
                }
                $first = max(1, $this->termWeek($termStart, $monday));
                $last = max(1, $this->termWeek($termStart, $sunday));
                $topics = CurriculumTopic::query()->where('curriculum_plan_id', $plan->curriculum_plan_id)->where('term_id', $plan->term_id)
                    ->whereNotNull('week_from')->where('week_from', '<=', $last)->whereRaw('COALESCE(week_to, week_from) >= ?', [$first])
                    ->orderBy('sort_order')->orderBy('id')->limit(20)->pluck('name');
                foreach ($topics as $name) {
                    $items[$subject.':'.$name] = ['subject' => $subject, 'topic' => mb_substr((string) $name, 0, 60)];
                }
            }
        }

        return array_slice(array_values($items), 0, (int) config('weekly_digests.growth.max_topics', 5));
    }

    /** Both family blocks, with the model version and audience they were computed under. */
    public function digest(int $learnerId, CarbonImmutable $start): array
    {
        $grew = array_map(fn ($row) => ['topic_id' => $row['topic_id'], 'name' => $row['name'], 'subject' => $row['subject']],
            array_slice($this->grew($learnerId, $start), 0, (int) config('weekly_digests.growth.max_topics', 5)));

        return ['grew_in' => $grew, 'help_at_home' => $this->helpAtHome($learnerId, $start), 'policy_version' => $this->modelVersion(), 'audience' => $this->audience()];
    }

    public static function grewText(array $growth): string
    {
        return implode(', ', array_map(fn ($row) => $row['name'], $growth['grew_in'] ?? []));
    }

    public static function helpText(array $growth): string
    {
        return implode('; ', array_map(fn ($row) => $row['subject'] === '' ? $row['topic'] : $row['subject'].': '.$row['topic'], $growth['help_at_home'] ?? []));
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} */
    private function window(CarbonImmutable $start): array
    {
        $timezone = app(CurrentSchool::class)->school()->timezone;
        $local = CarbonImmutable::parse($start->toDateString(), $timezone)->startOfDay();
        $app = (string) config('app.timezone');

        return [$local->setTimezone($app), $local->addDays(6)->endOfDay()->setTimezone($app)];
    }

    private function score(int $learnerId, int $topicId, array $sources, string $operator, CarbonImmutable $at): array
    {
        $rows = MasteryEvidence::query()->where('learner_id', $learnerId)->where('topic_id', $topicId)->whereIn('audience', $sources)
            ->where('observed_at', $operator, $at)->orderByDesc('observed_at')->orderByDesc('id')->limit(20)->get(['achieved_units', 'maximum_units'])->toArray();

        return app(MasteryScore::class)->calculate($rows);
    }

    private function termWeek(CarbonImmutable $termStart, CarbonImmutable $day): int
    {
        return intdiv((int) floor($termStart->diffInDays($day, false)), 7) + 1;
    }
}
