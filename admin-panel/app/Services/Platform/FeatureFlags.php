<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/**
 * Reads platform-managed feature flags for one school (Action Plan SA.14).
 *
 * A flag is on for a school when it is not killed and either the school is
 * switched on by name, or the rollout is "everyone" and the school is not
 * switched off by name. "beta" is on only for the schools switched on by
 * name; "off" is off everywhere, whatever the school switches say; a kill
 * switch turns it off everywhere at once.
 *
 * A platform flag that shares its key with a built-in app flag can only
 * narrow it: the app sees the built-in value and the platform value together
 * (both must be true), so HQ can switch a shipped feature off for a school
 * but never on where the code says it is not available.
 * engineering/specs/platform/feature-flags.md.
 */
class FeatureFlags
{
    public const ROLLOUTS = ['off', 'beta', 'everyone'];

    public function __construct(private readonly CurrentSchool $currentSchool) {}

    /** @return array<string, bool> every platform flag's value at this school */
    public function forSchool(int $schoolId): array
    {
        $read = function () use ($schoolId): array {
            // One query: the app context has a fixed query budget.
            $rows = DB::table('platform_feature_flags')
                ->leftJoin('platform_feature_flag_schools as switches', fn ($join) => $join->on('switches.flag_id', '=', 'platform_feature_flags.id')->where('switches.school_id', $schoolId))
                ->orderBy('platform_feature_flags.key')
                ->get(['platform_feature_flags.key', 'platform_feature_flags.rollout', 'platform_feature_flags.killed', 'switches.enabled']);
            $values = [];
            foreach ($rows as $flag) {
                $values[(string) $flag->key] = self::value((string) $flag->rollout, (bool) $flag->killed, $flag->enabled === null ? null : (bool) $flag->enabled);
            }

            return $values;
        };
        // Inside the same school (the app context), read directly: switching to
        // across-schools and back would reload the school's settings. The join is
        // limited to this school's switches either way.
        if ($this->currentSchool->isSet() && ! $this->currentSchool->isAcrossSchools() && $this->currentSchool->id() === $schoolId) {
            return $read();
        }

        return $this->currentSchool->acrossSchools($read);
    }

    /** Whether one platform flag is on at a school; an unknown flag is off. */
    public function enabled(string $key, int $schoolId): bool
    {
        return $this->forSchool($schoolId)[$key] ?? false;
    }

    /**
     * The flags the apps receive: the built-in values, each narrowed by a
     * platform flag of the same key, then the platform-only flags.
     *
     * @param  array<string, bool>  $builtIn
     * @return array<string, bool>
     */
    public function merge(array $builtIn, int $schoolId): array
    {
        $platform = $this->forSchool($schoolId);
        foreach ($builtIn as $key => $value) {
            if (array_key_exists($key, $platform)) {
                $builtIn[$key] = $value && $platform[$key];
                unset($platform[$key]);
            }
        }

        return $builtIn + $platform;
    }

    public static function value(string $rollout, bool $killed, ?bool $school): bool
    {
        if ($killed || $rollout === 'off') {
            return false;
        }

        return $school ?? ($rollout === 'everyone');
    }
}
