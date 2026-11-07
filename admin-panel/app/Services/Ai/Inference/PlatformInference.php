<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use App\Models\Ai\PlatformAiUsage;
use App\Models\User;
use App\Services\Ai\PromptRegistry;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * HQ's own model calls (Action Plan 3.4), such as AI drafts for the platform
 * content library. Same boundary as a school feature (InferenceGateway):
 * the platform-wide switch, a qualified provider, a registered prompt,
 * moderation of the input and the output, and metering with daily caps; but
 * made from /saas with no school, by platform staff whose role holds the
 * feature's ability, and metered in platform_ai_usage against HQ's own caps
 * (config ai.platform.features), never a school's. Refused while a school is
 * the current school. Stores tokens, cents, hashes and flag codes, never the
 * prompt or the answer.
 */
class PlatformInference
{
    public const FEATURES = 'ai.platform.features';

    /** Whether the feature could run now; the reason it cannot otherwise (no row is written either way). */
    public function unavailable(string $feature): ?string
    {
        $config = config(self::FEATURES.'.'.$feature);
        if (! config('ai.inference.enabled') || ! is_array($config) || ($config['enabled'] ?? false) !== true) {
            return 'inference_disabled';
        }
        if (! is_string($config['provider'] ?? null) || config('ai.providers.'.$config['provider'].'.qualified') !== true) {
            return 'provider_not_qualified';
        }

        return null;
    }

