<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\Activities\AcademicAdjudicator;
use App\Models\Activities\AcademicReasoningReview;
use App\Models\Activities\FixtureSelection;
use App\Models\Activities\FixtureSide;
use App\Models\Platform\School;
use App\Models\Shared\SharedFixture;
use App\Models\Shared\SharedParticipant;
use App\Models\Students;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Learning\TutorContext;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Shared\SharedRecords;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * AI feedback on the reasoning in a speech transcript or written argument,
 * for a human adjudicator of an academic competition (Action Plan 5.3).
 *
 * Advice only: the feedback is shown to the adjudicator who asked (and the
 * school's activity managers). It never decides or changes a result, never
 * carries a score, and never reaches a learner or family by itself; an
 * adjudicator may write feedback of their own for one of their school's
 * entrants, and is responsible for what they share.
 *
 * Before anything is stored or sent, the text is redacted: both schools'
 * names, team names, this school's entrants and adjudicators, the other
 * school's shared sheet labels, the asking adult, emails and numbers. The
 * model sees only the competition kind, format, motion, stance and the
 * redacted text, never a school or learner name. The redacted text is kept
 * only until the feedback is written. Everything else is the gateway's:
 * the platform and school switches (off by default), the daily budgets, the
 * safeguarding phrase guard and moderation with quarantine, and receipts
 * that hold references only.
 */
class ReasoningReviews
{
    public const FEATURE = 'reasoning_feedback';

    public const PROMPT = 'reasoning-feedback';

    public const MAXIMUM_TEXT = 12000;

    /** Words of school and team names that are ordinary words in a speech, so are not redacted on their own. */
    private const ORDINARY_WORDS = ['the', 'and', 'for', 'school', 'schools', 'high', 'primary', 'secondary', 'college', 'academy', 'team', 'club', 'society', 'junior', 'senior',
        'girls', 'boys', 'international', 'state', 'community', 'grammar', 'preparatory', 'prep', 'combined', 'comprehensive', 'house', 'under', 'first', 'second', 'third',
        'debaters', 'debating', 'debate', 'speakers', 'speaker', 'quiz', 'quizzers', 'speech', 'outside', 'judge'];

    public function __construct(private SharedRecords $shared, private AcademicContests $contests) {}

    public static function rules(string $action): array
    {
        return match ($action) {
            'review' => ['operation_id' => ['required', 'uuid'], 'text' => ['required', 'string', 'min:50', 'max:'.self::MAXIMUM_TEXT],
                'source' => ['required', Rule::in(['speech', 'written'])], 'from_side' => ['required', Rule::in(['ours', 'theirs'])]],
            'share' => ['operation_id' => ['required', 'uuid'], 'learner_id' => ['required', 'integer', 'min:1'], 'text' => ['required', 'string', 'min:20', 'max:4000']],
            default => [],
        };
    }

    /**
     * Who may ask: a judge (manager or named adjudicator) of an accepted
     * competition whose kind allows it, with the school's switch on.
     *
     * @return array{0: SharedFixture, 1: SharedParticipant, 2: FixtureSide|null, 3: array<string, bool>}
     */
    private function judge(User $actor, int $id): array
    {
        $found = $this->contests->judge($actor, $id);
        [$fixture, $part] = $found;
        abort_unless(AcademicContests::kind((string) $fixture->kind)['reasoning_feedback'], 422, 'AI feedback on reasoning is not offered for this kind of competition.');
        abort_unless(app(ModuleGate::class)->allows(AcademicContests::MODULE), 403, 'AI feedback on reasoning is switched off for your school.');
        abort_unless($part->state === SharedParticipant::ACCEPTED, 409, 'Accept the competition first.');

        return $found;
    }

    public function request(User $actor, int $id, array $input): array
    {
        $data = Validator::make($input, self::rules('review'))->validate();
        [$fixture, $part, $side] = $this->judge($actor, $id);
        // Off by default: nothing is stored, redacted or sent while the platform feature is off.
        abort_unless($this->contests->feedbackSwitchedOn(), 409, 'AI feedback on reasoning is not switched on.');
        $stance = $this->contests->stance($fixture, $data['from_side'] === 'ours' ? $part->role : ($part->role === 'host' ? 'guest' : 'host'));
        $prepared = $this->input($actor, $fixture, $side, $stance, $data);
        $hash = hash('sha256', $prepared);
        // Only the request that creates the review calls the model. A retry of the same
        // operation (even while the first call is still running) returns the review as
        // it stands, so it can never consume, fail or clear the first request's call.
        $created = false;
        $review = DB::transaction(function () use ($actor, $id, $data, $prepared, $hash, $stance, &$created) {
            School::query()->whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->judge($actor, $id);
            $old = AcademicReasoningReview::query()->where('operation_id', $data['operation_id'])->first();
            if ($old !== null) {
                abort_unless((int) $old->requested_by === (int) $actor->id && (int) $old->shared_fixture_id === $id && hash_equals($old->input_sha256, $hash), 409, 'This operation already contains a different request.');

                return $old;
            }
            $row = AcademicReasoningReview::query()->create(['shared_fixture_id' => $id, 'requested_by' => $actor->id, 'operation_id' => $data['operation_id'], 'source' => $data['source'],
                'stance' => $stance, 'from_side' => $data['from_side'], 'state' => 'pending', 'input' => $prepared, 'input_sha256' => $hash]);
            Audit::record('academic.reasoning_requested', $row, [], ['shared_fixture_id' => $id, 'source' => $row->source, 'from_side' => $row->from_side]);
            $created = true;

            return $row;
        });
        if ($created) {
            $this->run($actor, $review);
        }

        return $this->present($review->refresh());
    }

    /** One gateway call; whatever happens, the redacted text is not kept afterwards. */
    private function run(User $actor, AcademicReasoningReview $review): void
    {
        $feedback = null;
        $failure = null;
        try {
            $response = app(AiGateway::class)->runFeature($actor, Capability::Complete, new FeatureInferenceRequest(self::FEATURE, (string) $review->operation_id, ['review_id' => (int) $review->id]));
            $feedback = ReasoningFeedbackModeration::parse($response->output);
            if ($feedback === null) {
                $failure = 'reasoning_feedback_output_invalid';
            }
        } catch (AiUnavailable $error) {
            $failure = $error->reason;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            $failure = 'inference_refused';
        } catch (Throwable $error) {
            Log::warning('reasoning_feedback_unavailable', ['exception_class' => $error::class]);
            $failure = 'inference_unavailable';
        }
        DB::transaction(function () use ($review, $feedback, $failure) {
            $row = AcademicReasoningReview::query()->lockForUpdate()->findOrFail($review->id);
            if ($row->state !== 'pending') {
                return;
            }
            $state = $feedback !== null ? 'completed' : ($failure === 'inference_quarantined' ? 'quarantined' : 'unavailable');
            $row->forceFill(['state' => $state, 'feedback' => $feedback, 'failure_code' => $feedback === null ? substr((string) $failure, 0, 64) : null, 'input' => null])->save();
            Audit::record('academic.reasoning_finished', $row, ['state' => 'pending'], ['state' => $state]);
        });
    }

    /**
     * What the model receives: the kind, format, motion and stance, and the
     * redacted text. No school, team, learner or adult name, and no id.
     */
    private function input(User $actor, SharedFixture $fixture, ?FixtureSide $side, ?string $stance, array $data): string
    {
        $redact = $this->redactor($actor, $fixture, $side);

        return json_encode(['task' => 'reasoning_feedback', 'kind' => (string) $fixture->kind, 'format' => $fixture->format === null ? null : $redact($fixture->format),
            'motion' => $redact((string) $fixture->motion), 'stance' => $stance, 'source' => $data['source'], 'text' => $redact(trim($data['text']))],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** @return callable(string): string */
    private function redactor(User $actor, SharedFixture $fixture, ?FixtureSide $side): callable
    {
        $id = (int) $fixture->id;
        $names = [];
        $participants = $this->shared->participants(Fixtures::TYPE, $id);
        foreach ($participants as $row) {
            $names[] = $row['name'];
        }
        $names[] = (string) $fixture->external_opponent;
        if ($side !== null) {
            $names[] = (string) $side->team_label;
            $learners = FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->get(['learner_id', 'sheet_label']);
            foreach ($learners as $row) {
                $names[] = (string) $row->sheet_label;
            }
            foreach (Students::withTrashed()->with('user')->whereIn('id', $learners->pluck('learner_id'))->get() as $learner) {
                array_push($names, (string) $learner->user?->first_name, (string) $learner->user?->last_name, (string) $learner->admission_no);
            }
        }
        foreach (AcademicAdjudicator::query()->where('shared_fixture_id', $id)->get() as $row) {
            $user = $row->user_id === null ? null : User::query()->find($row->user_id);
            array_push($names, (string) $row->name, (string) $user?->first_name, (string) $user?->last_name);
        }
        // The other school's team name and shared sheet labels, read through the boundary once both accepted.
        $other = $participants->first(fn ($row) => $row['school_id'] !== app(CurrentSchool::class)->id() && $row['state'] === SharedParticipant::ACCEPTED);
        if ($other !== null && $this->shared->open(Fixtures::TYPE, $id)) {
            $theirs = $this->shared->published(FixtureSide::class, $other['school_id'], ['id', 'team_label', 'sheet_shared'], fn ($q) => $q->where('shared_fixture_id', $id))->first();
            if ($theirs !== null) {
                $names[] = (string) $theirs->team_label;
                if ($theirs->sheet_shared) {
                    foreach ($this->shared->published(FixtureSelection::class, $other['school_id'], ['sheet_label'], fn ($q) => $q->where('shared_fixture_side_id', $theirs->id)) as $row) {
                        $names[] = (string) $row->sheet_label;
                    }
                }
            }
        }
        $tokens = [];
        foreach ($names as $name) {
            // A whole name, and each of its words long enough to be a name ("Asha D." gives
            // "Asha", "Baobab High" gives "Baobab"), but not ordinary words like "High".
            foreach ([trim($name), ...preg_split('/[\s.,]+/u', trim($name)) ?: []] as $i => $token) {
                if (mb_strlen($token) >= 3 && ($i === 0 || ! in_array(mb_strtolower($token), self::ORDINARY_WORDS, true))) {
                    $tokens[mb_strtolower($token)] = $token;
                }
            }
        }
        uksort($tokens, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return function (string $text) use ($actor, $tokens): string {
            $text = app(TutorContext::class)->minimize($actor, $text);
            foreach ($tokens as $token) {
                $text = preg_replace('/(?<![\pL\pN])'.preg_quote($token, '/').'(?![\pL\pN])/iu', '[name]', $text) ?? '';
            }

            return $text;
        };
    }

    /** The review for the adjudicator who asked, or this school's activity managers. */
    public function show(User $actor, int $id, int $reviewId): array
    {
        return $this->present($this->mine($actor, $id, $reviewId));
    }

    private function mine(User $actor, int $id, int $reviewId, bool $write = false): AcademicReasoningReview
    {
        [, , , $roles] = $this->contests->judge($actor, $id, $write);
        $review = AcademicReasoningReview::query()->where('shared_fixture_id', $id)->findOrFail($reviewId);
        abort_unless($roles['manages'] || (int) $review->requested_by === (int) $actor->id, 404);

        return $review;
    }

    /**
     * The adjudicator who asked writes feedback for one of their own school's
     * entrants (on a review of that school's own side). It is their words and
     * their responsibility; the AI's text is never shared by itself.
     */
    public function share(User $actor, int $id, int $reviewId, array $input): array
    {
        $data = Validator::make($input, self::rules('share'))->validate();
        $review = $this->mine($actor, $id, $reviewId, true);
        abort_unless((int) $review->requested_by === (int) $actor->id, 403, 'Only the adjudicator who asked for this feedback can share feedback from it.');
        abort_unless($review->from_side === 'ours' && $review->state === 'completed', 409, 'Feedback can be shared only for your own school\'s entrant, once it is written.');
        DB::transaction(function () use ($actor, $id, $reviewId, $data) {
            School::query()->whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            [, , $side] = $this->contests->judge($actor, $id);
            $row = AcademicReasoningReview::query()->lockForUpdate()->findOrFail($reviewId);
            $replay = $row->shared_at !== null && hash_equals((string) $row->shared_operation, (string) $data['operation_id']);
            if ($replay) {
                return;
            }
            abort_unless($side !== null && FixtureSelection::query()->where('shared_fixture_side_id', $side->id)->where('learner_id', (int) $data['learner_id'])->exists(), 422, 'Share feedback only with one of your own entrants.');
            $before = ['shared' => $row->shared_at !== null];
            $row->forceFill(['shared_learner_id' => (int) $data['learner_id'], 'shared_feedback' => trim($data['text']), 'shared_by' => $actor->id, 'shared_at' => now(), 'shared_operation' => $data['operation_id']])->save();
            Audit::record('academic.reasoning_feedback_shared', $row, $before, ['shared' => true, 'learner_id' => (int) $data['learner_id']]);
        });

        return $this->present($review->refresh());
    }

    public function present(AcademicReasoningReview $review): array
    {
        return ['id' => (int) $review->id, 'state' => (string) $review->state, 'source' => (string) $review->source, 'from_side' => (string) $review->from_side, 'stance' => $review->stance,
            'requested_by' => (int) $review->requested_by, 'requested_at' => $review->created_at?->toIso8601String(),
            'feedback' => $review->state === 'completed' ? $review->feedback : null, 'failure_code' => $review->failure_code,
            'shared' => $review->shared_at === null ? null : ['learner_id' => (int) $review->shared_learner_id, 'text' => (string) $review->shared_feedback, 'shared_at' => $review->shared_at->toIso8601String()]];
    }

    /**
     * The gateway policy's view of one pending review: the asking adjudicator's
     * own, in this school, for a competition they still judge.
     */
    public function pending(User $actor, int $reviewId): AcademicReasoningReview
    {
        $review = AcademicReasoningReview::query()->where('requested_by', $actor->id)->findOrFail($reviewId);
        abort_unless($review->state === 'pending' && is_string($review->input), 409, 'This request was already answered.');
        $this->judge($actor, (int) $review->shared_fixture_id);

        return $review;
    }

    /** Quarantine retention: erase what is left of a review the gateway quarantined. */
    public function erase(string $operationId, int $userId): void
    {
        $row = AcademicReasoningReview::query()->where('operation_id', $operationId)->where('requested_by', $userId)->lockForUpdate()->first();
        if ($row === null) {
            return;
        }
        $row->forceFill(['input' => null, 'feedback' => null, 'quarantine_purged_at' => $row->quarantine_purged_at ?? now()])->save();
    }
}
