<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\VendorStockEvent;
use App\Models\Commerce\VendorStockMovement;
use Illuminate\Support\Facades\Schema;

final class VendorInventory
{
    public function actual(int $product): int
    {
        return (int) (Schema::hasTable('vendor_stock_events') ? VendorStockEvent::where('product_id', $product)->sum('units_delta') : VendorStockMovement::where('product_id', $product)->sum('units_delta'));
    }

    public function order(VendorOrderLine $line, ?VendorOrderAction $cancellation = null): void
    {
        if (! $line->tracks_stock) {
            return;
        }
        $balance = VendorStockBalance::where('product_id', $line->product_id)->lockForUpdate()->sole();
        $actual = $this->actual($line->product_id);
        abort_unless($actual === $balance->on_hand, 409, 'Reconcile counted stock before continuing.');
        $delta = $cancellation === null ? -$line->quantity : $line->quantity;
        abort_unless($actual + $delta >= 0 && $actual + $delta <= 1000000000, 409, 'The requested counted quantity is unavailable.');
        VendorStockEvent::create(['product_id' => $line->product_id, 'order_line_id' => $line->id, 'order_action_id' => $cancellation?->id, 'kind' => $cancellation === null ? 'purchase' : 'cancellation', 'sequence' => $balance->revision + 1, 'units_delta' => $delta, 'before_units' => $actual, 'after_units' => $actual + $delta, 'created_at' => now('UTC')]);
    }
}
