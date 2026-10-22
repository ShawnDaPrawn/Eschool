<?php

declare(strict_types=1);

namespace App\Http\Controllers\Classes\Concerns;

/**
 * The session year the admin panel is currently set to. Shared by the class
 * and class-subject controllers.
 */
trait ClassSessionYear
{
    private function getCurrentSessionYearId(): ?int
    {
        $sessionYearId = getSettings('session_year')['session_year'];

        return $sessionYearId ? (int) $sessionYearId : null;
    }
}
