<?php

declare(strict_types=1);

namespace App\Services\Learning\Strengths;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeMemory;
use App\Models\Learning\TutorSession;
use App\Models\Library\LibraryItem;
use App\Models\Pathways\CareerProfile;
use App\Models\Pathways\CareerProfileVersion;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryScore;
use App\Services\Learning\Digests\GrowthMovement;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * The learner's interests and strengths mirror (Action Plan 4.8):
 * where they are growing fastest (velocity, never level), what they remember
 * well, what they chose to explore with the tutor (topic names only), the
 * activities they take part in and the skills from them, reading (a seam
 * until the reading library is built) and a few gentle career ideas.
 *
 * It is a mirror for the learner, not a measure for anyone else:
 *  - The only way in is forLearner(), which refuses every actor except the
 *    signed-in learner themself (StrengthsAccess::learner). There is no
 *    method that takes a learner id, so no other service can read a mirror
 *    for streaming, placement, risk or reporting. A guardian or teacher sees
 *    only a read-only copy the learner chose to share with them by name
 *    (StrengthsShares). StrengthsMirrorGuardTest fails the build if any code
 *    outside this feature uses these classes.
 *  - It holds no numbers that invite comparison: no scores, levels,
 *    percentages, counts, ranks, class or school averages. Order inside a
 *    section is about this learner only.
 *  - Tutor conversations are never read: only the topic and subject a
 *    session was started on.
 *  - Each section follows its own module switch; a switched-off source shows
 *    as not available, never as empty evidence.
 *
 * engineering/specs/learning/strengths-mirror.md.
 */
class StrengthsMirror
{
    public const SECTIONS = ['growing', 'remembering', 'explored', 'activities', 'reading', 'might_enjoy'];

    /** The signed-in learner's own mirror, worked out now. */
    public function forLearner(User $actor): array
    {
        $learner = app(StrengthsAccess::class)->learner($actor);

        return $this->compose($learner, (int) $actor->id);
    }

    private function compose(Students $learner, int $readerId): array
    {
        $gate = app(ModuleGate::class);
        $learnerId = (int) $learner->id;
        $growing = $gate->allows('mastery') ? $this->growing($learnerId) : null;
        $remembering = $gate->allows('daily_practice') ? $this->remembering($learnerId) : null;
        $explored = $gate->allows('tutor') ? $this->explored($learnerId) : null;
        $activities = $gate->allows('groups') ? $this->activities($learnerId) : null;
        $helping = $gate->allows('peer_tutoring') ? $this->helping($learnerId) : null;
        $topicIds = array_merge(array_column($growing ?? [], 'topic_id'), array_column($remembering ?? [], 'topic_id'),
            ...array_map(fn ($subject) => array_column($subject['topics'], 'topic_id'), $explored ?? []));
        $subjectIds = array_merge(array_column($growing ?? [], 'subject_id'), array_column($remembering ?? [], 'subject_id'), array_column($explored ?? [], 'subject_id'));
        $ideas = $gate->allows('pathway') ? $this->ideas($topicIds, $subjectIds) : null;
        $skills = $activities === null && $helping === null ? null
            : array_merge(array_map(fn ($a) => ['kind' => 'leading', 'label' => __('Leading others in :activity', ['activity' => $a['name']])],
                array_values(array_filter($activities ?? [], fn ($a) => $a['role'] === 'captain'))),
                array_map(fn ($name) => ['kind' => 'helping', 'label' => __('Helping others learn :topic', ['topic' => $name])], $helping ?? []));

        return [
            'school_id' => app(CurrentSchool::class)->id(),
            'learner_id' => $learnerId,
            'reader_id' => $readerId,
            'first_name' => (string) ($learner->user?->first_name ?? ''),
            'as_of' => app(AcademicAuthority::class)->today(),
            'privacy' => __('This is yours. Only you can see it, unless you choose to share it.'),
            'sections' => [
                'growing' => $this->section($growing, __('Where you are growing'),
                    __('The topics where your understanding has moved forward most in the last few weeks.'),
                    __('Keep practising. The topics you are growing in will show here.')),
                'remembering' => $this->section($remembering, __('What you remember well'),
                    __('Topics your practice shows you are holding on to.'),
                    __('Your daily practice will show what you remember well.')),
                'explored' => $this->section($explored, __('What you chose to explore'),
                    __('Topics you picked to work on with the tutor this year.'),
                    __('Topics you explore with the tutor will show here.')),
                'activities' => $this->section($activities === null && $helping === null ? null : ($activities ?? []), __('What you take part in'),
                    __('Your activities this year, and the skills you are building in them.'),
                    __('Activities you join will show here.')) + ['skills' => $skills ?? []],
                // Reading arrives with the reading library; until then the section is a clear, empty seam.
                'reading' => $this->section(null, __('What you are reading'), '', '',
                    __('Books you read will show here once the reading library is open at your school.')),
                'might_enjoy' => $this->section($ideas, __('You might enjoy finding out about'),
                    __('Careers that use some of the things you are growing in. These are ideas to explore, never a plan made for you.'),
                    __('Ideas will show here as your strengths grow.')),
            ],
        ];
    }

