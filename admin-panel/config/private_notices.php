<?php

use App\Services\Wellbeing\WellbeingNotices;

// Private providers scope inbox SQL and independently recheck delivery.
return [
    'scopes' => ['wellbeing.' => WellbeingNotices::class],
    'staff_types' => ['wellbeing.staff_update'],
];