    public function run(User $actor, Capability $capability, InferenceRequest $request): ProviderResponse
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403, 'HQ model calls are made only from HQ, never from a school.');
        $started = (int) hrtime(true);
        $deadline = new InferenceDeadline($started + 60 * 1000000000);
        $config = $this->configuration($actor, $capability, $request->feature);
        $prompt = app(PromptRegistry::class)->get($request->prompt);
        if ($request->prompt !== $config['prompt']) {
            throw new AiUnavailable('prompt_not_allowed');
        }
        $input = $request->input;
        if (! is_string($input)) {
            throw new AiUnavailable('media_policy_required');
        }
        $reserve = strlen($input) + strlen($prompt['system']) + 256 + $config['maximum_output_tokens'];
        if ($reserve > 131072) {
            throw new AiUnavailable('request_too_large');
        }
        $cost = $this->cost($reserve, $config['cents_per_million_tokens']);
        $hash = hash('sha256', json_encode([$actor->id, $capability->value, $request, $prompt['sha256'], $config['provider'], $config['model']], JSON_THROW_ON_ERROR));
        $usage = DB::transaction(function () use ($actor, $capability, $request, $config, $prompt, $hash, $reserve, $cost) {
            $day = now((string) config('app.timezone'))->toDateString();
            // Today's rows for the feature are locked, so two requests cannot both pass the cap.
            $today = PlatformAiUsage::query()->where('feature', $request->feature)->where('usage_date', $day)->lockForUpdate()->get();
            $this->fresh($actor, $capability, $request->feature, $config);
            $prior = PlatformAiUsage::query()->where('feature', $request->feature)->where('operation_id', $request->operationId)->first();
            if ($prior !== null) {
                throw new AiUnavailable((int) $prior->user_id !== (int) $actor->id || ! hash_equals($prior->request_hash, $hash) ? 'operation_conflict' : 'operation_not_replayable');
            }
            if ($today->contains(fn (PlatformAiUsage $row) => $row->billing_currency !== $config['billing_currency'] && ($row->cost_cents > 0 || $row->reserved_cost_cents > 0))) {
                throw new AiUnavailable('budget_currency_changed');
            }
            foreach (['platform' => $today, 'actor' => $today->where('user_id', (int) $actor->id)] as $budget => $rows) {
                $tokens = $rows->sum(fn ($row) => $row->reserved_units + $row->used_units);
                $cents = $rows->sum(fn ($row) => $row->reserved_cost_cents + $row->cost_cents);
                if ($tokens + $reserve > $config[$budget.'_daily_tokens'] || $cents + $cost > $config[$budget.'_daily_cents']) {
                    throw new AiUnavailable($budget.'_daily_cap');
                }
            }

            return PlatformAiUsage::query()->create(['user_id' => $actor->id, 'feature' => $request->feature, 'provider' => $config['provider'], 'model' => $config['model'],
                'operation_id' => $request->operationId, 'request_hash' => $hash, 'usage_date' => $day, 'unit' => 'tokens', 'state' => 'reserved',
                'reserved_units' => $reserve, 'reserved_cost_cents' => $cost, 'billing_currency' => $config['billing_currency'], 'prompt_key' => $prompt['key'],
                'prompt_version' => $prompt['version'], 'prompt_sha256' => $prompt['sha256']]);
        });
        $dispatched = false;
        $actualTokens = null;
        try {
            $moderator = app($config['moderator']);
            $provider = app($config['provider_class']);
            if (! $moderator instanceof InferenceModerator || ! $provider instanceof InferenceProvider) {
                throw new AiUnavailable('adapter_unavailable');
            }
            $anonymous = hash_hmac('sha256', 'platform:'.$actor->id, (string) config('app.key'));
            $context = new ModerationContext($request->operationId, $anonymous, $deadline);
            $providerRequest = new ProviderRequest($capability, $request->operationId, $config['model'], $anonymous, $prompt['system'], $input,
                $config['maximum_output_tokens'], $reserve, $deadline);
            // No HTTP in preflight: fail before even external moderation when the provider cannot dispatch.
            $provider->preflight($providerRequest);
            $moderator->preflight($capability, $input, $context);
            $this->fresh($actor, $capability, $request->feature, $config);
            $this->hold($usage, $moderator->flags($capability, 'input', $input, $context));
            $this->fresh($actor, $capability, $request->feature, $config);
            $dispatched = true;
            $result = $provider->infer($providerRequest);
            if ($result->tokens < 1 || $result->tokens > $reserve || ! is_string($result->output) || trim($result->output) === '' || strlen($result->output) > 262144) {
                throw new AiUnavailable('provider_protocol_invalid');
            }
            $actualTokens = $result->tokens;
            $this->fresh($actor, $capability, $request->feature, $config);
            $this->hold($usage, $moderator->flags($capability, 'output', $result->output, $context));
            $usage->update(['state' => 'completed', 'reserved_units' => 0, 'used_units' => $result->tokens, 'reserved_cost_cents' => 0,
                'cost_cents' => $this->cost($result->tokens, $config['cents_per_million_tokens']), 'output_sha256' => hash('sha256', $result->output), 'completed_at' => now()]);

            return $result;
        } catch (Throwable $error) {
            // Once dispatched, an unknown outcome stays charged at the reservation.
            $charged = $actualTokens ?? ($dispatched && ! ($error instanceof InferenceNotDispatched) ? $reserve : 0);
            $usage->refresh();
            $usage->update(['state' => $usage->state === 'quarantined' ? 'quarantined' : 'failed', 'reserved_units' => 0, 'reserved_cost_cents' => 0,
                'used_units' => $charged, 'cost_cents' => $this->cost($charged, $config['cents_per_million_tokens']),
                'failure_code' => $error instanceof AiUnavailable ? $error->reason : 'inference_unavailable', 'completed_at' => now()]);
            throw new AiUnavailable($error instanceof AiUnavailable ? $error->reason : 'inference_unavailable');
        }
    }

    /** The usage row of an operation, for the feature to link to. */
    public function usage(string $feature, string $operationId): ?PlatformAiUsage
    {
        return PlatformAiUsage::query()->where('feature', $feature)->where('operation_id', $operationId)->first();
    }

    /**
     * Today's spend for the feature against HQ's caps, in cents.
     *
     * @return array{spent: int, cap: int|null, currency: string}
     */
    public function today(string $feature): array
    {
        $day = now((string) config('app.timezone'))->toDateString();
        $spent = (int) PlatformAiUsage::query()->where('feature', $feature)->where('usage_date', $day)->selectRaw('COALESCE(SUM(reserved_cost_cents + cost_cents),0) AS cents')->value('cents');
        $cap = config(self::FEATURES.'.'.$feature.'.platform_daily_cents');

        return ['spent' => $spent, 'cap' => is_int($cap) ? $cap : null, 'currency' => (string) config('ai.billing_currency')];
    }

    private function configuration(User $actor, Capability $capability, string $feature): array
    {
        if ((string) config('app.key') === '') {
            throw new AiUnavailable('inference_key_missing');
        }
        abort_if(app(CurrentSchool::class)->isSet(), 403);
        $config = config(self::FEATURES.'.'.$feature);
        $reason = $this->unavailable($feature);
        if ($reason !== null || ! is_array($config)) {
            throw new AiUnavailable($reason ?? 'inference_disabled');
        }
        abort_unless(is_string($config['ability'] ?? null) && app(PlatformStaffAccess::class)->can($actor, $config['ability']), 403);
        if (! is_array($config['capabilities'] ?? null) || ! in_array($capability->value, $config['capabilities'], true)) {
            throw new AiUnavailable('capability_unavailable');
        }
        foreach (['maximum_output_tokens', 'cents_per_million_tokens', 'platform_daily_tokens', 'actor_daily_tokens', 'platform_daily_cents', 'actor_daily_cents'] as $key) {
            if (! is_int($config[$key] ?? null) || $config[$key] < 1 || $config[$key] > 100000000) {
                throw new AiUnavailable('budget_not_configured');
            }
        }
        foreach (['provider', 'model', 'provider_class', 'moderator', 'prompt'] as $key) {
            if (! is_string($config[$key] ?? null) || $config[$key] === '' || strlen($config[$key]) > 128) {
                throw new AiUnavailable('adapter_unavailable');
            }
        }
        $currency = config('ai.billing_currency');
        if (! is_string($currency) || ! preg_match('/^[A-Z]{3}$/D', $currency)) {
            throw new AiUnavailable('budget_not_configured');
        }
        $config['billing_currency'] = $currency;
        $config['capability'] = $capability->value;

        return $config;
    }

    /** Switches, role and caps are read again between stages: a change stops a call in flight. */
    private function fresh(User $actor, Capability $capability, string $feature, array $config): void
    {
        if ($this->configuration($actor, $capability, $feature) !== $config) {
            throw new AiUnavailable('inference_context_changed');
        }
    }

    /** Moderation flags hold the call back: only the flag codes are kept, never the text. */
    private function hold(PlatformAiUsage $usage, array $flags): void
    {
        if ($flags === []) {
            return;
        }
        $usage->update(['state' => 'quarantined', 'flags' => array_values(array_filter($flags, 'is_string'))]);
        throw new AiUnavailable('inference_quarantined');
    }

    private function cost(int $tokens, int $price): int
    {
        return intdiv($tokens * $price + 999999, 1000000);
    }
}
