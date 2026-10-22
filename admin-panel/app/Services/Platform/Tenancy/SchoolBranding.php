<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use App\Models\Platform\School;
use App\Models\Settings;

/**
 * Keeps a school's row (name, crest, timezone, colours) in step with the
 * settings its admins edit in the panel, so the school switcher and
 * /me/context's list of schools show what the school calls itself. Called
 * whenever one of these settings is written (Settings and SettingsBuilder).
 */
final class SchoolBranding
{
    /** Settings type => schools column. */
    public const COLUMNS = [
        'school_name' => 'name',
        'school_short_name' => 'short_name',
        'school_tagline' => 'tagline',
        'logo1' => 'crest_path',
        'time_zone' => 'timezone',
        'theme_color' => 'theme_color',
        'secondary_color' => 'secondary_color',
    ];

    /** The schools columns' lengths. */
    private const LENGTHS = [
        'name' => 255, 'short_name' => 64, 'tagline' => 255, 'crest_path' => 255,
        'timezone' => 64, 'theme_color' => 16, 'secondary_color' => 16,
    ];

    public static function isBrandingKey(mixed $type): bool
    {
        return is_string($type) && isset(self::COLUMNS[$type]);
    }

    public static function sync(int $schoolId): void
    {
        $school = School::query()->find($schoolId);
        if ($school === null) {
            return;
        }

        $settings = Settings::withoutGlobalScope(PlatformSettings::class)
            ->where('school_id', $schoolId)
            ->whereIn('type', array_keys(self::COLUMNS))
            ->orderBy('id')
            ->pluck('message', 'type');

        foreach (self::COLUMNS as $type => $column) {
            $value = trim((string) ($settings[$type] ?? ''));
            if ($column === 'name' || $column === 'timezone') {
                // Never empty, and only a real timezone.
                if ($value === '' || ($column === 'timezone' && ! in_array($value, timezone_identifiers_list(), true))) {
                    continue;
                }
            }
            $school->setAttribute($column, $value === '' ? null : mb_substr($value, 0, self::LENGTHS[$column]));
        }
        if ($school->isDirty()) {
            $school->save();
        }
    }

    private function __construct() {}
}
