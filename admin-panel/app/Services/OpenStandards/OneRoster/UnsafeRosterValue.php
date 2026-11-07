<?php

declare(strict_types=1);

namespace App\Services\OpenStandards\OneRoster;

use RuntimeException;

final class UnsafeRosterValue extends RuntimeException
{
    public const MESSAGE = 'The roster contains an unsafe spreadsheet value. Correct the source data before preparing another export.';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
