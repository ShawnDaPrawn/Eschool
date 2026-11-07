<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PracticeAudioAnswer;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeReview;
use App\Models\Learning\PracticeSetEntry;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The teacher's practice marking queue (Action Plan 3.11): short written
 * answers that matched no accepted answer and recorded spoken answers wait
 * here for one of the current teachers of the group the learner answered in.
 * Nobody else (another teacher, a learner, a parent, another school) can see,
 * hear or mark them. A teacher's mark is written once and feeds the learner's
 * practice memory and mastery evidence exactly as an automatic mark does.
 */
class PracticeMarking
{
    /** @return array{group_id: int, items: list<array<string, mixed>>, page: int, last_page: int, total: int} */
    public function queue(User $actor, int $groupId, int $page = 1): array
    {
        $school = app(CurrentSchool::class)->id();
        $group = app(PracticeAccess::class)->staff($actor, $groupId);
        $reviews = $this->waiting($groupId)->orderBy('id')->paginate(25, ['*'], 'page', max(1, $page));
        $entries = PracticeSetEntry::query()->whereIn('id', $reviews->pluck('practice_set_entry_id'))->get()->keyBy('id');
        $sets = PracticeDailySet::query()->whereIn('id', $entries->pluck('practice_daily_set_id'))->where('teaching_group_id', $group->id)->get()->keyBy('id');
        $learners = Students::query()->whereIn('id', $reviews->pluck('learner_id'))->with('user:id,first_name,last_name')->get()->keyBy('id');
        $audio = PracticeAudioAnswer::query()->whereIn('practice_review_id', $reviews->pluck('id'))->get()->keyBy('practice_review_id');
        $items = [];
        foreach ($reviews as $review) {
            $entry = $entries->get($review->practice_set_entry_id);
            $set = $entry === null ? null : $sets->get($entry->practice_daily_set_id);
            abort_if($set === null, 409);
            try {
                [, , , $definition] = app(DailyPractice::class)->entryFor($actor, (int) $review->learner_id, (int) $set->id, (int) $entry->id, false, true);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // The question no longer reaches this group (stopped or switched off): it cannot be marked here.
                continue;
            }
            $definition = app(PracticeAnswers::class)->definition($definition);
            $recording = $audio->get($review->id);
            $items[] = ['id' => (int) $review->id, 'learner_id' => (int) $review->learner_id, 'learner_name' => (string) $learners->get($review->learner_id)?->user?->full_name,
                'kind' => $definition['kind'], 'prompt' => $definition['prompt'], 'answer' => $definition['kind'] === 'short_text' ? (string) $review->answer : null,
                'accepted' => $definition['answers'], 'explanation' => $definition['explanation'], 'answered_on' => $review->reviewed_on->toDateString(),
                'audio' => $recording === null ? null : ['kept' => $recording->purged_at === null, 'mime' => $recording->mime, 'bytes' => (int) $recording->bytes,
                    'delete_after' => $recording->purge_after->toDateString()],
                // The AI suggestion seam stays empty until a provider is qualified (ShortTextAssist).
                'suggestion' => $definition['kind'] === 'short_text' ? app(ShortTextAssist::class)->suggest($definition, (string) $review->answer) : null];
        }
        app(PracticeAccess::class)->staff($actor, $groupId);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return ['group_id' => (int) $group->id, 'items' => $items, 'page' => $reviews->currentPage(), 'last_page' => $reviews->lastPage(), 'total' => $reviews->total()];
    }

    /** How many answers wait in the group (for the teacher's own groups only). */
    public function count(User $actor, int $groupId): int
    {
        app(PracticeAccess::class)->staff($actor, $groupId);

        return $this->waiting($groupId)->count();
    }

    /**
     * A waiting spoken answer's recording, for the group's current teachers.
     *
     * @return array{bytes: string, mime: string}
     */
    public function audio(User $actor, int $groupId, int $reviewId): array
    {
        $school = app(CurrentSchool::class)->id();
        [$review] = $this->locate($actor, $groupId, $reviewId, false, false);
        $recording = PracticeAudioAnswer::query()->where('practice_review_id', $review->id)->where('learner_id', $review->learner_id)->firstOrFail();
        $bytes = app(PracticeMedia::class)->audio($recording);
        abort_if($bytes === null, 410, __('This recording has been deleted under the retention rule.'));
        $this->locate($actor, $groupId, $reviewId, false, false);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);
        Audit::record('learning.practice_audio_heard', $review, [], ['teaching_group_id' => $groupId]);

