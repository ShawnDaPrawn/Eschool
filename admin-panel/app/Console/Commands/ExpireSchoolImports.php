<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Saas\PlatformSchoolImports;
use Illuminate\Console\Command;

/** Imports run on a school's behalf (SA.11) that were not applied in time expire, and their files are deleted. */
class ExpireSchoolImports extends Command
{
    protected $signature = 'platform:expire-school-imports';

    protected $description = 'Expire imports run on a school\'s behalf that were not applied in time, deleting their files';

    public function handle(PlatformSchoolImports $imports): int
    {
        $expired = $imports->expire();
        $this->info($expired.' expired.');

        return self::SUCCESS;
    }
}
