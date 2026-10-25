<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\ReleasedMarks;
use App\Services\Ai\PromptRegistry;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Staff inference and separately registered, purpose-scoped family feature inference. */
class InferenceGateway
{
    public function run(User $actor, Capability $capability, InferenceRequest $request): ProviderResponse
    {
        return $this->execute($actor, $capability, $request);
    }

    public function runFeature(User $actor, Capability $capability, FeatureInferenceRequest $request): ProviderResponse
    {
        $schools = app(CurrentSchool::class);
        abort_if($schools->isImpersonating() || $schools->isAcrossSchools(), 403);
        $schoolId = $schools->id();
        $started = (int) hrtime(true);

        // Restore the caller's original tenant even if an adapter/policy misbehaves.
        return $schools->run($schoolId, function () use ($actor, $capability, $request, $schoolId, $started) {
            $fresh = app(ProfilePolicy::class)->actor($actor);
            $policy = app(FeaturePolicyRegistry::class)->policy($request->feature);
            $scope = $policy->resolve($fresh, $capability, $request);
            if ($scope->schoolId !== $schoolId || $scope->actorId !== (int) $fresh->id
                || $scope->inference->feature !== $request->feature || $scope->inference->operationId !== $request->operationId) {
                throw new AiUnavailable('feature_scope_invalid');
            }
            $deadline = new InferenceDeadline($started + $scope->timeoutMilliseconds * 1000000);
            $this->authorizeFeature($actor, $scope, $policy);
            $deadline->remainingSeconds();

            return $this->execute($actor, $capability, $scope->inference, $scope, $policy, $deadline,
                hash('sha256', json_encode($request, JSON_THROW_ON_ERROR)));
        });
    }

