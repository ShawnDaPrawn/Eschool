<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

/**
 * Which school the current request works for.
 *
 * Until Phase 1 adds schools and memberships there is exactly one school, so
 * this answers 1. Code that needs the school asks this resolver (resolved from
 * the container) rather than hard-coding the id, so Phase 1 can replace the
 * answer with the signed-in user's membership in one place.
 */
class CurrentSchool
{
    /** The single school of a Phase 0 install. */
    public const DEFAULT_ID = 1;

    public function id(): int
    {
        return self::DEFAULT_ID;
    }

    /** Where this school's files live on a disk: schools/{id}/{path}. */
    public function storagePath(string $path = ''): string
    {
        $path = trim($path, '/');

        return 'schools/'.$this->id().($path === '' ? '' : '/'.$path);
    }
}
