<?php

declare(strict_types=1);

use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

// Dedicated inactive synthetic learner and explicit year; never activates an account.
$profile = Model::unguarded(fn () => app(CurrentSchool::class)->run(1, function () {
    $year = SessionYear::firstOrCreate(['name' => 'SA-SAMS export rehearsal'], ['start_date' => now()->startOfYear()->toDateString(), 'end_date' => now()->endOfYear()->toDateString(), 'default' => 0]);
    $room = ClassSection::firstOrFail();
    $user = User::firstOrCreate(['email' => 'sasams.inactive@demo.navigateed.test'], ['first_name' => 'Export', 'last_name' => 'Rehearsal', 'status' => 0, 'password' => Hash::make('NavigateDemo2026')]);
    $learner = Students::firstOrCreate(['user_id' => $user->id], ['class_id' => $room->class_id, 'class_section_id' => $room->id,
        'category_id' => Students::whereNotNull('category_id')->value('category_id'), 'admission_no' => 'SASAMS-DEMO', 'roll_number' => 997, 'admission_date' => $year->start_date]);
    $learner->refresh();
    Enrolment::firstOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id],
        ['starts_on' => $year->start_date, 'ends_on' => $year->end_date, 'reason' => 'Synthetic export rehearsal']);

    return ['schema_version' => 1, 'school_id' => 1, 'session_year_id' => (int) $year->id, 'kind' => 'learners', 'format' => 'csv',
        'version' => 'synthetic-fixture-v1', 'source' => 'Synthetic test only, not an official import layout', 'import_menu' => 'Fixture import',
        'on_date' => now()->toDateString(), 'delimiter' => ';', 'learner_refs' => [$learner->student_number => '000042'],
        'columns' => [['header' => 'LearnerRef', 'field' => 'learner_ref'], ['header' => 'Surname', 'field' => 'last_name']]];
}));
echo json_encode($profile, JSON_THROW_ON_ERROR);
