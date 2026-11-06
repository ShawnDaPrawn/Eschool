<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\TutorMemory as MemoryMark;
use App\Models\Learning\TutorMemoryClear;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorSession;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * The tutor's memory of one learner across sessions (Action Plan 3.10). It is
 * derived, never written by a model: for each subject, how many earlier
 * sessions and learning steps there were, how often the learner explained an
 * idea back, and which published topics they practised. It holds no chat text,
 * so the learner and their guardians can both see it.
 *
 * Rules: it belongs to one learner at one school; only completed tutor turns
 * count, never a turn that was quarantined, purged or flagged for
 * safeguarding; only turns from the current school year and the last
 * ai.tutor.memory_days days count; the learner or a guardian can clear it at
 * any time, after which only newer turns count. Tutor v2 reads the current
 * subject's part, without the current session.
 */
class TutorMemory
{
    private const KINDS = ['hint', 'question', 'correction'];

    public static function retentionDays(): int
    {
        return max(1, min(365, (int) config('ai.tutor.memory_days', 90)));
    }

    public function own(User $actor): array
    {
        $learner = app(TutorAccess::class)->learner($actor, false, false);

        return $this->data($actor, $learner);
    }

    public function forGuardian(User $actor, int $learnerId): array
    {
        return $this->data($actor, $this->guardianLearner($actor, $learnerId));
    }

    public function clearOwn(User $actor, string $operation): array
    {
        return $this->clear($actor, fn () => app(TutorAccess::class)->learner($actor, false, false), 'learner', $operation);
    }

    public function clearForGuardian(User $actor, int $learnerId, string $operation): array
    {
        return $this->clear($actor, fn () => $this->guardianLearner($actor, $learnerId), 'guardian', $operation);
    }

