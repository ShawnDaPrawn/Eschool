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
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class DailyPractice
{
    public function issue(User $actor, int $learner, int $groupId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'practice_on' => ['required', 'date_format:Y-m-d']])->validate();
        $authorize = function () use ($actor, $learner, $groupId, $data) {
            app(PracticeAccess::class)->learner($actor, $learner, $groupId, true);
            abort_unless($data['practice_on'] === app(AcademicAuthority::class)->today(), 409, 'Request the current school day’s practice.');
        };

        $receipt = app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'group_id' => $groupId], $authorize, function () use ($actor, $learner, $groupId, $data) {
            $group = app(PracticeAccess::class)->learner($actor, $learner, $groupId, true);
            $set = PracticeDailySet::where('learner_id', $learner)->where('teaching_group_id', $groupId)->where('practice_on', $data['practice_on'])->first();
            if ($set === null) {
                $choices = $this->candidates($actor, $learner, $group);
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
                    PracticeSetEntry::create(['practice_daily_set_id' => $set->id, 'practice_item_version_id' => $version->id, 'position' => ++$position]);
                    $seconds += $version->estimated_seconds;
                    if ($position === 10) {
                        break;
                    }
                }
            }

            // Replay stores only the immutable set identity; permissions and sources are checked afresh on every read.
            return ['set_id' => (int) $set->id];
        });

        return $this->read($actor, $learner, $receipt['set_id']);
    }

    private function candidates(User $actor, int $learner, TeachingGroup $group): array
    {
        $items = PracticeItem::where('teaching_group_id', $group->id)->where('active', true)->orderBy('id')->limit(1001)->get();
        abort_if($items->count() > 1000, 422, 'The subject practice bank exceeds its bounded catalogue.');
        $versions = PracticeItemVersion::whereIn('id', PracticeItemVersion::selectRaw('MAX(id)')->whereIn('practice_item_id', $items->pluck('id'))->groupBy('practice_item_id'))->get();
        $readable = app(ContentPolicy::class)->readableVersions($actor, $learner)->pluck('id')->all();
        $memories = PracticeMemory::where('learner_id', $learner)->whereIn('practice_item_id', $items->pluck('id'))->get()->keyBy('practice_item_id');
        // Only released-family or own-practice aggregates steer learner choices, never raw staff marks.
        $audience = app(TopicGraph::class)->settings()['family_marks_enabled'] ? 'family' : 'practice';
        $weakness = MasteryRecord::where('learner_id', $learner)->where('subject_id', $group->subject_id)->where('audience', $audience)->pluck('level', 'topic_id');
        $today = app(AcademicAuthority::class)->today();
        $byId = $items->keyBy('id');
        $available = [];
        foreach ($versions as $version) {
            $item = $byId[$version->practice_item_id];
            if (! in_array((int) $version->content_version_id, $readable, true)) {
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

        return array_column($available, 'version');
    }

    private function authorized(User $actor, int $learner, int $setId, bool $write = false): array
    {
        app(PracticeAccess::class)->actor($actor, $write);
        $set = PracticeDailySet::where('learner_id', $learner)->findOrFail($setId);
        $group = app(PracticeAccess::class)->learner($actor, $learner, (int) $set->teaching_group_id, $write);
        $entries = PracticeSetEntry::where('practice_daily_set_id', $setId)->orderBy('position')->get();
        abort_if($entries->count() > 10, 409);
        foreach ($entries as $entry) {
            $version = PracticeItemVersion::findOrFail($entry->practice_item_version_id);
            $item = PracticeItem::where('teaching_group_id', $group->id)->where('active', true)->findOrFail($version->practice_item_id);
            app(PracticeAccess::class)->topic($group, (int) $item->topic_id);
            app(ContentPolicy::class)->reader($actor, $learner, (int) $version->content_version_id);
            $entry->setRelation('approvedVersion', $version);
            $entry->setRelation('approvedItem', $item);
        }

        return [$set, $group, $entries];
    }

    public function read(User $actor, int $learner, int $setId): array
    {
        $school = app(CurrentSchool::class)->id();
        [$set, $group, $entries] = $this->authorized($actor, $learner, $setId);
        $reviews = PracticeReview::whereIn('practice_set_entry_id', $entries->pluck('id'))->get()->keyBy('practice_set_entry_id');
        $seconds = 0;
        $rows = $entries->map(function ($entry) use ($reviews, &$seconds) {
            $version = $entry->approvedVersion;
            $seconds += $version->estimated_seconds;

            // Approved answers are intentionally included for offline self-study, never official assessment grading.
            return ['id' => (int) $entry->id, 'item_id' => (int) $entry->approvedItem->id, 'topic_id' => (int) $entry->approvedItem->topic_id,
                'source_version_id' => (int) $version->content_version_id, 'estimated_seconds' => (int) $version->estimated_seconds,
                'definition' => $version->definition, 'review' => $reviews->has($entry->id) ? $this->reviewData($reviews[$entry->id]) : null];
        })->all();
        $this->authorized($actor, $learner, $setId);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return ['id' => $setId, 'school_id' => $school, 'reader_id' => (int) $actor->id, 'learner_id' => $learner, 'group_id' => (int) $group->id,
            'practice_on' => $set->practice_on->toDateString(), 'offline_until' => $set->offline_until->toISOString(), 'estimated_seconds' => $seconds,
            'completed' => $set->completed_at !== null, 'items' => $rows, 'streak_days' => $this->streak($learner)];
    }

    public function review(User $actor, int $learner, int $setId, int $entryId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'answer' => ['required', 'string', 'max:100'], 'rating' => ['required', 'integer', 'between:2,4']])->validate();
        $authorize = fn () => $this->authorized($actor, $learner, $setId, true);

        return app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'set_id' => $setId, 'entry_id' => $entryId], $authorize, function () use ($actor, $learner, $setId, $entryId, $data) {
            [$set, $group, $entries] = $this->authorized($actor, $learner, $setId, true);
            $entry = $entries->firstWhere('id', $entryId);
            abort_if($entry === null, 404);
            abort_if(PracticeReview::where('practice_set_entry_id', $entryId)->exists(), 409, 'This question was already reviewed. Reload the saved result.');
            $today = app(AcademicAuthority::class)->today();
            abort_if($set->practice_on->toDateString() > $today || CarbonImmutable::parse($set->practice_on->toDateString(), 'UTC')->diffInDays(CarbonImmutable::parse($today, 'UTC')) > 7, 409, 'This offline set is too old to synchronize. Start today’s practice.');
            $correct = app(PracticeAnswers::class)->correct($entry->approvedVersion->definition, $data['answer']);
            $memory = PracticeMemory::where('learner_id', $learner)->where('practice_item_id', $entry->approvedItem->id)->lockForUpdate()->first();
            $elapsed = $memory === null ? 0 : (int) CarbonImmutable::parse($memory->reviewed_on->toDateString(), 'UTC')->diffInDays(CarbonImmutable::parse($today, 'UTC'));
            $schedule = app(FsrsScheduler::class)->review($memory?->state, $correct ? $data['rating'] : 1, $elapsed);
            PracticeMemory::updateOrCreate(['learner_id' => $learner, 'practice_item_id' => $entry->approvedItem->id],
                ['state' => $schedule, 'reviewed_on' => $today, 'due_on' => CarbonImmutable::parse($today)->addDays($schedule['interval_days'])->toDateString()]);
            $review = PracticeReview::create(['learner_id' => $learner, 'practice_set_entry_id' => $entryId, 'answer' => $data['answer'], 'correct' => $correct, 'schedule' => $schedule, 'reviewed_on' => $today]);
            app(MasteryRecompute::class)->practice($review, $group, (int) $entry->approvedItem->topic_id);
            if (PracticeReview::whereIn('practice_set_entry_id', $entries->pluck('id'))->count() === $entries->count()) {
                $set->update(['completed_at' => now()->utc(), 'completed_on' => $today]);
            }

            return $this->reviewData($review) + ['streak_days' => $this->streak($learner)];
        });
    }

    private function reviewData(PracticeReview $review): array
    {
        return ['id' => (int) $review->id, 'entry_id' => (int) $review->practice_set_entry_id, 'correct' => $review->correct,
            'schedule' => $review->schedule, 'reviewed_on' => $review->reviewed_on->toDateString()];
    }

    private function streak(int $learner): int
    {
        $days = PracticeDailySet::where('learner_id', $learner)->whereNotNull('completed_at')->orderByDesc('completed_on')->limit(800)->pluck('completed_on')->map(fn ($date) => $date->toDateString())->unique()->all();
        $day = CarbonImmutable::parse(app(AcademicAuthority::class)->today());
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
