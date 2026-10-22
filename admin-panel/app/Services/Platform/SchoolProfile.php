<?php

declare(strict_types=1);

namespace App\Services\Platform;

/**
 * The school as the apps see it: name, crest, colours and timezone.
 *
 * Phase 0 runs one school, so this comes from the settings table and its id is
 * always 1 (the school every row is back-filled to in Phase 1). Phase 1 builds
 * it from the schools table and the user's memberships instead; the API shape
 * stays the same.
 */
final readonly class SchoolProfile
{
    /** The only school until Phase 1 adds the schools table. */
    public const SINGLE_SCHOOL_ID = 1;

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
}