    /** A section whose source is off ($items null) says so; it is never shown as empty evidence. */
    private function section(?array $items, string $title, string $note, string $empty, ?string $off = null): array
    {
        $message = match (true) {
            $items === null => $off ?? __('This is not switched on at your school yet.'),
            $items === [] => $empty,
            default => $note,
        };

        return ['available' => $items !== null, 'title' => $title, 'message' => $message, 'items' => array_values($items ?? [])];
    }

    /**
     * Velocity, not level: topics whose recent20-v1 mastery rose the most over
     * the window, in the evidence the learner and their family may see (the
     * same rule as the growth digest). A first piece of evidence is not growth.
     *
     * @return list<array{topic_id: int, topic: string, subject_id: int, subject: string}>
     */
    private function growing(int $learnerId): array
    {
        $audience = app(GrowthMovement::class)->audience();
        if ($audience === null) {
            return [];
        }
        $sources = $audience === 'practice' ? ['practice'] : [$audience, 'practice'];
        $now = CarbonImmutable::now();
        $from = $now->subDays((int) config('strengths_mirror.growth_window_days', 42));
        $topicIds = MasteryEvidence::query()->where('learner_id', $learnerId)->whereIn('audience', $sources)->where('observed_at', '>=', $from)
            ->whereIn('topic_id', MasteryRecord::query()->where('learner_id', $learnerId)->where('audience', $audience)->where('policy_version', MasteryScore::VERSION)->select('topic_id'))
            ->distinct()->orderBy('topic_id')->limit(200)->pluck('topic_id')->map(fn ($id) => (int) $id)->all();
        $minimum = (int) config('strengths_mirror.min_score_rise_bp', 500);
        $rises = [];
        foreach ($topicIds as $topicId) {
            try {
                $before = $this->score($learnerId, $topicId, $sources, '<', $from);
                $after = $this->score($learnerId, $topicId, $sources, '<=', $now);
            } catch (InvalidArgumentException) {
                continue;
            }
            if ($before['level'] === null || $after['level'] === null) {
                continue;
            }
            $levels = $after['level'] - $before['level'];
            $rise = $after['score_bp'] - $before['score_bp'];
            if ($levels > 0 || $rise >= $minimum) {
                $rises[$topicId] = [$levels, $rise];
            }
        }
        uksort($rises, fn ($a, $b) => [$rises[$b][0], $rises[$b][1], $a] <=> [$rises[$a][0], $rises[$a][1], $b]);

        return $this->topics(array_slice(array_keys($rises), 0, $this->max()));
    }

    /** @return array{level: ?int, score_bp: ?int} */
    private function score(int $learnerId, int $topicId, array $sources, string $operator, CarbonImmutable $at): array
    {
        $rows = MasteryEvidence::query()->where('learner_id', $learnerId)->where('topic_id', $topicId)->whereIn('audience', $sources)
            ->where('observed_at', $operator, $at)->orderByDesc('observed_at')->orderByDesc('id')->limit(20)->get(['achieved_units', 'maximum_units'])->toArray();

        return app(MasteryScore::class)->calculate($rows);
    }

