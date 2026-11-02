<?php

declare(strict_types=1);

namespace App\Services\Ai\Operations;

use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * AI spending caps set in HQ (owner decision, 31 October 2026). Each AI
 * feature's daily caps per school, per person and per learner default to
 * config/ai.php until an Owner saves a value at /saas/ai; the saved value
 * applies from the next request. A school's own lower limit (ai_school_limits,
 * /ai-settings) still applies on top, through AiSchoolControls, and is never
 * written here. Amounts are integer cents of ai.billing_currency; no exchange
 * rate is applied. Personal services (ai.inference.personal_features, the
 * Mastery tutor) have no school cap: their spend is kept out of the school's
 * daily total. engineering/specs/platform/hq-ai.md.
 */
class PlatformAiCaps
{
    /** The caps HQ edits, with the most HQ may set for each (integer cents). */
    public const MAXIMUM = ['school_daily_cents' => 1000000, 'actor_daily_cents' => 100000, 'learner_daily_cents' => 100000];

    /** @return list<string> */
    public static function personalFeatures(): array
    {
        return array_values(array_filter((array) config('ai.inference.personal_features', []), 'is_string'));
    }

    public static function personal(string $feature): bool
    {
        return in_array($feature, self::personalFeatures(), true);
    }

    /**
     * Trusted gateway hook: the feature's config with HQ's saved caps in place
     * of the config defaults. Read afresh on every call, so a save is in force
     * for the next request (and a request in flight sees the change and stops).
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function apply(string $feature, array $config): array
    {
        $saved = DB::table('platform_ai_caps')->where('feature', $feature)->pluck('cents', 'cap');
        foreach (array_keys(self::MAXIMUM) as $cap) {
            if (isset($config[$cap], $saved[$cap])) {
                $config[$cap] = (int) $saved[$cap];
            }
        }

        return $config;
    }

    /**
     * Every configured feature's caps: the config default, HQ's saved value
     * and the one in force (HQ's, else the default).
     *
     * @return array<string, array<string, array{default: int|null, hq: int|null, effective: int|null}>>
     */
    public function caps(): array
    {
        $saved = $this->saved();
        $answer = [];
        foreach ((array) config('ai.inference.features', []) as $feature => $settings) {
            if (! is_string($feature) || ! is_array($settings)) {
                continue;
            }
            foreach (array_keys(self::MAXIMUM) as $cap) {
                $default = is_int($settings[$cap] ?? null) ? $settings[$cap] : null;
                $hq = $saved[$feature][$cap] ?? null;
                $answer[$feature][$cap] = ['default' => $default, 'hq' => $hq, 'effective' => $default === null ? null : ($hq ?? $default)];
            }
        }

        return $answer;
    }

    /**
     * The latest changes, newest first: feature, cap, old and new cents, who and when.
     *
     * @return list<array<string, mixed>>
     */
    public function history(int $limit = 20): array
    {
        return DB::table('platform_ai_cap_changes')->leftJoin('users', 'users.id', '=', 'platform_ai_cap_changes.changed_by')
            ->orderByDesc('platform_ai_cap_changes.id')->limit($limit)
            ->get(['platform_ai_cap_changes.*', 'users.first_name', 'users.last_name'])
            ->map(fn ($row): array => ['feature' => (string) $row->feature, 'cap' => (string) $row->cap,
                'old' => $row->old_cents === null ? null : (int) $row->old_cents, 'new' => $row->new_cents === null ? null : (int) $row->new_cents,
                'default' => $row->default_cents === null ? null : (int) $row->default_cents, 'currency' => (string) $row->currency,
                'reason' => (string) $row->reason, 'by' => trim(($row->first_name ?? '').' '.($row->last_name ?? '')), 'at' => (string) $row->created_at])
            ->all();
    }

    /**
     * Rules for one feature's caps. A blank cap goes back to the config
     * default. A personal feature has no school cap.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function rules(array $data): array
    {
        $features = array_keys(array_filter((array) config('ai.inference.features', []), 'is_array'));
        $rules = [
            'feature' => ['required', 'string', Rule::in($features)],
            // Kept in the cap history and the audit log with who changed it.
            'reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
        $personal = is_string($data['feature'] ?? null) && self::personal($data['feature']);
        foreach (self::MAXIMUM as $cap => $maximum) {
            $rules[$cap] = $personal && $cap === 'school_daily_cents'
                ? ['prohibited']
                : ['nullable', 'integer', 'min:0', 'max:'.$maximum];
        }

        return $rules;
    }

    /**
     * Save one feature's caps. Only an Owner (ai.caps). Returns how many caps
     * changed; nothing is written, and no audit row, when none did.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, User $actor): int
    {
        abort_unless(app(PlatformStaffAccess::class)->can($actor, 'ai.caps'), 403);
        $data = Validator::make($data, self::rules($data), [
            'school_daily_cents.prohibited' => 'The tutor is a personal service: it has no school cap and does not count against the school\'s daily cap.',
        ])->validate();
        $feature = (string) $data['feature'];
        $currency = (string) config('ai.billing_currency');

        return DB::transaction(function () use ($data, $feature, $currency, $actor): int {
            abort_unless(app(PlatformStaffAccess::class)->can($actor, 'ai.caps'), 403);
            $rows = DB::table('platform_ai_caps')->where('feature', $feature)->lockForUpdate()->get()->keyBy('cap');
            $defaults = $this->caps()[$feature] ?? [];
            $before = [];
            $after = [];
            foreach (array_keys(self::MAXIMUM) as $cap) {
                if (! array_key_exists($cap, $data) || ($defaults[$cap]['default'] ?? null) === null) {
                    continue;
                }
                $old = isset($rows[$cap]) ? (int) $rows[$cap]->cents : null;
                $new = $data[$cap] === null ? null : (int) $data[$cap];
                $default = (int) $defaults[$cap]['default'];
                // The value already in force, whether saved or the default, is no change.
                if ($new === $old || ($old === null && $new === $default)) {
                    continue;
                }
                if ($new === null) {
                    DB::table('platform_ai_caps')->where('feature', $feature)->where('cap', $cap)->delete();
                } else {
                    DB::table('platform_ai_caps')->updateOrInsert(['feature' => $feature, 'cap' => $cap],
                        ['cents' => $new, 'updated_by' => $actor->id, 'updated_at' => now(), 'created_at' => $rows[$cap]->created_at ?? now()]);
                }
                DB::table('platform_ai_cap_changes')->insert(['feature' => $feature, 'cap' => $cap, 'old_cents' => $old, 'new_cents' => $new,
                    'default_cents' => $default, 'currency' => $currency, 'reason' => (string) $data['reason'], 'changed_by' => $actor->id, 'created_at' => now()]);
                $before[$cap] = $old ?? $default;
                $after[$cap] = $new ?? $default;
            }
            if ($after === []) {
                return 0;
            }
            // Feature, cents, currency and the reason only: never a key, a prompt or a school.
            Audit::record('platform_ai_caps_changed', null, ['feature' => $feature, ...$before],
                ['feature' => $feature, ...$after, 'currency' => $currency, 'reason' => (string) $data['reason'], 'changed_by' => $actor->id]);

            return count($after);
        });
    }

    /** @return array<string, array<string, int>> */
    private function saved(): array
    {
        $saved = [];
        foreach (DB::table('platform_ai_caps')->get(['feature', 'cap', 'cents']) as $row) {
            $saved[(string) $row->feature][(string) $row->cap] = (int) $row->cents;
        }

        return $saved;
    }
}
