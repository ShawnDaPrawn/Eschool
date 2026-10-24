<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use App\Models\Ai\AiFlag;
use App\Models\Ai\AiUsage;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Ai\PromptRegistry;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Throwable;

/** Internal staff foundation. Feature services remain responsible for source authorization. */
class InferenceGateway
{
    public function run(User $actor, Capability $capability, InferenceRequest $request): ProviderResponse
    {
        $schoolId = app(CurrentSchool::class)->id();
        $config = $this->configuration($actor, $capability, $request->feature);
        $prompt = app(PromptRegistry::class)->get($request->prompt);
        if ($request->prompt !== $config['prompt']) {
            throw new AiUnavailable('prompt_not_allowed');
        }
        // Vision requires a Files-backed, scanned and redacted media resolver in a later slice.
        if ($capability === Capability::Vision || ! is_string($request->input)) {
            throw new AiUnavailable('media_policy_required');
        }
        $reserve = strlen($request->input) + strlen($prompt['system']) + 256 + $config['maximum_output_tokens'];
        if ($reserve > 131072) {
            throw new AiUnavailable('request_too_large');
        }
        $cost = $this->cost($reserve, $config['cents_per_million_tokens']);
        $hash = hash('sha256', json_encode([$actor->id, $capability->value, $request, $prompt['sha256'], $config['provider'], $config['model']], JSON_THROW_ON_ERROR));
        $usage = DB::transaction(function () use ($actor, $request, $config, $prompt, $hash, $reserve, $cost, $capability) {
            School::query()->whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->configuration($actor, $capability, $request->feature);
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
            foreach (['school', 'actor'] as $scope) {
                $query = clone $daily;
                if ($scope === 'actor') {
                    $query->where('user_id', $actor->id);
                }
                $used = $query->selectRaw('COALESCE(SUM(reserved_units + used_units),0) AS tokens, COALESCE(SUM(reserved_cost_cents + cost_cents),0) AS cents')->first();
                if ((int) $used->tokens + $reserve > $config[$scope.'_daily_tokens'] || (int) $used->cents + $cost > $config[$scope.'_daily_cents']) {
                    throw new AiUnavailable($scope.'_daily_cap');
                }
            }

            return AiUsage::create(['user_id' => $actor->id, 'feature' => $request->feature, 'provider' => $config['provider'], 'model' => $config['model'],
                'operation_id' => $request->operationId, 'request_hash' => $hash, 'usage_date' => $day, 'unit' => 'tokens', 'state' => 'reserved',
                'reserved_units' => $reserve, 'reserved_cost_cents' => $cost, 'prompt_key' => $prompt['key'], 'prompt_version' => $prompt['version'],
                'prompt_sha256' => $prompt['sha256'], 'inference_sources' => $request->sources]);
        });
        if ($usage->state === 'completed') {
            return new ProviderResponse($usage->inference_output['output'], $usage->used_units);
        }
        $dispatched = false;
        try {
            $moderator = app($config['moderator']);
            $provider = app($config['provider_class']);
            if (! $moderator instanceof InferenceModerator || ! $provider instanceof InferenceProvider) {
                throw new AiUnavailable('adapter_unavailable');
            }
            $inputFlags = $moderator->flags($capability, 'input', $request->input);
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config);
            $this->quarantine($usage, $inputFlags);
            $anonymous = hash_hmac('sha256', app(CurrentSchool::class)->id().':'.$actor->id, (string) config('app.key'));
            $dispatched = true;
            $result = $provider->infer(new ProviderRequest($capability, $request->operationId, $config['model'], $anonymous, $prompt['system'], $request->input, $config['maximum_output_tokens'], $reserve));
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config);
            $this->validResult($capability, $result, $reserve);
            $outputFlags = $moderator->flags($capability, 'output', $result->output);
            $this->freshContext($schoolId, $actor, $capability, $request->feature, $config);
            $this->quarantine($usage, $outputFlags);
            $usage->update(['state' => 'completed', 'reserved_units' => 0, 'used_units' => $result->tokens, 'reserved_cost_cents' => 0,
                'cost_cents' => $this->cost($result->tokens, $config['cents_per_million_tokens']), 'inference_output' => ['output' => $result->output],
                'output_sha256' => hash('sha256', json_encode($result->output, JSON_THROW_ON_ERROR)), 'completed_at' => now()]);

            return $result;
        } catch (Throwable $e) {
            // Unknown outcomes consume the full reservation; a retry cannot turn compute free.
            app(CurrentSchool::class)->run($schoolId, function () use ($usage, $dispatched, $reserve, $cost, $e) {
                $usage->refresh();
                $usage->update(['state' => $usage->state === 'quarantined' ? 'quarantined' : 'failed', 'reserved_units' => 0, 'reserved_cost_cents' => 0,
                    'used_units' => $dispatched ? $reserve : 0, 'cost_cents' => $dispatched ? $cost : 0,
                    'failure_code' => $e instanceof AiUnavailable ? $e->reason : 'inference_unavailable', 'completed_at' => now()]);
            });
            throw new AiUnavailable($e instanceof AiUnavailable ? $e->reason : 'inference_unavailable');
        }
    }

    private function freshContext(int $schoolId, User $actor, Capability $capability, string $feature, array $config): void
    {
        if (app(CurrentSchool::class)->id() !== $schoolId || $this->configuration($actor, $capability, $feature) !== $config) {
            throw new AiUnavailable('inference_context_changed');
        }
    }

    private function configuration(User $actor, Capability $capability, string $feature): array
    {
        if ((string) config('app.key') === '') {
            throw new AiUnavailable('inference_key_missing');
        }
        $policy = app(ProfilePolicy::class);
        $fresh = $policy->actor($actor);
        abort_if(app(CurrentSchool::class)->isImpersonating(), 403);
        $config = config('ai.inference.features.'.$feature);
        if (! config('ai.inference.enabled') || ! is_array($config) || ($config['enabled'] ?? false) !== true) {
            throw new AiUnavailable('inference_disabled');
        }
        if (! is_array($config['roles'] ?? null) || ! is_array($config['capabilities'] ?? null)) {
            throw new AiUnavailable('feature_policy_invalid');
        }
        abort_unless(array_intersect($policy->roles($fresh), $config['roles'] ?? [], ['Teacher', 'Super Admin', 'School Admin', 'Principal', 'Grade Head', 'Counsellor', 'Facilitator']) !== [], 403);
        if (! in_array($capability->value, $config['capabilities'] ?? [], true)) {
            throw new AiUnavailable('capability_unavailable');
        }
        foreach (['maximum_output_tokens', 'cents_per_million_tokens', 'school_daily_tokens', 'actor_daily_tokens', 'school_daily_cents', 'actor_daily_cents'] as $key) {
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

    private function quarantine(AiUsage $usage, array $flags): void
    {
        if ($flags === []) {
            return;
        }
        DB::transaction(function () use ($usage, $flags) {
            foreach (array_unique($flags) as $flag) {
                if (! is_string($flag) || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $flag)) {
                    throw new AiUnavailable('moderation_invalid');
                }
                AiFlag::create(['ai_usage_id' => $usage->id, 'code' => $flag]);
            }
            $usage->update(['state' => 'quarantined']);
        });
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
