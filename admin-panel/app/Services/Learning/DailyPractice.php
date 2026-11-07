<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryRecord;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\PracticeMemory;
use App\Models\Learning\PracticeReview;
use App\Models\Learning\PracticeSetEntry;
use App\Models\Library\LibraryItemVersion;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\Library\LibraryPractice;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DailyPractice
{
    /**
     * @param  list<string>  $without  question kinds this client cannot answer (a classroom tablet records no audio)
     * @param  int  $contract  PracticeContract::ANSWER_FREE, or LEGACY for an app that has not opted in (choice and number only)
     */
    public function issue(User $actor, int $learner, int $groupId, array $input, array $without = [], int $contract = PracticeContract::ANSWER_FREE): array
    {
        app(PracticeContract::class)->allow($contract);
        $kinds = array_values(array_diff(app(PracticeContract::class)->kinds($contract), $without));
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'practice_on' => ['required', 'date_format:Y-m-d']])->validate();
        $authorize = function () use ($actor, $learner, $groupId, $data) {
            app(PracticeAccess::class)->learner($actor, $learner, $groupId, true);
            abort_unless($data['practice_on'] === app(AcademicAuthority::class)->today(), 409, 'Request the current school day’s practice.');
        };

        $receipt = app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'group_id' => $groupId], $authorize, function () use ($actor, $learner, $groupId, $data, $kinds) {
            $group = app(PracticeAccess::class)->learner($actor, $learner, $groupId, true);
            $set = PracticeDailySet::where('learner_id', $learner)->where('teaching_group_id', $groupId)->where('practice_on', $data['practice_on'])->first();
            if ($set === null) {
                // Only kinds this client can answer, chosen before the library top-up is decided.
                $choices = $this->candidates($actor, $learner, $group, $kinds);
                abort_if($choices === [], 409, 'Your teacher has not approved available practice for this subject yet.');
                $zone = app(CurrentSchool::class)->school()->timezone;
                $set = PracticeDailySet::create(['learner_id' => $learner, 'teaching_group_id' => $groupId, 'practice_on' => $data['practice_on'],
                    'offline_until' => CarbonImmutable::parse($data['practice_on'], $zone)->endOfDay()->utc()]);
                $seconds = 0;
                $position = 0;
                foreach ($choices as $version) {
                    if ($seconds + $version->estimated_seconds > 600) {
                        continue;
                    }
                    $source = $version instanceof LibraryItemVersion ? ['library_item_version_id' => $version->id] : ['practice_item_version_id' => $version->id];
                    PracticeSetEntry::create(['practice_daily_set_id' => $set->id, 'position' => ++$position] + $source);
                    $seconds += $version->estimated_seconds;
                    if ($position === 10) {
                        break;
                    }
                }
            }

            // Replay stores only the immutable set identity; permissions and sources are checked afresh on every read.
            return ['set_id' => (int) $set->id];
        });

        return $this->read($actor, $learner, $receipt['set_id'], $contract);
    }

    /**
     * The school's own approved questions first; when it has fewer than
     * LibraryPractice::MINIMUM_OWN and has switched the library on, published
     * library questions for the group's framework topics follow (3.3).
     *
     * @return list<PracticeItemVersion|LibraryItemVersion>
     */
    private function candidates(User $actor, int $learner, TeachingGroup $group, array $kinds): array
    {
        $items = PracticeItem::usedIn((int) $group->id)->where('active', true)->orderBy('id')->limit(1001)->get();
        abort_if($items->count() > 1000, 422, 'The subject practice bank exceeds its bounded catalogue.');
        $versions = PracticeItemVersion::whereIn('id', PracticeItemVersion::selectRaw('MAX(id)')->whereIn('practice_item_id', $items->pluck('id'))->groupBy('practice_item_id'))->get();
        $readable = app(ContentPolicy::class)->readableVersions($actor, $learner)->pluck('id')->all();
        $memories = PracticeMemory::where('learner_id', $learner)->whereIn('practice_item_id', $items->pluck('id'))->get()->keyBy('practice_item_id');
        // Only released-family or own-practice aggregates steer learner choices, never raw staff marks.
        $settings = app(TopicGraph::class)->settings();
        $audience = $settings['family_marks_enabled'] ? 'family' : 'practice';
        $weakness = MasteryRecord::where('learner_id', $learner)->where('subject_id', $group->subject_id)->where('audience', $audience)
            ->where('policy_version', $settings['policy_version'])->pluck('level', 'topic_id');
        $today = app(AcademicAuthority::class)->today();
        $byId = $items->keyBy('id');
        $available = [];
        foreach ($versions as $version) {
            $item = $byId[$version->practice_item_id];
            if (! in_array((int) $version->content_version_id, $readable, true) || ! in_array($version->definition['kind'] ?? null, $kinds, true)) {
                continue;
            }
            try {
                app(PracticeAccess::class)->topic($group, (int) $item->topic_id);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
            $memory = $memories->get($item->id);
            $available[] = ['version' => $version, 'due' => $memory === null || $memory->due_on->toDateString() <= $today ? 0 : 1,
                'weakness' => $weakness->get($item->topic_id) ?? -1, 'id' => (int) $item->id];
        }
        usort($available, fn ($a, $b) => [$a['due'], $a['weakness'], $a['id']] <=> [$b['due'], $b['weakness'], $b['id']]);
        $own = array_column($available, 'version');
        if (count($own) >= LibraryPractice::MINIMUM_OWN) {
            return $own;
        }

        return [...$own, ...app(LibraryPractice::class)->candidates($learner, $group, $weakness, $today, $kinds)];
    }

    private function authorized(User $actor, int $learner, int $setId, bool $write = false): array
    {
        app(PracticeAccess::class)->actor($actor, $write);
        $set = PracticeDailySet::where('learner_id', $learner)->findOrFail($setId);
        $group = app(PracticeAccess::class)->learner($actor, $learner, (int) $set->teaching_group_id, $write);
        $entries = PracticeSetEntry::where('practice_daily_set_id', $setId)->orderBy('position')->get();
        abort_if($entries->count() > 10, 409);
        foreach ($entries as $entry) {
            if ($entry->library_item_version_id !== null) {
                // A library question: the school's switch, the group's frameworks and the published version, afresh.
                [$version, $item, $topic] = app(LibraryPractice::class)->entry($group, (int) $entry->library_item_version_id);
                $entry->setRelation('approvedVersion', $version);
                $entry->setRelation('approvedItem', $item);
                $entry->setRelation('practiceTopic', $topic);

                continue;
            }
            $version = PracticeItemVersion::findOrFail($entry->practice_item_version_id);
            $item = PracticeItem::usedIn((int) $group->id)->where('active', true)->findOrFail($version->practice_item_id);
            app(PracticeAccess::class)->topic($group, (int) $item->topic_id);
            app(ContentPolicy::class)->reader($actor, $learner, (int) $version->content_version_id);
            $entry->setRelation('approvedVersion', $version);
            $entry->setRelation('approvedItem', $item);
        }

        return [$set, $group, $entries];
    }

    /**
     * A set as one of its group's current teachers sees it, for marking: the
     * teacher's own dated allocation to the set's group, never the learner's
     * reading rights. Entries are bound to the group exactly as for mastery
     * evidence (a school question still used in the group, a library question
     * still offered to it).
     */
    private function staffAuthorized(User $actor, int $learner, int $setId, bool $write): array
    {
        $set = PracticeDailySet::where('learner_id', $learner)->findOrFail($setId);
        $group = app(PracticeAccess::class)->staff($actor, (int) $set->teaching_group_id, $write);
        $entries = PracticeSetEntry::where('practice_daily_set_id', $setId)->orderBy('position')->get();
        foreach ($entries as $entry) {
            if ($entry->library_item_version_id !== null) {
                [$version, $item, $topic] = app(LibraryPractice::class)->entry($group, (int) $entry->library_item_version_id);
                $entry->setRelation('approvedVersion', $version);
                $entry->setRelation('approvedItem', $item);
                $entry->setRelation('practiceTopic', $topic);

                continue;
            }
            $version = PracticeItemVersion::findOrFail($entry->practice_item_version_id);
            $entry->setRelation('approvedVersion', $version);
            $entry->setRelation('approvedItem', PracticeItem::usedIn((int) $group->id)->findOrFail($version->practice_item_id));
        }

        return [$set, $group, $entries];
    }

    public function read(User $actor, int $learner, int $setId, int $contract = PracticeContract::ANSWER_FREE): array
    {
        app(PracticeContract::class)->allow($contract);
        $school = app(CurrentSchool::class)->id();
        [$set, $group, $entries] = $this->authorized($actor, $learner, $setId);
        $legacy = $contract === PracticeContract::LEGACY;
        if ($legacy) {
            // A legacy client reads choice and number questions only; a set holding nothing else for it asks for the update.
            $entries = $entries->filter(fn ($entry) => in_array($this->definitionOf($entry)['kind'] ?? null, PracticeAnswers::SINGLE, true))->values();
            abort_if($entries->isEmpty(), 426, 'Update the app to open this practice.');
        }
        $reviews = PracticeReview::whereIn('practice_set_entry_id', $entries->pluck('id'))->get()->keyBy('practice_set_entry_id');
        $seconds = 0;
        $rows = $entries->map(function ($entry) use ($reviews, &$seconds, $legacy) {
            $version = $entry->approvedVersion;
            $seconds += $version->estimated_seconds;
            if ($legacy) {
                // The legacy contract, exactly as before 3.11: approved answers are included for offline self-study.
                return ['id' => (int) $entry->id, 'item_id' => (int) $entry->approvedItem->id, 'topic_id' => $this->topicOf($entry),
                    'source_version_id' => $this->library($entry) ? (int) $version->id : (int) $version->content_version_id, 'estimated_seconds' => (int) $version->estimated_seconds,
                    'definition' => $this->definitionOf($entry), 'review' => $reviews->has($entry->id) ? $this->legacyReviewData($reviews[$entry->id]) : null];
            }

            // The device gets the question only (Action Plan 3.11): accepted answers, step answers, the label
            // mapping and the explanation arrive with the result, after the server has recorded an answer.
            // A library entry keeps the same shape: its item, mirror topic and library version ids.
            return ['id' => (int) $entry->id, 'item_id' => (int) $entry->approvedItem->id, 'topic_id' => $this->topicOf($entry),
                'source_version_id' => $this->library($entry) ? (int) $version->id : (int) $version->content_version_id, 'estimated_seconds' => (int) $version->estimated_seconds,
                'question' => app(PracticeAnswers::class)->question($this->definitionOf($entry)),
                'review' => $reviews->has($entry->id) ? $this->reviewData($reviews[$entry->id], $entry) : null];
        })->all();
        $this->authorized($actor, $learner, $setId);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return ['id' => $setId, 'school_id' => $school, 'reader_id' => (int) $actor->id, 'learner_id' => $learner, 'group_id' => (int) $group->id,
            'practice_on' => $set->practice_on->toDateString(), 'server_time' => now()->utc()->toISOString(), 'offline_until' => $set->offline_until->toISOString(), 'estimated_seconds' => $seconds,
            'completed' => $set->completed_at !== null, 'items' => $rows, 'streak_days' => $this->streak($learner)];
    }

    /**
     * Record and mark one answer (Action Plan 3.11). An answer the server can
     * mark is marked now; a short written answer that matches no accepted
     * answer, or a spoken answer, waits for the learner's teacher
     * (PracticeMarking). Either way the result and explanation are returned
     * only now that the answer is recorded, and memory and mastery evidence
     * are written once, when the answer is marked.
     */
    public function review(User $actor, int $learner, int $setId, int $entryId, array $input, ?UploadedFile $audio = null, int $contract = PracticeContract::ANSWER_FREE): array
    {
        app(PracticeContract::class)->allow($contract);
        $legacy = $contract === PracticeContract::LEGACY;
        abort_if($legacy && $audio !== null, 422, 'Update the app to record a spoken answer.');
        $data = Validator::make($input, $legacy
            ? ['operation_id' => ['required', 'uuid'], 'answer' => ['required', 'string', 'max:100'], 'rating' => ['required', 'integer', 'between:2,4']]
            : ['operation_id' => ['required', 'uuid'], 'answer' => ['nullable', 'string', 'max:'.PracticeAnswers::SHORT_TEXT_MAX],
                'parts' => ['nullable', 'array', 'max:16'], 'parts.*' => ['required', 'string', 'max:100'], 'rating' => ['required', 'integer', 'between:2,4']])->validate();
        $authorize = fn () => $this->authorized($actor, $learner, $setId, true);
        $recording = null;
        if ($audio !== null) {
            $authorize();
            $recording = app(PracticeMedia::class)->inspectAudio($audio);
        }
        // A legacy receipt hashes exactly what it did before 3.11, so retried pre-update operations still replay.
        $operation = $legacy ? $data + ['learner_id' => $learner, 'set_id' => $setId, 'entry_id' => $entryId]
            : array_intersect_key($data, array_flip(['operation_id', 'answer', 'parts', 'rating'])) + ['learner_id' => $learner, 'set_id' => $setId, 'entry_id' => $entryId, 'contract' => $contract]
                + ($recording === null ? [] : ['audio_sha256' => $recording['sha256']]);

        return app(PracticeOperations::class)->run($actor, $operation, $authorize, function () use ($actor, $learner, $setId, $entryId, $data, $recording, $legacy) {
            [$set, $group, $entries] = $this->authorized($actor, $learner, $setId, true);
            $entry = $entries->firstWhere('id', $entryId);
            abort_if($entry === null || ($legacy && ! in_array($this->definitionOf($entry)['kind'] ?? null, PracticeAnswers::SINGLE, true)), 404);
            abort_if(PracticeReview::where('practice_set_entry_id', $entryId)->exists(), 409, 'This question was already reviewed. Reload the saved result.');
            $today = app(AcademicAuthority::class)->today();
            abort_if($set->practice_on->toDateString() > $today || CarbonImmutable::parse($set->practice_on->toDateString(), 'UTC')->diffInDays(CarbonImmutable::parse($today, 'UTC')) > 7, 409, 'This offline set is too old to synchronize. Start today’s practice.');
            $mark = app(PracticeAnswers::class)->mark($this->definitionOf($entry), ['answer' => $data['answer'] ?? null, 'parts' => $data['parts'] ?? null, 'audio' => $recording !== null]);
            $marked = $mark['state'] === 'marked';
            $review = PracticeReview::create(['learner_id' => $learner, 'practice_set_entry_id' => $entryId, 'answer' => $mark['stored'], 'correct' => $mark['correct'],
                'state' => $mark['state'], 'achieved_units' => $mark['achieved'], 'maximum_units' => $mark['maximum'], 'parts' => $mark['parts'], 'rating' => (int) $data['rating'],
                'marked_how' => 'automatic', 'marked_at' => $marked ? now('UTC') : null, 'schedule' => null, 'reviewed_on' => $today]);
            if ($recording !== null) {
                app(PracticeMedia::class)->storeAudio((int) $review->id, $learner, $recording, $today);
            }
            if ($marked) {
                $this->settle($review, $entry, $group, $today);
            }
            if (PracticeReview::whereIn('practice_set_entry_id', $entries->pluck('id'))->count() === $entries->count()) {
                $set->update(['completed_at' => now()->utc(), 'completed_on' => $today]);
            }

            return ($legacy ? $this->legacyReviewData($review->refresh()) : $this->reviewData($review->refresh(), $entry)) + ['streak_days' => $this->streak($learner)];
        });
    }

    /**
     * The diagram picture of a labelling question in the learner's own set,
     * authorized exactly as reading the set is.
     */
    public function diagram(User $actor, int $learner, int $setId, int $entryId): string
    {
        $school = app(CurrentSchool::class)->id();
        [, , $entries] = $this->authorized($actor, $learner, $setId);
        $entry = $entries->firstWhere('id', $entryId);
        abort_if($entry === null, 404);
        $definition = app(PracticeAnswers::class)->definition($this->definitionOf($entry));
        abort_unless($definition['kind'] === 'diagram_label' && $definition['diagram']['source'] === ($this->library($entry) ? 'library' : 'school'), 404);
        $bytes = app(PracticeMedia::class)->diagram($definition['diagram']);
        $this->authorized($actor, $learner, $setId);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return $bytes;
    }

    /**
     * A marked answer's memory schedule and mastery evidence, once: called in
     * the same transaction that marks it, by the server or by a teacher.
     */
    public function settle(PracticeReview $review, PracticeSetEntry $entry, TeachingGroup $group, string $today): void
    {
        abort_unless($review->state === 'marked' && $review->correct !== null && $review->schedule === null, 409);
        $key = $this->library($entry) ? ['library_item_id' => (int) $entry->approvedItem->id] : ['practice_item_id' => (int) $entry->approvedItem->id];
        $memory = PracticeMemory::where('learner_id', $review->learner_id)->where($key)->lockForUpdate()->first();
        $elapsed = $memory === null ? 0 : max(0, (int) CarbonImmutable::parse($memory->reviewed_on->toDateString(), 'UTC')->diffInDays(CarbonImmutable::parse($today, 'UTC')));
        $schedule = app(FsrsScheduler::class)->review($memory?->state, $review->correct ? (int) $review->rating : 1, $elapsed);
        PracticeMemory::updateOrCreate(['learner_id' => $review->learner_id] + $key,
            ['state' => $schedule, 'reviewed_on' => $today, 'due_on' => CarbonImmutable::parse($today)->addDays($schedule['interval_days'])->toDateString()]);
        $review->forceFill(['schedule' => $schedule])->save();
        app(MasteryRecompute::class)->practice($review, $group, $this->topicOf($entry));
    }

    /**
     * One entry of a set, with its approved version, item and topic, authorized
     * for the learner afresh (PracticeMarking reads entries this way too).
     */
    public function entryFor(User $actor, int $learner, int $setId, int $entryId, bool $write = false, bool $staff = false): array
    {
        [$set, $group, $entries] = $staff ? $this->staffAuthorized($actor, $learner, $setId, $write) : $this->authorized($actor, $learner, $setId, $write);
        $entry = $entries->firstWhere('id', $entryId);
        abort_if($entry === null, 404);

        return [$set, $group, $entry, $this->definitionOf($entry)];
    }

    private function library(PracticeSetEntry $entry): bool
    {
        return $entry->library_item_version_id !== null;
    }

    /** The school's own topic node, or the school's mirror node of the library topic. */
    private function topicOf(PracticeSetEntry $entry): int
    {
        return $this->library($entry) ? (int) $entry->practiceTopic->id : (int) $entry->approvedItem->topic_id;
    }

    private function definitionOf(PracticeSetEntry $entry): array
    {
        return $this->library($entry) ? (array) $entry->approvedVersion->practiceDefinition() : $entry->approvedVersion->definition;
    }

    /** The legacy contract's result, exactly as before 3.11 (choice and number answers, always marked at once). */
    private function legacyReviewData(PracticeReview $review): array
    {
        return ['id' => (int) $review->id, 'entry_id' => (int) $review->practice_set_entry_id, 'correct' => (bool) $review->correct,
            'schedule' => $review->schedule, 'reviewed_on' => $review->reviewed_on->toDateString()];
    }

    /** The recorded result: only ever returned once the answer is recorded, so it may carry the solution. */
    private function reviewData(PracticeReview $review, PracticeSetEntry $entry): array
    {
        return ['id' => (int) $review->id, 'entry_id' => (int) $review->practice_set_entry_id, 'state' => $review->state, 'correct' => $review->correct,
            'achieved_units' => $review->achieved_units, 'maximum_units' => $review->maximum_units, 'parts' => $review->parts,
            'marked_how' => $review->marked_how, 'schedule' => $review->schedule, 'reviewed_on' => $review->reviewed_on->toDateString(),
            'solution' => app(PracticeAnswers::class)->solution($this->definitionOf($entry))];
    }

    private function streak(int $learner): int
    {
        $day = CarbonImmutable::parse(app(AcademicAuthority::class)->today());
        // Bound calendar dates before distinct, rather than truncating multi-subject rows.
        $days = PracticeDailySet::where('learner_id', $learner)->whereNotNull('completed_at')
            ->whereBetween('completed_on', [$day->subDays(365)->toDateString(), $day->toDateString()])
            ->distinct()->orderByDesc('completed_on')->pluck('completed_on')
            ->map(fn ($date) => $date->toDateString())->all();
        if (! in_array($day->toDateString(), $days, true)) {
            $day = $day->subDay();
        }
        $count = 0;
        while ($count < 365 && in_array($day->toDateString(), $days, true)) {
            $count++;
            $day = $day->subDay();
        }

        return $count;
    }
}
