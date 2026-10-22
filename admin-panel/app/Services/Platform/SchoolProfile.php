<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\Platform\School;
use App\Models\Settings;
use App\Services\Platform\Tenancy\PlatformSettings;
use Illuminate\Support\Facades\Storage;

/**
 * The school as the apps see it: name, crest, colours and timezone.
 *
 * The current school's profile is read from its own settings rows (what its
 * admins edit in the panel), over the platform's, with the schools row as
 * the fallback; another school in a list is summarised from its schools
 * row, which follows those settings (SchoolBranding).
 */
final readonly class SchoolProfile
{
    /** The school of a single-school install (every row was back-filled to it). */
    public const SINGLE_SCHOOL_ID = 1;

    /** The settings rows the profile is built from, read in one query. */
    private const SETTINGS = [
        'school_name', 'school_short_name', 'school_tagline', 'logo1',
        'theme_color', 'secondary_color', 'time_zone', 'session_year',
    ];

    public function __construct(
        public int $id,
        public string $name,
        public ?string $shortName,
        public ?string $tagline,
        public ?string $crestUrl,
        public ?string $themeColor,
        public ?string $secondaryColor,
        public string $timezone,
        public ?int $sessionYearId,
    ) {}

    /** The profile of the school the request works in, from its settings. */
    public static function current(School $school): self
    {
        $settings = Settings::withoutGlobalScope(PlatformSettings::class)
            ->whereIn('type', self::SETTINGS)
            ->where(fn ($query) => $query->whereNull('school_id')->orWhere('school_id', $school->id))
            ->orderByRaw('school_id IS NOT NULL')
            ->orderBy('id')
            ->pluck('message', 'type');
        $value = fn (string $type): ?string => ($v = trim((string) ($settings[$type] ?? ''))) === '' ? null : $v;

        $timezone = $value('time_zone') ?? $school->timezone;
        $logo = $value('logo1') ?? $school->crest_path;

        return new self(
            id: (int) $school->id,
            name: $value('school_name') ?? $school->name,
            shortName: $value('school_short_name') ?? $school->short_name,
            tagline: $value('school_tagline') ?? $school->tagline,
            crestUrl: $logo === null ? null : url(Storage::url($logo)),
            themeColor: $value('theme_color') ?? $school->theme_color,
            secondaryColor: $value('secondary_color') ?? $school->secondary_color,
            timezone: in_array($timezone, timezone_identifiers_list(), true) ? $timezone : (string) config('app.timezone'),
            sessionYearId: ctype_digit((string) $value('session_year')) ? (int) $value('session_year') : null,
        );
    }

    /** Another school in a list (the user's schools), from its schools row. */
    public static function summary(School $school): self
    {
        return new self(
            id: (int) $school->id,
            name: $school->name,
            shortName: $school->short_name,
            tagline: $school->tagline,
            crestUrl: $school->crestUrl(),
            themeColor: $school->theme_color,
            secondaryColor: $school->secondary_color,
            timezone: $school->timezone,
            sessionYearId: null,
        );
    }
}
