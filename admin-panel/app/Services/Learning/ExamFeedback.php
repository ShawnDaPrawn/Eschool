<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\ExamAnswer;
use App\Models\Learning\ExamAnswerReview;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\PromptRegistry;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class ExamFeedback
{
    public function send(User $actor, string $id, string $working): array
    {
        $row = app(ExamAnswerAccess::class)->answer($actor, $id, false, true);
        abort_unless(strlen($working) <= 8000 && mb_strlen($working) <= 2000 && mb_check_encoding($working, 'UTF-8') && ! str_contains($working, "\0"), 422);
        $school = app(CurrentSchool::class)->id();
        $hash = hash_hmac('sha256', json_encode([$id, $working], JSON_THROW_ON_ERROR), config('app.key'));
        $local = app(TutorModeration::class)->localFlags($working);
        $attempt = (string) Str::uuid();
        $row = DB::transaction(function () use ($actor, $id, $working, $hash, $attempt, $school, $local) {
            School::lockForUpdate()->findOrFail($school);
            $row = app(ExamAnswerAccess::class)->answer($actor, $id, false, true);
            if ($row->request_hash !== null) {
                abort_unless(hash_equals($row->request_hash, $hash), 409, 'This answer feedback request changed.');
                if ($row->state !== 'pending') {
                    return $row;
                }
                if ($row->lease_until->isFuture()) {
                    throw new AiUnavailable('exam_feedback_in_progress');
                }
            } else {
                $day = now(app(CurrentSchool::class)->school()->timezone)->toDateString();
                if ($local === [] && AiUsage::where('feature', 'exam_coach')->where('user_id', $actor->id)->where('usage_date', $day)->count() >= 20) {
                    throw new AiUnavailable('exam_daily_feedback_cap');
                }
            }
            $row->update(['state' => 'pending', 'working' => $working, 'request_hash' => $hash, 'expires_at' => $row->request_hash === null ? now('UTC')->addDays(30) : $row->expires_at, 'attempt_id' => $attempt, 'lease_until' => now('UTC')->addSeconds(30)]);

            return $row;
        });
        if ($row->state !== 'pending') {
            return $this->read($actor, $id);
        }
        try {
            if ($local !== []) {
                $this->localSafety($actor, $row, $local);
                throw new AiUnavailable('inference_quarantined');
            }
            $result = app(AiGateway::class)->runFeature($actor, Capability::Vision, new FeatureInferenceRequest('exam_coach', $row->operation_id, ['answer_id' => $id]));
            if (! is_string($result->output)) {
                throw new AiUnavailable('exam_feedback_invalid');
            }
            $question = app(ExamAnswerAccess::class)->question($actor, $row);
            $response = app(ExamAnswerModeration::class)->parse($row, $question, $result->output);
            DB::transaction(function () use ($actor, $id, $attempt, $school, $response) {
                School::lockForUpdate()->findOrFail($school);
                $fresh = app(ExamAnswerAccess::class)->answer($actor, $id, false, true);
                $usage = AiUsage::where('feature', 'exam_coach')->where('operation_id', $fresh->operation_id)->where('user_id', $actor->id)->lockForUpdate()->firstOrFail();
                abort_unless($fresh->attempt_id === $attempt && $usage->state === 'completed' && $usage->quarantine_purged_at === null, 409);
                $response['feedback'] = app(TutorContext::class)->minimize($actor, $response['feedback']);
                $fresh->update(['state' => 'completed', 'response' => $response, 'ai_usage_id' => $usage->id, 'revision' => $fresh->revision + 1]);
            });
        } catch (Throwable $error) {
            app(CurrentSchool::class)->run($school, function () use ($actor, $id, $attempt, $error) {
                $fresh = ExamAnswer::where('user_id', $actor->id)->where('attempt_id', $attempt)->where('state', 'pending')->whereNull('purged_at')->find($id);
                if ($fresh !== null) {
                    $usage = AiUsage::where('feature', 'exam_coach')->where('operation_id', $fresh->operation_id)->where('user_id', $actor->id)->first();
                    $fresh->update(['state' => $usage?->state === 'quarantined' ? 'quarantined' : 'failed', 'ai_usage_id' => $usage?->id,
                        'failure_code' => $error instanceof AiUnavailable ? $error->reason : 'exam_feedback_unavailable']);
                }
            });
            if (! $error instanceof AiUnavailable) {
                throw $error;
            }
        }

        return $this->read($actor, $id);
    }

    private function localSafety(User $actor, ExamAnswer $row, array $flags): void
    {
        DB::transaction(function () use ($actor, $row, $flags) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $row = app(ExamAnswerAccess::class)->answer($actor, $row->id, false, true);
            $prompt = app(PromptRegistry::class)->get('exam_coach');
            $usage = AiUsage::firstOrCreate(['feature' => 'exam_coach', 'operation_id' => $row->operation_id], ['user_id' => $actor->id, 'learner_id' => $row->learner_id,
                'provider' => 'local-safeguarding-guard', 'model' => 'local-patterns-1', 'request_hash' => $row->request_hash, 'state' => 'reserved', 'unit' => 'tokens',
                'usage_date' => now(app(CurrentSchool::class)->school()->timezone)->toDateString(), 'prompt_key' => 'exam_coach', 'prompt_version' => $prompt['version'], 'prompt_sha256' => $prompt['sha256']]);
            abort_unless((int) $usage->user_id === (int) $actor->id && $usage->quarantine_purged_at === null, 409);
            app(AiQuarantine::class)->capture($usage, 'input', $flags, ['text' => (string) $row->working, 'media' => [app(ExamAnswerAccess::class)->reference($row)]]);
            $usage->update(['failure_code' => 'inference_quarantined', 'completed_at' => now('UTC')]);
        });
    }

    public function read(User $actor, string $id, bool $staff = false): array
    {
        $access = app(ExamCoachAccess::class);
        $access->actor($actor);
        $meta = ExamAnswer::select('id', 'school_id', 'user_id', 'learner_id', 'teaching_group_id', 'operation_id', 'state', 'failure_code', 'revoked_at', 'purged_at', 'expires_at')->findOrFail($id);
        if (! $staff) {
            $person = $access->learner($actor);
            abort_unless((int) $meta->user_id === (int) $actor->id && (int) $meta->learner_id === (int) $person->id, 404);
            $access->learnerGroup($actor, (int) $meta->teaching_group_id);
        } else {
            $access->staff($actor, (int) $meta->teaching_group_id);
        }
        $usage = AiUsage::where('feature', 'exam_coach')->where('operation_id', $meta->operation_id)->where('user_id', $meta->user_id)->first(['state', 'quarantine_purged_at']);
        $state = $meta->revoked_at !== null || $meta->purged_at !== null || ! $meta->expires_at->isFuture() || $usage?->quarantine_purged_at !== null ? 'purged'
            : ($usage?->state === 'quarantined' ? 'quarantined' : $meta->state);
        if (in_array($state, ['purged', 'quarantined'], true)) {
            abort_if($staff, 404);

            return ['id' => $id, 'operation_id' => $meta->operation_id, 'state' => $state, 'failure_code' => $state === 'quarantined' ? 'inference_quarantined' : 'exam_working_expired', 'response' => null, 'review' => null];
        }
        $row = app(ExamAnswerAccess::class)->answer($actor, $id, $staff);
        $review = ExamAnswerReview::where('exam_answer_id', $id)->orderByDesc('answer_revision')->first();
        $data = ['id' => $row->id, 'operation_id' => $row->operation_id, 'state' => $state, 'failure_code' => $row->failure_code,
            'group_id' => (int) $row->teaching_group_id, 'paper_version_id' => $row->paper_version_id, 'question_number' => $row->question_number,
            'working' => (string) $row->working, 'revision' => (int) $row->revision, 'response' => $state === 'completed' ? $row->response : null,
            'review' => $review === null ? null : ['id' => $review->id, 'mark_units' => (int) $review->mark_units, 'reason' => $review->reason, 'answer_revision' => (int) $review->answer_revision],
            'provisional' => $review === null, 'expires_at' => $row->expires_at->toIso8601String()];
        app(ExamAnswerAccess::class)->answer($actor, $id, $staff);

        return $data;
    }

    public function review(User $actor, string $id, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1'],
            'mark_units' => ['required', 'integer', 'min:0'], 'reason' => ['required', 'string', 'min:3', 'max:2000']])->validate();
        $guard = function () use ($actor, $id, $data) {
            $row = app(ExamAnswerAccess::class)->answer($actor, $id, true, true);
            $question = app(ExamAnswerAccess::class)->question($actor, $row, true);
            abort_unless($row->state === 'completed' && $data['mark_units'] <= $question['max_units'], 422);
        };

        return app(ExamCoachOperations::class)->run($actor, 'answer.review', ['answer_id' => $id] + $data, $guard, function () use ($actor, $id, $data) {
            $row = app(ExamAnswerAccess::class)->answer($actor, $id, true, true);
            abort_unless((int) $row->revision === (int) $data['expected_revision'], 409, 'Refresh the current answer review.');
            $row->increment('revision');
            $review = ExamAnswerReview::create(['id' => (string) Str::uuid(), 'exam_answer_id' => $id, 'reviewer_id' => $actor->id,
                'mark_units' => $data['mark_units'], 'reason' => $data['reason'], 'answer_revision' => $row->revision]);

            return ['id' => $review->id, 'answer_id' => $id, 'revision' => (int) $row->revision, 'mark_units' => (int) $review->mark_units];
        });
    }
}
