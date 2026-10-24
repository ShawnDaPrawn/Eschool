<?php

declare(strict_types=1);

namespace App\Services\Platform\Files;

interface UploadScanner
{
    /** @return array{clean:bool,engine:string,signature:?string} */
    public function scan(string $bytes): array;
}
