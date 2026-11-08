<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use App\Models\Learning\PastPaper;
use App\Models\Learning\PastPaperMemo;
use App\Models\Learning\PastPaperVersion;
use App\Models\User;
use App\Services\Learning\PastPaperLibrary;
use App\Services\Learning\PastPaperMemos;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

/**
 * Marking answers against exam coach memos (Action Plan 3.16).
 *
 * preview(): a teacher of the paper's group marks a typed answer against the
 * memo's current mark points (draft or reviewed) to check them. Nothing is
 * stored, and nobody else sees it.
 *
 * learner(): the exam coach / past paper flow's server-side call. It uses only a
 * reviewed memo (PastPaperMemos::reviewed, with the learner's own access checks)
 * and only in a subject whose learner marking is switched on, which no subject
 * is until it passes the accuracy gate (Action Plan 3.18). It returns null
 * otherwise, before marking anything, and never returns the memo's words.
 *
 * Both read the paper through PastPaperLibrary::version, so the group, the
 * school, the licence and the topic references are checked first.
 * engineering/specs/learning/marking-engine.md.
 */
class PaperMarking
{
    public static function rules(): array
    {
        return [
            'question' => ['required', 'string', 'regex:/^[A-Za-z0-9().-]{1,16}$/'],
            'answer' => ['present', 'nullable', 'string', 'max:'.LearnerAnswer::MAX_CHARACTERS, function (string $attribute, mixed $value, \Closure $fail) {
                try {
                    LearnerAnswer::fromText((string) $value);
                } catch (InvalidArgumentException) {
                    $fail(__('Use at most :lines lines of at most :characters characters each.', ['lines' => (string) LearnerAnswer::MAX_LINES, 'characters' => (string) LearnerAnswer::MAX_LINE]));
                }
            }],
        ];
    }

    /** The paper, its questions with whether each can be marked, the memo's state and the subject profile, for the preview page. */
    public function page(User $actor, int $groupId, string $versionId): array
    {
        $library = app(PastPaperLibrary::class);
        $paper = $library->data($actor, $groupId, $versionId, true);
        $version = $library->version($actor, $groupId, $versionId, true);
        $memo = PastPaperMemo::where('past_paper_version_id', $version->id)->first();
        $points = $memo === null ? [] : array_column($memo->mark_points, 'points', 'number');
        $questions = [];
        foreach ($paper['questions'] as $question) {
            $own = $points[(string) $question['number']] ?? [];
            $questions[] = ['number' => (string) $question['number'], 'prompt' => $question['prompt'], 'max_units' => (int) $question['max_units'],
                'markable' => $own !== [] && array_sum(array_column($own, 'mark_units')) === (int) $question['max_units']];
        }
        $profile = $this->profile($version);

        return ['paper' => array_diff_key($paper, ['questions' => true, 'licence' => true]), 'questions' => $questions, 'group_id' => $groupId,
            'memo_state' => $memo?->state ?? 'none', 'profile' => $this->describe($profile)];
    }

    /** Mark a typed answer against the memo's current mark points, for a teacher of the paper's group. Nothing is stored. */
    public function preview(User $actor, int $groupId, string $versionId, array $input): array
    {
        $data = Validator::make($input, self::rules())->validate();
        $version = app(PastPaperLibrary::class)->version($actor, $groupId, $versionId, true);
        $memo = PastPaperMemo::where('past_paper_version_id', $version->id)->first();
        $question = $this->question($version, $memo?->mark_points ?? [], (string) $data['question']);
        $profile = $this->profile($version);

        return ['memo_state' => $memo->state, 'profile' => $this->describe($profile),
            'result' => app(MarkingEngine::class)->mark($question, LearnerAnswer::fromText((string) ($data['answer'] ?? '')), $profile)];
    }

    /**
     * Mark a learner's own answer for the exam coach flow; null unless the memo is reviewed
     * and learner marking is switched on for the subject (off everywhere until Action Plan 3.18).
     */
    public function learner(User $actor, int $groupId, string $versionId, array $input): ?array
    {
        $data = Validator::make($input, self::rules())->validate();
        $memo = app(PastPaperMemos::class)->reviewed($actor, $groupId, $versionId);
        if ($memo === null) {
            return null;
        }
        $profile = $this->profile(PastPaperVersion::findOrFail($memo['version_id']));
        if (! $profile->learnerMarkingEnabled()) {
            return null;
        }
        $question = collect($memo['questions'])->firstWhere('number', (string) $data['question']);
        abort_if($question === null, 404);

        return MarkingEngine::forLearner(app(MarkingEngine::class)->mark($question, LearnerAnswer::fromText((string) ($data['answer'] ?? '')), $profile));
    }

    private function question(PastPaperVersion $version, array $stored, string $number): array
    {
        foreach ($version->definition['questions'] as $question) {
            if ((string) $question['number'] !== $number) {
                continue;
            }
            $points = array_column($stored, 'points', 'number')[$number] ?? [];
            abort_unless($points !== [] && array_sum(array_column($points, 'mark_units')) === (int) $question['max_units'], 409,
                __('Question :number has no complete mark points yet.', ['number' => $number]));

            return ['number' => $number, 'max_units' => (int) $question['max_units'], 'points' => $points];
        }
        abort(404);
    }

    private function profile(PastPaperVersion $version): SubjectProfile
    {
        return app(SubjectProfiles::class)->forSubject((int) PastPaper::whereKey($version->past_paper_id)->value('subject_id'));
    }

    private function describe(SubjectProfile $profile): array
    {
        return ['key' => $profile->key(), 'label' => $profile->label(), 'conventions' => $profile->conventions(), 'learner_marking' => $profile->learnerMarkingEnabled()];
    }
}
