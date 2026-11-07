<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\GradeLevel;
use App\Models\Growth\MasteryGapClosure;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasteryTopic;
use App\Models\Growth\MasteryTopicGap;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Term;
use App\Services\Academics\AcademicAuthority;
use App\Services\Learning\Digests\GrowthMovement;
use App\Services\Learning\Motivation\MotivationAuthority;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Stages\SchoolStages;

/**
 * "A topic is now secure" (Action Plan 3.13, `mastery.gap_closed`).
 *
 * Only the mastery the family may see is used: released-mark evidence when
 * the school opted in, otherwise practice and tutor checkpoints ("family" or
 * "practice" audience), on the school's chosen mastery model. Staff-only
 * mastery is never used, because it includes unreleased marks.
 *
 * A topic is a gap when its level is known and below secure (level 3, the
 * boundary MasteryService::roots() uses for "topics to work on"). Unknown is
 * never a gap. The gap closes when the same audience and model version reach
 * secure with at least `min_evidence` pieces of evidence. The learner and
 * their current guardians are then told once per topic per term: the ledger
 * is written first and is unique per learner, topic and term, so a rebuild,
 * a retry or a level that dips and rises again never notifies twice. A
 * recompute that changes nothing changes nothing here.
 */
class GapClosures
{
    public const TYPE = 'mastery.gap_closed';

    public const LINK = '/growth/mastery';

    /** Called inside MasteryRecompute's school/subject mutex after every aggregate. */
    public function observe(int $learnerId, int $subjectId): void
    {
        $audience = app(GrowthMovement::class)->audience();
        if ($audience === null) {
            return;
        }
        $version = (string) app(TopicGraph::class)->settings()['policy_version'];
        $secure = (int) config('motivation.gap_closed.secure_level', 3);
        $minimum = (int) config('motivation.gap_closed.min_evidence', 3);
        $records = MasteryRecord::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->where('audience', $audience)
            ->where('policy_version', $version)->get(['topic_id', 'level', 'evidence_count']);
        if ($records->isEmpty()) {
            return;
        }
        $gaps = MasteryTopicGap::query()->where('learner_id', $learnerId)->whereIn('topic_id', $records->pluck('topic_id'))->get()->keyBy('topic_id');
        foreach ($records as $record) {
            $gap = $gaps->get($record->topic_id);
            if ($gap !== null && ($gap->audience !== $audience || $gap->policy_version !== $version)) {
                // Never compare across audiences or model versions: start again.
                $gap->delete();
                $gap = null;
            }
            if ($record->level === null) {
                continue;
            }
            if ($record->level < $secure) {
                if ($gap === null) {
                    // The school/subject mutex serialises this learner's topic rows.
                    MasteryTopicGap::create(['learner_id' => $learnerId, 'topic_id' => (int) $record->topic_id, 'audience' => $audience, 'policy_version' => $version, 'opened_at' => now()]);
                }

                continue;
            }
            if ($gap !== null && $record->evidence_count >= $minimum) {
                $gap->delete();
                $this->close($learnerId, (int) $record->topic_id, $audience, $version);
            }
        }
    }

    private function close(int $learnerId, int $topicId, string $audience, string $version): void
    {
        $topic = MasteryTopic::query()->findOrFail($topicId);
        $year = (int) schoolSetting('session_year') ?: null;
        $term = $this->term($topic->grade_level_id);
        $key = hash('sha256', implode(':', ['gap_closed', $learnerId, $topicId, $term?->id !== null ? 'term:'.$term->id : 'year:'.($year ?? 0)]));
        if (MasteryGapClosure::query()->where('dedupe_key', $key)->exists()) {
            return;
        }
        $closure = MasteryGapClosure::create(['learner_id' => $learnerId, 'topic_id' => $topicId, 'term_id' => $term?->id, 'session_year_id' => $term?->session_year_id ?? $year,
            'audience' => $audience, 'policy_version' => $version, 'dedupe_key' => $key, 'closed_at' => now()]);
        $learner = Students::query()->findOrFail($learnerId);
        $recipients = app(MotivationAuthority::class)->family($learner);
        if (app(SchoolStages::class)->forLearner($learner) === SchoolStages::EARLY_YEARS) {
            // Early years learners do not sign in; only their guardians are told.
            $recipients = array_values(array_diff($recipients, [(int) $learner->user_id]));
        }
        if ($recipients === []) {
            return;
        }
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::TYPE, __('Something to celebrate'), __('A topic that needed more practice is now secure. Open the mastery map to see it.'), self::LINK);
        $notice->delivery_payload = ['link' => self::LINK, 'motivation_id' => (int) $closure->id];
        $notice->save();
        $closure->update(['notification_id' => $notice->id]);
        $notifier->assign($notice, $recipients);
    }

    /** The topic grade's running term, or the latest that has started (a holiday belongs to the term before it). */
    private function term(?int $gradeLevelId): ?Term
    {
        $today = app(AcademicAuthority::class)->today();
        $sort = $gradeLevelId === null ? null : GradeLevel::query()->whereKey($gradeLevelId)->value('sort_order');

        return Term::query()->whereNotNull('start_date')->whereDate('start_date', '<=', $today)
            ->when($sort !== null, fn ($q) => $q->where('grade_from', '<=', $sort)->where('grade_to', '>=', $sort))
            ->orderByDesc('start_date')->orderByDesc('id')->first();
    }

    /**
     * Topics this learner made secure this school year (the 20 most recent),
     * in plain words: names only, never a score or level, sorted by subject
     * and name.
     *
     * @return list<array{topic_id: int, name: string, subject: string, secured_on: string}>
     */
    public function recent(int $learnerId): array
    {
        $audience = app(GrowthMovement::class)->audience();
        if ($audience === null) {
            return [];
        }
        $rows = MasteryGapClosure::query()->where('learner_id', $learnerId)->where('audience', $audience)->where('session_year_id', (int) schoolSetting('session_year'))
            ->orderByDesc('closed_at')->limit(20)->get();
        $topics = MasteryTopic::query()->whereIn('id', $rows->pluck('topic_id'))->get()->keyBy('id');
        $subjects = Subject::withTrashed()->whereIn('id', $topics->pluck('subject_id'))->pluck('name', 'id');
        $items = [];
        foreach ($rows as $row) {
            $topic = $topics->get($row->topic_id);
            if ($topic === null || isset($items[$row->topic_id])) {
                continue;
            }
            $items[$row->topic_id] = ['topic_id' => (int) $row->topic_id, 'name' => mb_substr((string) $topic->name, 0, 60),
                'subject' => mb_substr((string) $subjects->get($topic->subject_id), 0, 60), 'secured_on' => $row->closed_at->toDateString()];
        }
        $items = array_values($items);
        usort($items, fn ($a, $b) => [$a['subject'], $a['name'], $a['topic_id']] <=> [$b['subject'], $b['name'], $b['topic_id']]);

        return $items;
    }
}
