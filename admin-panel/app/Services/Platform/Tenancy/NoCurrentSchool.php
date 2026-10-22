<?php

declare(strict_types=1);

namespace App\Services\Platform\Tenancy;

use RuntimeException;

/**
 * School-owned data was asked for where no school is chosen. This is a
 * programming error (a route without school.context, a command or job that
 * did not pick a school), never something to catch and carry on from: the
 * alternative would be another school's rows, or every school's.
 */
final class NoCurrentSchool extends RuntimeException
{
    public static function forSignedInRequest(): self
    {
        return new self('A signed-in request asked for school-owned data without a school: add the school.context middleware to its route group.');
    }

    public static function forWrite(): self
    {
        return new self('No school is chosen for this school-owned write: run it inside CurrentSchool::run($school, fn () => ...).');
    }
}
