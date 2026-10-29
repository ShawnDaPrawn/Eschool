<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeePlan;
use DomainException;
use Illuminate\Support\Facades\Schema;

/** Academic cleanup cannot make canonical financial configuration/history vanish. */
final class FinancialYears
{
    public static function assertDeletable(int $year): void
    {
        if ((Schema::hasTable('fee_plans') && FeePlan::withTrashed()->where('session_year_id', $year)->exists()) || (Schema::hasTable('bill_runs') && BillRun::where('session_year_id', $year)->exists())) {
            throw new DomainException(__('This school year has current financial configuration or history. Keep the year available for financial records.'));
        }
    }
}