    /** Only an active guardian of the learner at this school; approval is not needed to see or clear. */
    private function guardianLearner(User $actor, int $learnerId): Students
    {
        $fresh = app(TutorAccess::class)->actor($actor, false, false);
        abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 404);
        $learner = Students::findOrFail($learnerId);
        abort_unless(app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id), 404);

        return $learner;
    }

    /**
     * Clears once per operation UUID. Replaying the same operation (a retried
     * request) changes nothing and writes no audit entry, so turns made since
     * the original clear stay in the memory. An operation recorded for another
     * person, learner or route is refused with 409.
     */
    private function clear(User $actor, callable $learner, string $role, string $operation): array
    {
        abort_unless(Str::isUuid($operation), 422);

        return DB::transaction(function () use ($actor, $learner, $role, $operation) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $person = $learner();
            $prior = TutorMemoryClear::where('operation_id', $operation)->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless((int) $prior->cleared_by === (int) $actor->id && (int) $prior->learner_id === (int) $person->id && $prior->cleared_by_role === $role,
                    409, __('This clear request was already used for something else.'));

                return $this->data($actor, $learner());
            }
            $now = now();
            TutorMemoryClear::create(['learner_id' => $person->id, 'cleared_by' => $actor->id, 'cleared_by_role' => $role, 'operation_id' => $operation, 'cleared_at' => $now]);
            $mark = MemoryMark::firstOrNew(['learner_id' => $person->id]);
            $before = $mark->cleared_at?->toIso8601String();
            $mark->fill(['cleared_at' => $now, 'cleared_by' => $actor->id, 'cleared_by_role' => $role])->save();
            Audit::record('tutor.memory_cleared', $mark, ['cleared_at' => $before], ['cleared_at' => $mark->cleared_at->toIso8601String(), 'by' => $role, 'operation_id' => $operation]);

            return $this->data($actor, $learner());
        });
    }

    private function data(User $actor, Students $learner): array
    {
        $mark = MemoryMark::where('learner_id', $learner->id)->first();
        $subjects = [];
        foreach ($this->subjects($learner) as $subjectId => $row) {
            $subjects[] = ['subject_id' => $subjectId, 'subject_name' => (string) Subject::whereKey($subjectId)->value('name')] + $row;
        }

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'learner_id' => (int) $learner->id,
            'retention_days' => self::retentionDays(), 'cleared_at' => $mark?->cleared_at?->toIso8601String(),
            'note' => __('The tutor remembers which topics were practised in the last :days days, not what was said. It can be cleared at any time.', ['days' => self::retentionDays()]),
            'subjects' => $subjects, 'contains_raw_chat' => false];
    }

    /** The current subject's memory for tutor v2, without the session being taught. */
    public function forContext(User $actor, TutorSession $session): ?array
    {
        $learner = Students::findOrFail($session->learner_id);
        $row = $this->subjects($learner, (int) $session->subject_id, (string) $session->id)[(int) $session->subject_id] ?? null;
        if ($row === null) {
            return null;
        }

        return ['earlier_sessions' => $row['sessions'], 'learning_steps' => $row['learning_steps'], 'explained_back' => $row['explained_back'],
            'topics_practised' => array_map(fn ($topic) => app(TutorContext::class)->minimize($actor, $topic['name']), array_slice($row['topics'], 0, 5)),
            'last_active_on' => $row['last_active_on']];
    }

    /** @return array<int, array{sessions: int, learning_steps: int, explained_back: int, topics: list<array{id: int, name: string}>, last_active_on: string}> */
    private function subjects(Students $learner, ?int $subject = null, ?string $exclude = null): array
    {
        $since = $this->since($learner);
        $sessions = TutorSession::where('learner_id', $learner->id)->where('session_year_id', (int) schoolSetting('session_year'))
            ->when($learner->user_id !== null, fn ($q) => $q->where('user_id', $learner->user_id))
            ->when($subject !== null, fn ($q) => $q->where('subject_id', $subject))
            ->when($exclude !== null, fn ($q) => $q->where('id', '!=', $exclude))
            ->get(['id', 'subject_id', 'topic_id'])->keyBy('id');
        if ($sessions->isEmpty()) {
            return [];
        }
        // Flagged or purged operations never count, even when the message row
        // still says completed (a later review can quarantine an operation).
        $flagged = AiUsage::where('feature', 'tutor')->where('learner_id', $learner->id)
            ->where(fn ($q) => $q->where('state', 'quarantined')->orWhereNotNull('quarantine_purged_at'))->select('operation_id');
        $turns = TutorMessage::whereIn('tutor_session_id', $sessions->keys())->where('state', 'completed')->whereNull('purged_at')
            ->whereIn('response_kind', self::KINDS)->where('created_at', '>', $since)->whereNotIn('operation_id', $flagged)
            ->groupBy('tutor_session_id')->selectRaw("tutor_session_id, COUNT(*) AS steps, SUM(CASE WHEN turn_type = 'explain_back' THEN 1 ELSE 0 END) AS explained, MAX(created_at) AS last_at")
            ->get();
        $topics = MasteryTopic::whereIn('id', $sessions->pluck('topic_id')->filter()->unique()->values())->pluck('name', 'id');
        $rows = [];
        foreach ($turns as $turn) {
            $session = $sessions->get($turn->tutor_session_id);
            $id = (int) $session->subject_id;
            $rows[$id] ??= ['sessions' => 0, 'learning_steps' => 0, 'explained_back' => 0, 'topics' => [], 'last_active_on' => ''];
            $rows[$id]['sessions']++;
            $rows[$id]['learning_steps'] += (int) $turn->steps;
            $rows[$id]['explained_back'] += (int) $turn->explained;
            $rows[$id]['last_active_on'] = max($rows[$id]['last_active_on'], substr((string) $turn->last_at, 0, 10));
            if ($session->topic_id !== null && isset($topics[$session->topic_id])) {
                $rows[$id]['topics'][(int) $session->topic_id] = ['id' => (int) $session->topic_id, 'name' => (string) $topics[$session->topic_id]];
            }
        }
        foreach ($rows as &$row) {
            $row['topics'] = array_slice(array_values($row['topics']), 0, 10);
        }
        unset($row);
        uasort($rows, fn ($a, $b) => strcmp($b['last_active_on'], $a['last_active_on']));

        return $rows;
    }

    /** The later of the retention cut-off and the learner's last clear. */
    private function since(Students $learner): CarbonInterface
    {
        $since = now()->subDays(self::retentionDays());
        $cleared = MemoryMark::where('learner_id', $learner->id)->first()?->cleared_at;
        if ($cleared !== null && Carbon::parse($cleared)->greaterThan($since)) {
            return Carbon::parse($cleared);
        }

        return $since;
    }
}
