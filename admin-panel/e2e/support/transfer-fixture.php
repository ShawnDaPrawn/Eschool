<?php

use App\Models\Academics\GradeLevel;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Identity\StudentNumbers;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Str;

$schools = app(CurrentSchool::class);
$today = now('Africa/Johannesburg')->toDateString();
$start = now('Africa/Johannesburg')->addDay()->toDateString();
$destination = $schools->run(2, function (): array {
    $admin = User::where('email', 'admin.b@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('school_transfers', true, $admin);
    $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
    $room = ClassSection::with('class')->orderBy('id')->firstOrFail();
    $room->class->forceFill(['grade_level_id' => GradeLevel::where('code', '5')->value('id')])->save();

    return ['year' => (int) $year->id, 'room' => (int) $room->id, 'category' => (int) Category::value('id')];
});
$result = $schools->run(1, function () use ($today, $start, $destination): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    app(ModuleGate::class)->change('school_transfers', true, $admin);
    $guardian = User::where('email', 'parent.a@demo.navigateed.test')->firstOrFail();
    $guardian->forceFill(['email_verified_at' => now()])->save();
    $parent = Parents::where('user_id', $guardian->id)->firstOrFail();
    $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
    $room = ClassSection::with('class')->orderBy('id')->firstOrFail();
    $room->class->forceFill(['grade_level_id' => GradeLevel::where('code', '5')->value('id')])->save();
    $children = [];
    foreach ([390, 1280, 999] as $width) {
        $person = User::create(['first_name' => 'Transfer'.$width, 'last_name' => 'Synthetic', 'email' => 'transfer.'.Str::uuid().'@example.test', 'password' => bcrypt('SyntheticOnly2026'), 'status' => 0]);
        $person->assignRole('Student');
        $child = Students::create(['user_id' => $person->id, 'class_id' => $room->class_id, 'class_section_id' => $room->id, 'category_id' => Category::value('id'), 'guardian_id' => $parent->id, 'admission_no' => 'TR-BROWSER-'.Str::uuid(), 'admission_date' => substr((string) $year->start_date, 0, 10), 'application_type' => 'offline']);
        app(StudentNumbers::class)->assign($child);
        Enrolment::create(['learner_id' => $child->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id, 'starts_on' => substr((string) $year->start_date, 0, 10), 'ends_on' => null, 'legacy_dates' => false]);
        app(EnrolmentService::class)->syncProjection((int) $child->id, (int) $year->id);
        GuardianLink::updateOrCreate(['learner_id' => $child->id, 'guardian_user_id' => $guardian->id], ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true, 'can_see_fees' => false]);
        $children[$width] = ['id' => (int) $child->id, 'name' => $person->full_name];
    }

    return ['children' => $children, 'guardian' => (int) $guardian->id, 'token' => $guardian->createToken('synthetic-browser-transfer')->plainTextToken, 'destination' => $destination, 'today' => $today, 'start' => $start];
});
echo json_encode($result, JSON_THROW_ON_ERROR);
