<?php

use App\Services\Consultations\ConsultationNotices;
use App\Services\Discipline\DisciplineNotices;
use App\Services\FamilyForms\ConsentNotices;
use App\Services\Growth\ProgressNoticeScope;
use App\Services\Learning\Digests\DigestNotices;
use App\Services\Recognition\RecognitionNotices;
use App\Services\Transfers\TransferNoticeScope;
use App\Services\Wellbeing\WellbeingNoticeReadState;
use App\Services\Wellbeing\WellbeingNotices;

// Private providers scope inbox SQL and independently recheck delivery.
$disciplineStaff = array_fill_keys([DisciplineNotices::STAFF, DisciplineNotices::REVIEW, DisciplineNotices::HEARING, DisciplineNotices::DETENTION],
    ['feature' => 'discipline', 'title' => 'School discipline update', 'message' => 'Open School discipline to review an available update.']);

return [
    'scopes' => ['consent.' => ConsentNotices::class, 'digest.' => DigestNotices::class, 'discipline.' => DisciplineNotices::class, 'progress.' => ProgressNoticeScope::class, 'consultations.' => ConsultationNotices::class, 'wellbeing.' => WellbeingNotices::class, 'transfer.' => TransferNoticeScope::class, 'recognition.' => RecognitionNotices::class],
    'read_states' => ['wellbeing.' => WellbeingNoticeReadState::class],
    'staff_inbox' => ['progress.staff_band_changed' => ['feature' => 'progress', 'title' => 'Learning progress updated', 'message' => 'Open Learning progress to review an available update.'], 'consultations.staff_opened' => ['feature' => 'consultations', 'title' => 'School consultation available', 'message' => 'Open school consultations to review an available ballot.'], 'wellbeing.staff_update' => ['feature' => 'wellbeing', 'title' => 'Private school update', 'message' => 'Open your private school area to review an available update.']] + $disciplineStaff,
    'staff_types' => ['progress.staff_band_changed', 'consultations.staff_opened', 'wellbeing.staff_update', ...array_keys($disciplineStaff)],
];