    private function execute(User $actor, Capability $capability, InferenceRequest $request, ?AuthorizedInferenceScope $scope = null,
        ?InferenceFeaturePolicy $policy = null, ?InferenceDeadline $deadline = null, ?string $intentHash = null): ProviderResponse
    {
        $schoolId = app(CurrentSchool::class)->id();
        $config = $this->configuration($actor, $capability, $request->feature, $scope);
        $prompt = app(PromptRegistry::class)->get($request->prompt);
        if ($request->prompt !== $config['prompt']) {
            throw new AiUnavailable('prompt_not_allowed');
        }
        if ($capability === Capability::Vision || ! is_string($request->input)) {
            throw new AiUnavailable('media_policy_required');
        }
        $reserve = strlen($request->input) + strlen($prompt['system']) + 256 + $config['maximum_output_tokens'];
        if ($reserve > 131072) {
            throw new AiUnavailable('request_too_large');
        }
        $cost = $this->cost($reserve, $config['cents_per_million_tokens']);
        $identity = [$actor->id, $capability->value, $request, $prompt['sha256'], $config['provider'], $config['model']];
        if ($scope !== null) {
            $identity[] = [$scope->learnerId, $scope->subjectId, $scope->audience->value, $intentHash];
        }
        // Preserve hashes of existing staff receipts; feature scopes add their own identity.
        $hash = hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));
        $usage = DB::transaction(function () use ($actor, $request, $config, $prompt, $hash, $reserve, $cost, $capability, $scope, $policy, $deadline, $schoolId) {
            School::query()->whereKey($schoolId)->lockForUpdate()->firstOrFail();
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config, $scope, $policy, $deadline);
            $prior = AiUsage::query()->where('feature', $request->feature)->where('operation_id', $request->operationId)->first();
            if ($prior !== null) {
                if ((int) $prior->user_id !== (int) $actor->id || ! hash_equals($prior->request_hash, $hash)) {
                    throw new AiUnavailable('operation_conflict');
                }
                if ($prior->state !== 'completed') {
                    throw new AiUnavailable('operation_not_replayable');
                }

                return $prior;
            }
            $day = now(app(CurrentSchool::class)->school()->timezone)->toDateString();
            $daily = AiUsage::query()->where('unit', 'tokens')->where('usage_date', $day);
            $budgets = ['school', 'actor', ...($scope?->learnerId === null ? [] : ['learner'])];
            foreach ($budgets as $budget) {
                $query = clone $daily;
                if ($budget === 'actor') {
                    $query->where('user_id', $actor->id);
                } elseif ($budget === 'learner') {
                    $query->where('learner_id', $scope->learnerId);
                }
                $used = $query->selectRaw('COALESCE(SUM(reserved_units + used_units),0) AS tokens, COALESCE(SUM(reserved_cost_cents + cost_cents),0) AS cents')->first();
                if ((int) $used->tokens + $reserve > $config[$budget.'_daily_tokens'] || (int) $used->cents + $cost > $config[$budget.'_daily_cents']) {
                    throw new AiUnavailable($budget.'_daily_cap');
                }
            }

            return AiUsage::create(['user_id' => $actor->id, 'learner_id' => $scope?->learnerId, 'feature' => $request->feature, 'provider' => $config['provider'], 'model' => $config['model'],
                'operation_id' => $request->operationId, 'request_hash' => $hash, 'usage_date' => $day, 'unit' => 'tokens', 'state' => 'reserved',
                'reserved_units' => $reserve, 'reserved_cost_cents' => $cost, 'prompt_key' => $prompt['key'], 'prompt_version' => $prompt['version'],
                'prompt_sha256' => $prompt['sha256'], 'inference_sources' => $request->sources]);
        });
        if ($usage->state === 'completed') {
            return new ProviderResponse($usage->inference_output['output'], $usage->used_units);
        }
        $dispatched = false;
        $actualTokens = null;
        try {
            $moderator = app($config['moderator']);
            $provider = app($config['provider_class']);
            if (! $moderator instanceof InferenceModerator || ! $provider instanceof InferenceProvider) {
                throw new AiUnavailable('adapter_unavailable');
            }
            $identity = $scope?->learnerId === null ? 'actor:'.$actor->id : 'learner:'.$scope->learnerId;
            $anonymous = hash_hmac('sha256', $schoolId.':'.$identity, (string) config('app.key'));
            $moderationContext = new ModerationContext($request->operationId, $anonymous, $deadline);
            $providerRequest = new ProviderRequest($capability, $request->operationId, $config['model'], $anonymous, $prompt['system'], $request->input,
                $config['maximum_output_tokens'], $reserve, $deadline);
            // No HTTP in preflight. Fail before even external moderation when the provider cannot dispatch.
            $provider->preflight($providerRequest);
            $moderator->preflight($capability, $request->input, $moderationContext);
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config, $scope, $policy, $deadline);
            $inputFlags = $moderator->flags($capability, 'input', $request->input, $moderationContext);
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config, $scope, $policy, $deadline);
            $this->quarantine($usage, $inputFlags, 'input', $request->input);
            $dispatched = true;
            $result = $provider->infer($providerRequest);
            $this->validResult($capability, $result, $reserve);
            $actualTokens = $result->tokens;
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config, $scope, $policy, $deadline);
            $outputFlags = $moderator->flags($capability, 'output', $result->output, $moderationContext);
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config, $scope, $policy, $deadline);
            $this->quarantine($usage, $outputFlags, 'output', $result->output);
            $usage->update(['state' => 'completed', 'reserved_units' => 0, 'used_units' => $result->tokens, 'reserved_cost_cents' => 0,
                'cost_cents' => $this->cost($result->tokens, $config['cents_per_million_tokens']), 'inference_output' => ['output' => $result->output],
                'output_sha256' => hash('sha256', json_encode($result->output, JSON_THROW_ON_ERROR)), 'completed_at' => now()]);

            return $result;
        } catch (Throwable $error) {
            $charged = $actualTokens ?? ($dispatched && ! ($error instanceof InferenceNotDispatched) ? $reserve : 0);
            app(CurrentSchool::class)->run($schoolId, function () use ($usage, $charged, $config, $error) {
                $usage->refresh();
                $usage->update(['state' => $usage->state === 'quarantined' ? 'quarantined' : 'failed', 'reserved_units' => 0, 'reserved_cost_cents' => 0,
                    'used_units' => $charged, 'cost_cents' => $this->cost($charged, $config['cents_per_million_tokens']),
                    'failure_code' => $error instanceof AiUnavailable ? $error->reason : 'inference_unavailable', 'completed_at' => now()]);
            });
            throw new AiUnavailable($error instanceof AiUnavailable ? $error->reason : 'inference_unavailable');
        }
    }

    private function authorizeFeature(User $actor, AuthorizedInferenceScope $scope, InferenceFeaturePolicy $policy): void
    {
        $schools = app(CurrentSchool::class);
        if ($schools->isAcrossSchools() || $schools->isImpersonating() || $schools->id() !== $scope->schoolId || (int) $actor->id !== $scope->actorId) {
            throw new AiUnavailable('inference_context_changed');
        }
        if (get_class(app(FeaturePolicyRegistry::class)->policy($scope->inference->feature)) !== get_class($policy)) {
            throw new AiUnavailable('feature_policy_unavailable');
        }
        $fresh = app(ProfilePolicy::class)->actor($actor);
        $policy->reauthorize($fresh, $scope);
        if ($schools->isAcrossSchools() || $schools->isImpersonating() || $schools->id() !== $scope->schoolId) {
            throw new AiUnavailable('inference_context_changed');
        }
    }

    private function freshContext(int $schoolId, User $actor, Capability $capability, string $feature, array $config,
        ?AuthorizedInferenceScope $scope = null, ?InferenceFeaturePolicy $policy = null, ?InferenceDeadline $deadline = null): void
    {
        $schools = app(CurrentSchool::class);
        if ($schools->isAcrossSchools() || $schools->isImpersonating() || $schools->id() !== $schoolId) {
            throw new AiUnavailable('inference_context_changed');
        }
        if ($scope !== null && $policy !== null) {
            $this->authorizeFeature($actor, $scope, $policy);
        }
        if ($this->configuration($actor, $capability, $feature, $scope) !== $config) {
            throw new AiUnavailable('inference_context_changed');
        }
        $deadline?->remainingSeconds();
    }

    private function configuration(User $actor, Capability $capability, string $feature, ?AuthorizedInferenceScope $scope = null): array
    {
        if ((string) config('app.key') === '') {
            throw new AiUnavailable('inference_key_missing');
        }
        $schools = app(CurrentSchool::class);
        abort_if($schools->isImpersonating() || $schools->isAcrossSchools(), 403);
        abort_unless(School::query()->active()->whereKey($schools->id())->exists(), 403);
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        $config = config('ai.inference.features.'.$feature);
        if (! config('ai.inference.enabled') || ! is_array($config) || ($config['enabled'] ?? false) !== true) {
            throw new AiUnavailable('inference_disabled');
        }
        if (! is_array($config['roles'] ?? null) || ! is_array($config['capabilities'] ?? null)) {
            throw new AiUnavailable('feature_policy_invalid');
        }
        $roles = $policy->roles($fresh);
        $permitted = $scope?->audience === InferenceAudience::Family ? ['Parent', 'Student'] : ['Teacher', 'Super Admin', 'School Admin', 'Principal', 'Grade Head', 'Counsellor', 'Safeguarding', 'Facilitator'];
        abort_unless(array_intersect($roles, $config['roles'], $permitted) !== [], 403);
        $registered = config('ai.inference.feature_policies', []);
        if ($scope === null && (! is_array($registered) || array_key_exists($feature, $registered))) {
            throw new AiUnavailable('feature_policy_required');
        }
        if ($scope !== null) {
            abort_unless($scope->schoolId === $schools->id() && $scope->actorId === (int) $fresh->id, 403);
            if ($scope->subjectId !== null) {
                Subject::query()->findOrFail($scope->subjectId);
            }
            if ($scope->learnerId !== null) {
                $learner = Students::query()->findOrFail($scope->learnerId);
                abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');
                if ($scope->audience === InferenceAudience::Family) {
                    app(ReleasedMarks::class)->learner($fresh, $scope->learnerId);
                }
            } else {
                abort_if($scope->audience === InferenceAudience::Family && in_array('Student', $roles, true), 403, 'A learner scope is required.');
            }
        }
        if (! in_array($capability->value, $config['capabilities'], true)) {
            throw new AiUnavailable('capability_unavailable');
        }
        $budgets = ['maximum_output_tokens', 'cents_per_million_tokens', 'school_daily_tokens', 'actor_daily_tokens', 'school_daily_cents', 'actor_daily_cents'];
        if ($scope?->learnerId !== null) {
            $budgets = [...$budgets, 'learner_daily_tokens', 'learner_daily_cents'];
        }
        foreach ($budgets as $key) {
            if (! is_int($config[$key] ?? null) || $config[$key] < 1 || $config[$key] > 100000000) {
                throw new AiUnavailable('budget_not_configured');
            }
        }
        foreach (['provider', 'model', 'provider_class', 'moderator', 'prompt'] as $key) {
            if (! is_string($config[$key] ?? null) || $config[$key] === '' || strlen($config[$key]) > 128) {
                throw new AiUnavailable('adapter_unavailable');
            }
        }

        return $config;
    }

    private function quarantine(AiUsage $usage, array $flags, string $stage, string|array|null $content): void
    {
        if ($flags === []) {
            return;
        }
        app(AiQuarantine::class)->capture($usage, $stage, $flags, $content);
        throw new AiUnavailable('inference_quarantined');
    }

    private function validResult(Capability $capability, ProviderResponse $result, int $reserve): void
    {
        $valid = $result->tokens > 0 && $result->tokens <= $reserve;
        if ($capability === Capability::Embed) {
            $valid = $valid && is_array($result->output) && count($result->output) > 0 && count($result->output) <= 4096;
            foreach ((array) $result->output as $number) {
                $valid = $valid && (is_int($number) || is_float($number)) && is_finite((float) $number);
            }
        } else {
            $valid = $valid && is_string($result->output) && trim($result->output) !== '';
        }
        if (! $valid || strlen(json_encode($result->output, JSON_THROW_ON_ERROR)) > 262144) {
            throw new AiUnavailable('provider_protocol_invalid');
        }
    }

    private function cost(int $tokens, int $price): int
    {
        return intdiv($tokens * $price + 999999, 1000000);
    }
}
