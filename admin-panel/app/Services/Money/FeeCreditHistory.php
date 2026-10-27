<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/** Internal school-scoped projections of finalized corrections only. */
final class FeeCreditHistory
{
    public static function releases(): Builder
    {
        return DB::table('fee_credit_releases as c')->join('fee_credit_notes as n', fn ($j) => $j->on('n.school_id', '=', 'c.school_id')->on('n.id', '=', 'c.credit_note_id'))
            ->join('payment_allocations as a', fn ($j) => $j->on('a.school_id', '=', 'c.school_id')->on('a.id', '=', 'c.allocation_id'))
            ->join('payments as p', fn ($j) => $j->on('p.school_id', '=', 'c.school_id')->on('p.id', '=', 'c.payment_id'))
            ->where('c.school_id', app(CurrentSchool::class)->id())->whereNotNull('n.sealed_at');
    }

    public static function notes(): Builder
    {
        return DB::table('fee_credit_notes as n')->where('n.school_id', app(CurrentSchool::class)->id())->whereNotNull('n.sealed_at');
    }
}
