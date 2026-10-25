<?php

declare(strict_types=1);

namespace App\Events\Transfers;

/** Dispatched synchronously inside the completed transfer transaction. References only. */
final readonly class TransferCompleted
{
    public function __construct(public int $sourceSchool, public int $packageId, public ?int $destinationSchool = null, public ?int $receiptId = null, public ?int $yearId = null) {}
}
