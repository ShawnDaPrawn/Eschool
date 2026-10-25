<?php

declare(strict_types=1);

namespace App\Services\Ai\Quarantine;

use App\Models\Ai\AiUsage;
use App\Models\User;

/** Server-registered feature adapter. Never accepts a path or remote URL. */
interface QuarantineMediaResolver
{
    /** @return array{bytes:string,mime:string} */
    public function read(User $reviewer, AiUsage $usage, array $reference): array;
}
