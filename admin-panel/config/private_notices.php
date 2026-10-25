<?php

use App\Services\Recognition\RecognitionNotices;
use App\Services\Transfers\TransferNoticeScope;
use App\Services\Wellbeing\WellbeingNoticeReadState;
use App\Services\Wellbeing\WellbeingNotices;

// Private providers scope inbox SQL and independently recheck delivery.
return [
    'scopes' => ['wellbeing.' => WellbeingNotices::class, 'transfer.' => TransferNoticeScope::class, 'recognition.' => RecognitionNotices::class],
    'read_states' => ['wellbeing.' => WellbeingNoticeReadState::class],
    'staff_inbox' => ['wellbeing.staff_update' => ['feature' => 'wellbeing', 'title' => 'Private school update', 'message' => 'Open your private school area to review an available update.']],
    'staff_types' => ['wellbeing.staff_update'],
];
