<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorPhoto;
use App\Models\Learning\TutorSession;
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

class TutorSessions
{
    public const SUPPORT_MESSAGE = 'Please talk to a trusted adult or your school safeguarding team. If you are in immediate danger, contact local emergency services.';

    public function create(User $actor, int $groupId, array $versions, string $operation): array
    {
        $group = app(TutorAccess::class)->group($actor, $groupId, true);
        Validator::make(['versions' => $versions, 'operation_id' => $operation], ['versions' => ['required', 'array', 'min:1', 'max:3'],
            'versions.*' => ['required', 'integer', 'min:1', 'distinct'], 'operation_id' => ['required', 'uuid']])->validate();
        $versions = array_map('intval', $versions);
        sort($versions);
        $hash = hash('sha256', json_encode([$groupId, $versions], JSON_THROW_ON_ERROR));
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($actor, $group, $versions, $operation, $hash, $school) {
            School::lockForUpdate()->findOrFail($school);
            $group = app(TutorAccess::class)->group($actor, (int) $group->id, true);
            $prior = TutorSession::where('operation_id', $operation)->first();
            if ($prior !== null) {
                abort_unless((int) $prior->user_id === (int) $actor->id && hash_equals($hash, $prior->request_hash), 409);
                $session = app(TutorAccess::class)->session($actor, $prior->id);
            } else {
                abort_if(TutorSession::where('user_id', $actor->id)->where('state', 'open')->count() >= 100, 422, 'Close an older tutor session before opening another.');
                $learner = app(TutorAccess::class)->learner($actor, true);
                $session = TutorSession::create(['id' => (string) Str::uuid(), 'learner_id' => $learner->id, 'user_id' => $actor->id,
                    'teaching_group_id' => $group->id, 'subject_id' => $group->subject_id, 'session_year_id' => $group->session_year_id,
                    'operation_id' => $operation, 'request_hash' => $hash, 'source_version_ids' => $versions]);
            }
            foreach ($versions as $version) {
                app(TutorContext::class)->version($actor, $session, $version);
            }

            return $this->sessionData($session);
        });
    }

    public function close(User $actor, string $id): array
    {
        return DB::transaction(function () use ($actor, $id) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $session = app(TutorAccess::class)->session($actor, $id);
            app(TutorAccess::class)->learner($actor, true);
            $session->update(['state' => 'closed']);

            return $this->sessionData($session);
        });
    }

    public function send(User $actor, string $sessionId, string $operation, string $text, string $working = '', array $photos = []): array
    {
        app(TutorAccess::class)->session($actor, $sessionId, true);
        Validator::make(compact('operation', 'text', 'working', 'photos'), ['operation' => ['required', 'uuid'], 'text' => ['required', 'string', 'max:1000'],
            'working' => ['string', 'max:2000'], 'photos' => ['array', 'max:3'], 'photos.*' => ['required', 'uuid', 'distinct']])->validate();
        abort_unless(mb_check_encoding($text.$working, 'UTF-8') && ! str_contains($text.$working, "\0") && strlen($text) <= 4000 && strlen($working) <= 8000, 422);
        $hash = hash_hmac('sha256', json_encode([$sessionId, $text, $working, $photos], JSON_THROW_ON_ERROR), config('app.key'));
        $attempt = (string) Str::uuid();
        $school = app(CurrentSchool::class)->id();
        $message = DB::transaction(function () use ($actor, $sessionId, $operation, $text, $working, $photos, $hash, $attempt, $school) {
            School::lockForUpdate()->findOrFail($school);
            $session = app(TutorAccess::class)->session($actor, $sessionId, true);
            $prior = TutorMessage::where('operation_id', $operation)->lockForUpdate()->first();
            if ($prior !== null) {
                abort_unless((int) $prior->user_id === (int) $actor->id && hash_equals($hash, $prior->request_hash), 409, 'This turn request changed.');
                if ($prior->state !== 'pending') {
                    return $prior;
                }
                if ($prior->lease_until->isFuture()) {
                    throw new AiUnavailable('tutor_turn_in_progress');
                }
                $prior->update(['attempt_id' => $attempt, 'lease_until' => now('UTC')->addSeconds(30)]);

                return $prior;
            }
            abort_if(TutorMessage::where('tutor_session_id', $sessionId)->count() >= 100, 422, 'Open a fresh session to continue.');
            $day = now(app(CurrentSchool::class)->school()->timezone)->toDateString();
            if (AiUsage::where('feature', 'tutor')->where('user_id', $actor->id)->where('usage_date', $day)->count() >= 50) {
                throw new AiUnavailable('tutor_daily_turn_cap');
            }
            $context = app(TutorContext::class)->capture($actor, $session);
            $message = TutorMessage::create(['id' => (string) Str::uuid(), 'tutor_session_id' => $sessionId, 'user_id' => $actor->id,
                'operation_id' => $operation, 'request_hash' => $hash, 'learner_text' => $text, 'working' => $working, 'media_ids' => $photos,
                'source_manifest' => $context['manifest'], 'context_text' => $context['text'], 'attempt_id' => $attempt, 'lease_until' => now('UTC')->addSeconds(30)]);
            app(TutorPhotos::class)->references($actor, $message);

            return $message;
        });
        if ($message->state !== 'pending') {
            return $this->turnData($actor, $message);
        }
        try {
            $flags = app(TutorModeration::class)->localFlags($text.' '.$working);
            if ($flags !== []) {
                $usage = $this->localSafety($actor, $message, $flags);
                throw new AiUnavailable('inference_quarantined');
            }
            $result = app(AiGateway::class)->runFeature($actor, $photos === [] ? Capability::Complete : Capability::Vision,
                new FeatureInferenceRequest('tutor', $operation, ['message_id' => $message->id]));
            $usage = AiUsage::where('feature', 'tutor')->where('operation_id', $operation)->where('user_id', $actor->id)->firstOrFail();
            if (! is_string($result->output)) {
                throw new AiUnavailable('tutor_output_invalid');
            }
            $response = app(TutorModeration::class)->parse($message, $result->output);

            return DB::transaction(function () use ($actor, $message, $response, $usage, $attempt, $school) {
                School::lockForUpdate()->findOrFail($school);
                app(TutorFeaturePolicy::class)->message($actor, $message->id);
                app(TutorAccess::class)->session($actor, $message->tutor_session_id, true);
                $fresh = TutorMessage::lockForUpdate()->findOrFail($message->id);
                $usage = AiUsage::lockForUpdate()->findOrFail($usage->id);
                abort_unless($fresh->attempt_id === $attempt && $fresh->purged_at === null && $usage->quarantine_purged_at === null && $usage->state === 'completed', 409);
                $response['text'] = app(TutorContext::class)->minimize($actor, $response['text']);
                $response['question'] = app(TutorContext::class)->minimize($actor, $response['question']);
                $fresh->update(['state' => 'completed', 'response' => $response, 'response_kind' => $response['kind'], 'ai_usage_id' => $usage->id]);

                return $this->turnData($actor, $fresh);
            });
        } catch (Throwable $error) {
            app(CurrentSchool::class)->run($school, function () use ($actor, $message, $attempt, $error) {
                $usage = AiUsage::where('feature', 'tutor')->where('operation_id', $message->operation_id)->where('user_id', $actor->id)->first();
                $fresh = TutorMessage::where('attempt_id', $attempt)->find($message->id);
                if ($fresh !== null && $fresh->purged_at === null && $fresh->state === 'pending') {
                    $fresh->update(['state' => $usage?->state === 'quarantined' ? 'quarantined' : 'failed', 'ai_usage_id' => $usage?->id,
                        'failure_code' => $error instanceof AiUnavailable ? $error->reason : 'tutor_unavailable']);
                }
            });
            if ($error instanceof AiUnavailable) {
                return $this->turnData($actor, $message->refresh());
            }
            throw $error;
        }
    }

    private function localSafety(User $actor, TutorMessage $message, array $flags): AiUsage
    {
        return DB::transaction(function () use ($actor, $message, $flags) {
            School::lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $session = app(TutorAccess::class)->session($actor, $message->tutor_session_id, true);
            $prompt = app(PromptRegistry::class)->get('tutor');
            $usage = AiUsage::firstOrCreate(['feature' => 'tutor', 'operation_id' => $message->operation_id], ['user_id' => $actor->id, 'learner_id' => $session->learner_id,
                'provider' => 'local-safeguarding-guard', 'model' => 'local-patterns-1', 'request_hash' => $message->request_hash,
                'usage_date' => now(app(CurrentSchool::class)->school()->timezone)->toDateString(), 'unit' => 'tokens', 'state' => 'reserved',
                'prompt_key' => 'tutor', 'prompt_version' => $prompt['version'], 'prompt_sha256' => $prompt['sha256']]);
            abort_unless((int) $usage->user_id === (int) $actor->id && hash_equals($usage->request_hash, $message->request_hash) && $usage->quarantine_purged_at === null, 409);
            $content = ['text' => $message->learner_text."\n".$message->working, 'media' => app(TutorPhotos::class)->references($actor, $message)];
            app(AiQuarantine::class)->capture($usage, 'input', $flags, $content);
            $usage->update(['completed_at' => now(), 'failure_code' => 'inference_quarantined']);

            return $usage;
        });
    }

    public function turnData(User $actor, TutorMessage $message): array
    {
        app(TutorAccess::class)->session($actor, $message->tutor_session_id);
        abort_unless((int) $message->user_id === (int) $actor->id, 404);
        $safe = $message->state === 'completed' && $message->purged_at === null;
        if ($safe) {
            app(TutorFeaturePolicy::class)->message($actor, $message->id);
        }
        // Own human-authored drafts can be recovered after an unknown write.
        // AI output still needs the full source guard; quarantined/purged input
        // never returns to the conversation or a parent summary.
        $ownInput = in_array($message->state, ['pending', 'failed', 'completed'], true) && $message->purged_at === null;

        return ['id' => $message->id, 'operation_id' => $message->operation_id, 'state' => $message->state,
            'question' => $ownInput ? $message->learner_text : null, 'working' => $ownInput ? $message->working : null, 'response' => $safe ? $message->response : null,
            'failure_code' => $message->failure_code, 'support_message' => $message->state === 'quarantined' ? self::SUPPORT_MESSAGE : null,
            'provisional' => true];
    }

    private function sessionData(TutorSession $session): array
    {
        return ['id' => $session->id, 'learner_id' => (int) $session->learner_id, 'subject_id' => (int) $session->subject_id,
            'group_id' => (int) $session->teaching_group_id, 'session_year_id' => (int) $session->session_year_id, 'state' => $session->state];
    }

    public function read(User $actor, string $id, int $page = 1): array
    {
        $session = app(TutorAccess::class)->session($actor, $id);
        $rows = TutorMessage::where('tutor_session_id', $id)->orderBy('created_at')->orderBy('id')->paginate(20, ['*'], 'page', max(1, $page));
        $data = $rows->getCollection()->map(fn ($row) => $this->turnData($actor, $row))->all();
        $pendingPhotos = TutorPhoto::where('tutor_session_id', $id)->where('user_id', $actor->id)->whereNull('revoked_at')->whereNull('purged_at')->whereNotNull('approved_at')
            ->whereNotIn('operation_id', TutorMessage::where('user_id', $actor->id)->select('operation_id'))->orderBy('id')->limit(20)->get()
            ->map(fn ($photo) => ['id' => $photo->id, 'sha256' => $photo->sha256, 'mime' => 'image/png', 'operation_id' => $photo->operation_id,
                'school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id])->all();
        app(TutorAccess::class)->session($actor, $id);

        return $this->sessionData($session) + ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id,
            'page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'turns' => $data, 'pending_photos' => $pendingPhotos];
    }

    public function turnByOperation(User $actor, string $session, string $operation): array
    {
        app(TutorAccess::class)->session($actor, $session);
        $message = TutorMessage::where('tutor_session_id', $session)->where('operation_id', $operation)->firstOrFail();

        return $this->turnData($actor, $message);
    }
}
