<?php

declare(strict_types=1);

namespace App\Listeners\Gallery;

use App\Events\Transfers\TransferCompleted;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferReceipt;
use App\Services\Gallery\GalleryTransfers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class ApplyGalleryTransfer
{
    public function handle(TransferCompleted $event): void
    {
        abort_unless(DB::transactionLevel() > 0, 409);
        $scope = app(CurrentSchool::class);
        $package = $scope->run($event->sourceSchool, function () use ($event): TransferPackage {
            $row = TransferPackage::lockForUpdate()->findOrFail($event->packageId);
            $internal = $event->destinationSchool !== null;
            abort_unless(! ($row->contents['export_only'] ?? false) && ($internal
                ? $row->state === 'accepted' && (int) $row->to_school_id === $event->destinationSchool
                : $row->state === 'released' && $row->to_school_id === null), 409);
            app(GalleryTransfers::class)->depart($row);

            return $row;
        });
        if ($event->destinationSchool !== null) {
            $scope->run($event->destinationSchool, function () use ($event, $package): void {
                $receipt = TransferReceipt::lockForUpdate()->findOrFail($event->receiptId);
                app(GalleryTransfers::class)->arrive($package, $receipt, (int) $event->yearId);
            });
        }
    }
}
