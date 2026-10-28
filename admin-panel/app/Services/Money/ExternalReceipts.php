<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Called under the shared school lock before any new outside receipt posting. */
final class ExternalReceipts
{
    public function assertAvailable(string $method, string $reference): void
    {
        abort_if(DB::table('financial_receipts')->where('school_id', app(CurrentSchool::class)->id())->where('method', $method)->where('source_reference', $reference)->lockForUpdate()->exists(), 409, 'This cash receipt or bank transaction is already recorded.');
    }
}
