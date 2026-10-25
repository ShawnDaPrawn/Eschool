<?php

use App\Services\Consultations\ConsultationNotices;
use App\Services\Recognition\RecognitionNotices;
use App\Services\Transfers\TransferNoticeScope;
use App\Services\Wellbeing\WellbeingNoticeReadState;
use App\Services\Wellbeing\WellbeingNotices;

// Private providers scope inbox SQL and independently recheck delivery.
return [
    'scopes' => ['consultations.' => ConsultationNotices::class, 'wellbeing.' => WellbeingNotices::class, 'transfer.' => TransferNoticeScope::class, 'recognition.' => RecognitionNotices::class],
    'read_states' => ['wellbeing.' => WellbeingNoticeReadState::class],
    'staff_inbox' => ['consultations.staff_opened' => ['feature' => 'consultations', 'title' => 'School consultation available', 'message' => 'Open school consultations to review an available ballot.'], 'wellbeing.staff_update' => ['feature' => 'wellbeing', 'title' => 'Private school update', 'message' => 'Open your private school area to review an available update.']],
    'staff_types' => ['consultations.staff_opened', 'wellbeing.staff_update'],
];