    /**
     * Topics whose daily-practice memory is stable (FSRS stability) and that
     * the learner would still recall today, most stable first.
     *
     * @return list<array{topic_id: int, topic: string, subject_id: int, subject: string}>
     */
    private function remembering(int $learnerId): array
    {
        $minimum = (float) config('strengths_mirror.remember_min_stability_days', 7);
        $recall = (float) config('strengths_mirror.remember_min_recall', 0.85);
        $today = CarbonImmutable::parse(app(AcademicAuthority::class)->today(), 'UTC');
        $strong = [];
        foreach (PracticeMemory::query()->where('learner_id', $learnerId)->orderBy('id')->limit(2000)->get(['practice_item_id', 'library_item_id', 'state', 'reviewed_on']) as $memory) {
            $state = is_array($memory->state) ? $memory->state : [];
            $stability = $state['stability_days'] ?? null;
            $rating = $state['rating'] ?? null;
            if (! is_numeric($stability) || (float) $stability < $minimum || ! is_int($rating) || $rating < 2) {
                continue;
            }
            $elapsed = max(0, (int) CarbonImmutable::parse($memory->reviewed_on->toDateString(), 'UTC')->diffInDays($today, false));
            if (pow(1 + $elapsed / (9 * (float) $stability), -1) < $recall) {
                continue;
            }
            $key = $memory->practice_item_id !== null ? 'item:'.$memory->practice_item_id : 'library:'.$memory->library_item_id;
            $strong[$key] = max($strong[$key] ?? 0.0, (float) $stability);
        }
        $items = PracticeItem::query()->whereIn('id', $this->ids($strong, 'item:'))->pluck('topic_id', 'id');
        $libraryTopics = LibraryItem::query()->whereIn('id', $this->ids($strong, 'library:'))->pluck('library_topic_id', 'id');
        $schoolTopics = MasteryTopic::query()->whereIn('library_topic_id', $libraryTopics->values()->unique())->orderBy('id')->get(['id', 'library_topic_id'])
            ->groupBy('library_topic_id')->map(fn ($rows) => (int) $rows->first()->id);
        $byTopic = [];
        foreach ($strong as $key => $stability) {
            [$kind, $id] = explode(':', $key);
            $topicId = $kind === 'item' ? $items->get((int) $id) : $schoolTopics->get((int) $libraryTopics->get((int) $id));
            if ($topicId !== null) {
                $byTopic[(int) $topicId] = max($byTopic[(int) $topicId] ?? 0.0, $stability);
            }
        }
        uksort($byTopic, fn ($a, $b) => [$byTopic[$b], $a] <=> [$byTopic[$a], $b]);

        return $this->topics(array_slice(array_keys($byTopic), 0, $this->max()));
    }

    /** @return list<int> */
    private function ids(array $keys, string $prefix): array
    {
        return array_values(array_map(fn ($key) => (int) substr($key, strlen($prefix)), array_filter(array_keys($keys), fn ($key) => str_starts_with($key, $prefix))));
    }

    /**
     * The subjects and topics the learner started tutor sessions on this
     * school year. Only tutor_sessions' subject and topic are read: never a
     * message, answer, photo or anything said in the conversation.
     *
     * @return list<array{subject_id: int, subject: string, topics: list<array{topic_id: int, topic: string}>}>
     */
    private function explored(int $learnerId): array
    {
        $sessions = TutorSession::query()->where('learner_id', $learnerId)->where('session_year_id', (int) schoolSetting('session_year'))
            ->orderByDesc('created_at')->limit(500)->get(['subject_id', 'topic_id']);
        $subjects = Subject::withTrashed()->whereIn('id', $sessions->pluck('subject_id')->unique())->pluck('name', 'id');
        $topics = MasteryTopic::query()->whereIn('id', $sessions->pluck('topic_id')->filter()->unique())->pluck('name', 'id');
        $result = [];
        foreach ($sessions as $session) {
            $subject = (int) $session->subject_id;
            if (! $subjects->has($subject)) {
                continue;
            }
            $result[$subject] ??= ['subject_id' => $subject, 'subject' => mb_substr((string) $subjects[$subject], 0, 60), 'topics' => []];
            $topic = $session->topic_id === null ? null : (int) $session->topic_id;
            if ($topic !== null && $topics->has($topic) && ! in_array($topic, array_column($result[$subject]['topics'], 'topic_id'), true)
                && count($result[$subject]['topics']) < $this->max()) {
                $result[$subject]['topics'][] = ['topic_id' => $topic, 'topic' => mb_substr((string) $topics[$topic], 0, 60)];
            }
        }

        return array_slice(array_values($result), 0, $this->max());
    }

    /**
     * Current memberships of active groups this school year: name, discipline,
     * season and the learner's role (member or captain).
     *
     * @return list<array{activity_group_id: int, name: string, discipline: string, season: string, role: string}>
     */
    private function activities(int $learnerId): array
    {
        $today = app(AcademicAuthority::class)->today();
        $memberships = ActivityMembership::query()->where('learner_id', $learnerId)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->whereIn('activity_group_id', ActivityGroup::query()->where('session_year_id', (int) schoolSetting('session_year'))->where('active', true)->select('id'))
            ->orderBy('activity_group_id')->limit(50)->get(['activity_group_id', 'role']);
        $groups = ActivityGroup::query()->whereIn('id', $memberships->pluck('activity_group_id'))->get(['id', 'name', 'discipline', 'season'])->keyBy('id');
        $result = [];
        foreach ($memberships as $membership) {
            $group = $groups->get($membership->activity_group_id);
            if ($group === null) {
                continue;
            }
            $id = (int) $group->id;
            // A captaincy wins over a plain membership of the same group.
            if (! isset($result[$id]) || $membership->role === 'captain') {
                $result[$id] = ['activity_group_id' => $id, 'name' => mb_substr((string) $group->name, 0, 80), 'discipline' => mb_substr((string) $group->discipline, 0, 60),
                    'season' => mb_substr((string) $group->season, 0, 60), 'role' => $membership->role === 'captain' ? 'captain' : 'member'];
            }
        }

        return array_values($result);
    }