        return ['bytes' => $bytes, 'mime' => $recording->mime];
    }

    /**
     * The teacher's mark for one waiting answer: correct or not yet. Marked
     * once: a replay of the same operation returns the same receipt, and any
     * other attempt to mark it again is refused.
     *
     * @param  array<string, mixed>  $input
     * @return array{id: int, state: string, correct: bool, marked_how: string}
     */
    public function mark(User $actor, int $groupId, int $reviewId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'correct' => ['required', 'boolean']])->validate();
        $correct = filter_var($data['correct'], FILTER_VALIDATE_BOOLEAN);
        $authorize = fn () => $this->locate($actor, $groupId, $reviewId, true, false);

        return app(PracticeOperations::class)->run($actor, ['operation_id' => $data['operation_id'], 'correct' => $correct, 'review_id' => $reviewId, 'group_id' => $groupId], $authorize,
            function () use ($actor, $groupId, $reviewId, $correct) {
                [, $set, $entry, $group] = $this->locate($actor, $groupId, $reviewId, true, false);
                $review = PracticeReview::query()->lockForUpdate()->findOrFail($reviewId);
                abort_unless($review->state === 'awaiting_teacher', 409, __('This answer has already been marked.'));
                $today = app(AcademicAuthority::class)->today();
                $review->forceFill(['state' => 'marked', 'correct' => $correct, 'achieved_units' => $correct ? 1 : 0, 'maximum_units' => 1,
                    'marked_how' => 'teacher', 'marked_by' => $actor->id, 'marked_at' => now('UTC')])->save();
                app(DailyPractice::class)->settle($review, $entry, $group, $today);
                $recording = PracticeAudioAnswer::query()->where('practice_review_id', $review->id)->whereNull('purged_at')->first();
                $soon = CarbonImmutable::parse($today)->addDays(PracticeMedia::AUDIO_KEEP_AFTER_MARKING_DAYS)->toDateString();
                if ($recording !== null && $recording->purge_after->toDateString() > $soon) {
                    $recording->forceFill(['purge_after' => $soon])->save();
                }
                Audit::record('learning.practice_marked', $review, ['state' => 'awaiting_teacher'], ['state' => 'marked', 'correct' => $correct, 'set_id' => (int) $set->id]);

                return ['id' => (int) $review->id, 'state' => 'marked', 'correct' => $correct, 'marked_how' => 'teacher'];
            });
    }

    /** @return Builder<PracticeReview> */
    private function waiting(int $groupId): Builder
    {
        return PracticeReview::query()->where('state', 'awaiting_teacher')
            ->whereIn('practice_set_entry_id', PracticeSetEntry::query()->select('id')
                ->whereIn('practice_daily_set_id', PracticeDailySet::query()->select('id')->where('teaching_group_id', $groupId)));
    }

    /**
     * A review in this group, for one of its current teachers: the actor's
     * dated allocation first, then the review found only through this group's
     * sets, then its entry authorized afresh.
     *
     * @return array{0: PracticeReview, 1: PracticeDailySet, 2: PracticeSetEntry, 3: TeachingGroup}
     */
    private function locate(User $actor, int $groupId, int $reviewId, bool $write, bool $waiting = true): array
    {
        app(PracticeAccess::class)->staff($actor, $groupId, $write);
        $review = PracticeReview::query()->whereKey($reviewId)->whereIn('practice_set_entry_id', PracticeSetEntry::query()->select('id')
            ->whereIn('practice_daily_set_id', PracticeDailySet::query()->select('id')->where('teaching_group_id', $groupId)))->firstOrFail();
        abort_if($waiting && $review->state !== 'awaiting_teacher', 404);
        $entry = PracticeSetEntry::query()->findOrFail($review->practice_set_entry_id);
        [$set, $group, $entry] = app(DailyPractice::class)->entryFor($actor, (int) $review->learner_id, (int) $entry->practice_daily_set_id, (int) $entry->id, $write, true);
        abort_unless((int) $group->id === $groupId, 404);

        return [$review, $set, $entry, $group];
    }
}
