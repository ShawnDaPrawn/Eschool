<?php

declare(strict_types=1);

namespace App\Mail\Platform;

use App\Models\Platform\NotificationDelivery;

/** Mail whose authority can expire while it waits for its worker. */
interface ChecksRecipient
{
    public function mayDeliver(?NotificationDelivery $delivery): bool;
}