    /**
     * What the learner has taught others in completed, supervised peer
     * tutoring this school year (a topic or an activity skill).
     *
     * @return list<string>
     */
    private function helping(int $learnerId): array
    {
        $sessions = PeerTutoringSession::query()->where('tutor_learner_id', $learnerId)->where('status', 'completed')
            ->where('session_year_id', (int) schoolSetting('session_year'))->orderByDesc('starts_at')->limit(200)->get(['topic_id', 'skill_name']);
        $topics = MasteryTopic::query()->whereIn('id', $sessions->pluck('topic_id')->filter()->unique())->pluck('name', 'id');
        $names = [];
        foreach ($sessions as $session) {
            $name = $session->topic_id !== null ? $topics->get((int) $session->topic_id) : $session->skill_name;
            if (is_string($name) && $name !== '') {
                $names[mb_substr($name, 0, 60)] = true;
            }
        }

        return array_slice(array_keys($names), 0, $this->max());
    }

    /**
     * Up to three current, published career profiles of this school that link
     * a topic or subject from the learner's own strengths. Each comes with the
     * dated public source it was published from. Never a recommendation of
     * subjects or streams.
     *
     * @param  list<int>  $topicIds
     * @param  list<int>  $subjectIds
     * @return list<array{career_profile_id: string, title: string, summary: string, because: string, source_title: string, source_url: string}>
     */
    private function ideas(array $topicIds, array $subjectIds): array
    {
        if ($topicIds === [] && $subjectIds === []) {
            return [];
        }
        $today = app(AcademicAuthority::class)->today();
        $profiles = CareerProfile::query()->where('status', 'published')->whereDate('expires_on', '>=', $today)->orderBy('id')->limit(200)->get(['id', 'current_version_id']);
        $versions = CareerProfileVersion::query()->whereIn('id', $profiles->pluck('current_version_id')->filter())->get()->keyBy('id');
        $topicNames = MasteryTopic::query()->whereIn('id', $topicIds)->pluck('name', 'id');
        $subjectNames = Subject::query()->whereIn('id', $subjectIds)->pluck('name', 'id');
        $matches = [];
        foreach ($profiles as $profile) {
            $version = $versions->get($profile->current_version_id);
            if ($version === null || $version->status !== 'published' || $version->expires_on->toDateString() < $today) {
                continue;
            }
            $details = is_array($version->details) ? $version->details : [];
            $linkedTopics = array_values(array_intersect($topicIds, array_map('intval', $details['topic_ids'] ?? [])));
            $linkedSubjects = array_values(array_intersect($subjectIds, array_map('intval', array_merge(...array_map(fn ($row) => $row['subject_ids'] ?? [], $details['subjects'] ?? [[]])))));
            if ($linkedTopics === [] && $linkedSubjects === []) {
                continue;
            }
            $because = $linkedTopics !== [] ? (string) $topicNames->get($linkedTopics[0], '') : (string) $subjectNames->get($linkedSubjects[0], '');
            $matches[] = ['weight' => [count(array_unique($linkedTopics)), count(array_unique($linkedSubjects))], 'item' => [
                'career_profile_id' => (string) $profile->id, 'title' => mb_substr((string) $version->title, 0, 191),
                'summary' => mb_substr((string) $version->summary, 0, 300), 'because' => mb_substr($because, 0, 60),
                'source_title' => mb_substr((string) $version->source_title, 0, 240), 'source_url' => (string) $version->source_url]];
        }
        usort($matches, fn ($a, $b) => [$b['weight'], $a['item']['title']] <=> [$a['weight'], $b['item']['title']]);

        return array_slice(array_column($matches, 'item'), 0, 3);
    }

    /**
     * Topic ids to names, keeping their order.
     *
     * @param  list<int>  $topicIds
     * @return list<array{topic_id: int, topic: string, subject_id: int, subject: string}>
     */
    private function topics(array $topicIds): array
    {
        $topics = MasteryTopic::query()->whereIn('id', $topicIds)->get(['id', 'name', 'subject_id'])->keyBy('id');
        $subjects = Subject::withTrashed()->whereIn('id', $topics->pluck('subject_id')->unique())->pluck('name', 'id');
        $result = [];
        foreach ($topicIds as $id) {
            $topic = $topics->get($id);
            if ($topic !== null) {
                $result[] = ['topic_id' => (int) $id, 'topic' => mb_substr((string) $topic->name, 0, 60),
                    'subject_id' => (int) $topic->subject_id, 'subject' => mb_substr((string) $subjects->get($topic->subject_id, ''), 0, 60)];
            }
        }

        return $result;
    }

    private function max(): int
    {
        return max(1, (int) config('strengths_mirror.max_items', 5));
    }
}
