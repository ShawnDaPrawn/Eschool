<?php

use App\Services\Recognition\RecognitionNotices;
use App\Services\Transfers\TransferNoticeScope;
use App\Services\Wellbeing\WellbeingNotices;

// Private providers scope inbox SQL and independently recheck delivery.
return [
    'scopes' => ['wellbeing.' => WellbeingNotices::class, 'transfer.' => TransferNoticeScope::class, 'recognition.' => RecognitionNotices::class],
    'staff_types' => ['wellbeing.staff_update'],
];
