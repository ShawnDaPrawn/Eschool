<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Checks raw retained history even when commerce is unavailable. Never invents stock. */
final class VendorCatalogueReconciliation
{
    public function inspect(): array
    {
        $report = ['vendor_identity_mismatches' => 0, 'vendor_stock_history_mismatches' => 0, 'vendor_stock_projection_mismatches' => 0];
        if (! Schema::hasTable('vendors')) {
            return $report;
        }
        $school = app(CurrentSchool::class)->id();
        $report['vendor_identity_mismatches'] = DB::table('vendors as v')->leftJoin('ledger_accounts as a', function ($join) {
            $join->on('a.id', 'v.ledger_account_id')->on('a.school_id', 'v.school_id');
        })->where('v.school_id', $school)->where(function ($q) {
            $q->whereNull('a.id')->orWhere('a.type', '<>', 'vendor')->orWhere('a.module', '<>', 'vendors')->orWhereNotNull('a.owner_user_id')->orWhereColumn('a.currency', '<>', 'v.currency')->orWhereRaw("a.reference <> CONCAT('vendors:merchant:',v.operation_id,':',v.currency)");
        })->count();
        foreach (DB::table('vendor_products')->where('school_id', $school)->orderBy('id')->get() as $product) {
            $balance = DB::table('vendor_stock_balances')->where('school_id', $school)->where('product_id', $product->id)->first();
            $events = Schema::hasTable('vendor_stock_events');
            $movements = DB::table($events ? 'vendor_stock_events' : 'vendor_stock_movements')->where('school_id', $school)->where('product_id', $product->id)->orderBy($events ? 'sequence' : 'id')->get();
            if ($events && $movements->where('kind', 'manual')->count() !== DB::table('vendor_stock_movements')->where('school_id', $school)->where('product_id', $product->id)->count()) {
                $report['vendor_stock_history_mismatches']++;
            }
            if (! $product->tracks_stock) {
                $report['vendor_stock_history_mismatches'] += $balance !== null || $movements->isNotEmpty() ? 1 : 0;

                continue;
            }
            $units = 0;
            foreach ($movements as $index => $movement) {
                if ($events && (int) $movement->sequence !== $index + 1) {
                    $report['vendor_stock_history_mismatches']++;
                }
                if ((int) $movement->before_units !== $units || (int) $movement->after_units !== $units + (int) $movement->units_delta) {
                    $report['vendor_stock_history_mismatches']++;
                }
                $units += (int) $movement->units_delta;
            }
            if ($balance === null || (int) $balance->on_hand !== $units || (int) $balance->revision !== $movements->count()) {
                $report['vendor_stock_projection_mismatches']++;
            }
        }

        return $report;
    }

    public static function healthy(array $report): bool
    {
        return array_sum($report) === 0;
    }
}
