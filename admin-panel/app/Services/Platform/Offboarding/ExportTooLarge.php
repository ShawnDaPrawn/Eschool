<?php

declare(strict_types=1);

namespace App\Services\Platform\Offboarding;

use RuntimeException;

/** The school's export would pass config('offboarding.maximum_export_bytes'). */
class ExportTooLarge extends RuntimeException {}
