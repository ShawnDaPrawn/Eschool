<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Ai\AiUsage;
use App\Models\Assistance\AssistanceTurn;
use App\Models\User;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceGateway;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AssistanceInference
{
    public function feature(AssistanceTurn $turn): string
    {
        return $turn->purpose === 'answer' ? 'school_assistant' : 'message_translation';
    }

    public function prepare(User $actor, int $learner, string $purpose, array $input, bool $office = false): array
    {
        abort_unless(in_array($purpose, ['answer', 'translation'], true) && (! $office || $purpose === 'translation'), 422);
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        unset($input['operation_id']);
        $authorize = function () use ($actor, $learner, $purpose, $input, $office, $operation): void {
            app(AssistanceAccess::class)->actor($actor, true);
            $old = AssistanceTurn::where('user_id', $actor->id)->where('operation_id', $operation)->select('id')->first();
            if ($old !== null) {
                $this->authorize($actor, $old->id);
            } elseif ($purpose === 'answer') {
                app(AssistanceAccess::class)->family($actor, $learner, true);
            } else {
                $source = app(AssistanceContext::class)->translation($actor, $input, $office);
                abort_unless($source['learner'] === $learner, 404);
            }
        };

        $receipt = app(AssistanceOperations::class)->once($actor, 'turn.prepare', ['operation_id' => $operation, 'learner_id' => $learner, 'purpose' => $purpose, 'office' => $office, 'input' => $input],
            $authorize, function () use ($actor, $learner, $purpose, $input, $office, $operation): array {
                abort_if(AssistanceTurn::where('user_id', $actor->id)->whereNull('purged_at')->where('expires_at', '>', now()->utc())->count() >= 50,
                    429, 'Wait for an existing school-assistance review to expire.');
                $context = app(AssistanceContext::class);
                $source = $purpose === 'answer' ? $context->answer($actor, $learner, $input) : $context->translation($actor, $input, $office);
                $turn = AssistanceTurn::create(['id' => (string) Str::uuid(), 'user_id' => $actor->id, 'learner_id' => $learner,
                    'operation_id' => $operation, 'purpose' => $purpose, 'office' => $office, 'source_request' => $source['request'],
                    'source_manifest' => $source['manifest'], 'approved_input' => $source['input'], 'citations' => $source['citations'],
                    'expires_at' => now()->utc()->addMinutes(15)]);

                return ['id' => $turn->id];
            });

        return $this->view($actor, $receipt['id']);
    }

    public function authorize(User $actor, string $id): AssistanceTurn
    {
        app(AssistanceAccess::class)->actor($actor);
        $meta = AssistanceTurn::select('id', 'user_id', 'learner_id', 'purpose', 'office', 'state', 'purged_at', 'expires_at')->findOrFail($id);
        abort_unless((int) $meta->user_id === (int) $actor->id && $meta->purged_at === null && $meta->expires_at->isFuture()
            && in_array($meta->state, ['prepared', 'completed'], true), 404);
        abort_if(AiUsage::where('operation_id', $id)->where('user_id', $actor->id)->where('feature', $this->feature($meta))
            ->where(fn ($q) => $q->where('state', 'quarantined')->orWhereNotNull('quarantine_purged_at'))->exists(), 404);
        if ($meta->purpose === 'answer') {
            app(AssistanceAccess::class)->family($actor, (int) $meta->learner_id);
        } elseif ($meta->office) {
            app(AssistanceAccess::class)->office($actor);
        } else {
            app(AssistanceAccess::class)->family($actor, (int) $meta->learner_id);
        }
        $turn = AssistanceTurn::findOrFail($id);
        $context = app(AssistanceContext::class);
        $current = $turn->purpose === 'answer'
            ? $context->answer($actor, (int) $turn->learner_id, $turn->source_request)
            : $context->translation($actor, $turn->source_request, $turn->office);
        abort_unless(($current['learner'] ?? (int) $turn->learner_id) === (int) $turn->learner_id
            && $current['manifest'] === $turn->source_manifest && hash_equals($turn->approved_input, $current['input']), 409, 'The approved source changed. Prepare a new review.');

        return $turn;
    }

    public function view(User $actor, string $id): array
    {
        $turn = $this->authorize($actor, $id);

        return ['id' => $turn->id, 'purpose' => $turn->purpose, 'state' => $turn->state, 'learner_id' => (int) $turn->learner_id,
            'expires_at' => $turn->expires_at->toISOString(), 'language' => $turn->source_request['language'],
            'review_text' => $turn->approved_input, 'citations' => $turn->citations, 'response' => $turn->response];
    }

    public function run(User $actor, string $id, bool $identifiersReviewed): array
    {
        abort_unless($identifiersReviewed, 422, 'Review the exact minimised text for visible names and identifiers before sharing.');
        $turn = $this->authorize($actor, $id);
        if ($turn->state === 'completed') {
            return $this->view($actor, $id);
        }
        $turn->update(['approved_at' => $turn->approved_at ?? now()->utc()]);
        // The server-generated primary UUID is globally unique; a client cannot
        // collide another reader's moderation or usage locator.
        $response = app(InferenceGateway::class)->runFeature($actor, Capability::Complete,
            new FeatureInferenceRequest($this->feature($turn), $turn->id, ['turn_id' => $turn->id]));
        $this->authorize($actor, $id);
        $parsed = app(AssistanceModeration::class)->parse($turn, $response->output);
        DB::transaction(function () use ($actor, $id, $parsed): void {
            $turn = AssistanceTurn::lockForUpdate()->findOrFail($id);
            $this->authorize($actor, $id);
            if ($turn->state === 'completed') {
                abort_unless($turn->response === $parsed, 409);

                return;
            }
            $turn->update(['state' => 'completed', 'response' => $parsed]);
        });

        return $this->view($actor, $id);
    }

    public function purgeExpired(): int
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        $rows = AssistanceTurn::whereNull('purged_at')->where('expires_at', '<=', now()->utc())->orderBy('id')->limit(200)->get();
        foreach ($rows as $turn) {
            $this->erase($turn);
        }

        return $rows->count();
    }

    public function erase(AssistanceTurn $turn): void
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating()
            && (int) $turn->school_id === $school->id(), 403);
        $turn->update(['state' => 'purged', 'source_request' => null, 'source_manifest' => null, 'approved_input' => null,
            'citations' => null, 'response' => null, 'purged_at' => now()->utc()]);
        // Keep metering and cost history, erase the gateway's replay body too.
        AiUsage::where('operation_id', $turn->id)->where('user_id', $turn->user_id)->where('feature', $this->feature($turn))
            ->update(['inference_output' => null, 'inference_sources' => null]);
    }
}
