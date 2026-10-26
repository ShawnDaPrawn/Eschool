<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\Platform\Files\UploadScanner;

/** Test protocol only; never evidence of a deployed scanner. */
class SyntheticCleanScanner implements UploadScanner
{
    public int $scans = 0;

    public function scan(string $bytes): array
    {
        $this->scans++;

        return ['clean' => true, 'engine' => 'synthetic-test-protocol'];
    }
}
