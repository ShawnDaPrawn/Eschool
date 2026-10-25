<?php

declare(strict_types=1);

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardDefinition;
use App\Models\Recognition\AwardNomination;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\AwardWindow;
use App\Models\Recognition\House;
use App\Models\Recognition\HouseMembership;
use App\Models\Recognition\PointCategory;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\AwardNominations;
use App\Services\Recognition\AwardPresentations;
use App\Services\Recognition\RecognitionCatalog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

$database = (string) config('database.connections.'.config('database.default').'.database');
if (! str_ends_with($database, '_test') && $database !== 'eschool_e2e') {
    throw new RuntimeException('Recognition browser fixtures require an isolated test database.');
}

return app(CurrentSchool::class)->run(1, function (): array {
    $admin = User::where('email', 'admin.a@demo.navigateed.test')->firstOrFail();
    $head = User::where('email', 'teacher.a@demo.navigateed.test')->firstOrFail();
    $gate = app(ModuleGate::class);
    $gate->change('house_points', true, $admin);
    $gate->change('awards', true, $admin);
    $gate->forget();
    $year = SessionYear::findOrFail((int) schoolSetting('session_year'));
    $start = substr((string) $year->start_date, 0, 10);
    $end = substr((string) $year->end_date, 0, 10);
    $room = ClassSection::with('class')->whereHas('class', fn ($q) => $q->where('name', 'Grade 5'))->firstOrFail();
    $grade = (int) GradeLevel::where('code', '5')->value('id');
    $room->class->forceFill(['grade_level_id' => $grade])->save();
    // Other workflows change demo placements and dates. Own the complete
    // synthetic recognition identity instead of borrowing the first legacy row.
    $person = User::firstOrCreate(['email' => 'recognition.learner@demo.navigateed.test'], ['first_name' => 'RecognitionBrowser', 'last_name' => 'Learner', 'password' => Hash::make('NavigateDemo2026'), 'status' => 0]);
    $learner = Students::firstOrCreate(['user_id' => $person->id], ['class_id' => $room->class_id, 'class_section_id' => $room->id,
        'category_id' => Students::whereNotNull('category_id')->value('category_id'), 'admission_no' => 'RECOGNITION-BROWSER', 'roll_number' => 990, 'admission_date' => $start]);
    $guardian = User::firstOrCreate(['email' => 'recognition.guardian@demo.navigateed.test'], ['first_name' => 'RecognitionBrowser', 'last_name' => 'Guardian', 'password' => Hash::make('NavigateDemo2026'), 'status' => 1]);
    $guardian->forceFill(['email_verified_at' => now()])->save();
    foreach ([[$guardian, 'Parent'], [$person, 'Student']] as [$user, $role]) {
        $user->assignRole($role);
        SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $user->id, 'role' => $role], ['status' => 'active', 'starts_on' => $start, 'ends_on' => null]);
    }
    GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id], ['relationship' => 'guardian', 'status' => 'active', 'can_approve' => true]);
    LearnerApproval::firstOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id, 'consent_version' => config('identity.consent_version')], ['consented_at' => now()]);
    // Activation follows the verified synthetic guardian's recorded consent.
    $person->forceFill(['status' => 1])->save();
    $learner->setRelation('user', $person);
    Enrolment::updateOrCreate(['learner_id' => $learner->id, 'session_year_id' => $year->id, 'class_section_id' => $room->id], ['starts_on' => $start, 'ends_on' => $end, 'legacy_dates' => false]);
    AcademicLeadership::firstOrCreate(['user_id' => $head->id, 'session_year_id' => $year->id, 'kind' => 'grade_head', 'grade_level_id' => $grade], ['starts_on' => $start, 'ends_on' => $end, 'reason' => 'Synthetic recognition head', 'revision' => 1]);
    $op = fn (array $d) => ['operation_id' => (string) Str::uuid()] + $d;
    $catalog = app(RecognitionCatalog::class);
    $house = House::where('name', 'Recognition Mountain')->first() ?? $catalog->house($admin, $op(['name' => 'Recognition Mountain', 'emblem' => 'mountain', 'primary_colour' => '#315b8c', 'secondary_colour' => '#ffffff', 'active' => true]));
    $category = PointCategory::where('name', 'Synthetic helpfulness')->first() ?? $catalog->category($admin, $op(['name' => 'Synthetic helpfulness', 'default_points' => 5, 'minimum_points' => 5, 'maximum_points' => 5, 'active' => true]));
    if (! HouseMembership::where('learner_id', $learner->id)->where('session_year_id', $year->id)->exists()) {
        $catalog->membership($admin, $op(['learner_id' => $learner->id, 'house_id' => $house->id, 'session_year_id' => $year->id, 'starts_on' => $start, 'ends_on' => null, 'active' => true, 'reason' => 'Synthetic browser house']));
    }
    $awardCategory = AwardCategory::where('name', 'Recognition grade awards')->first() ?? $catalog->awardCategory($admin, $op(['name' => 'Recognition grade awards', 'parent_id' => null, 'scope_kind' => 'grade', 'grade_level_id' => $grade, 'subject_id' => null, 'activity_group_id' => null, 'active' => true]));
    $award = AwardDefinition::where('name', 'Recognition service award')->first() ?? $catalog->definition($admin, $op(['award_category_id' => $awardCategory->id, 'name' => 'Recognition service award', 'criteria' => 'Synthetic service evidence', 'frequency' => 'annual', 'active' => true]));
    if (! AwardWindow::where('award_category_id', $awardCategory->id)->where('session_year_id', $year->id)->exists()) {
        $catalog->window($admin, $op(['revision' => 0, 'award_category_id' => $awardCategory->id, 'session_year_id' => $year->id, 'opens_on' => $start, 'closes_on' => $end, 'status' => 'open']));
    }
    $settings = $catalog->settings();
    if ($settings->daily_staff_cap < 500) {
        $catalog->configure($admin, 'house_points', $op(['revision' => $settings->revision, 'daily_staff_cap' => 500, 'public_points_allowed' => false, 'principal_approval_required' => false]));
    }
    foreach (AwardPresentation::where('award_definition_id', $award->id)->where('learner_id', $learner->id)->where('state', 'active')->get() as $p) {
        app(AwardPresentations::class)->void($admin, (int) $p->id, $op(['revision' => $p->revision, 'reason' => 'Reset synthetic browser certificate']));
    }
    foreach (AwardNomination::where('award_definition_id', $award->id)->where('learner_id', $learner->id)->whereIn('state', ['pending', 'approved'])->get() as $n) {
        app(AwardNominations::class)->withdraw($admin, (int) $n->id, $op(['revision' => $n->revision, 'reason' => 'Reset synthetic browser nomination']));
    }

    return ['learner' => (int) $learner->id, 'name' => $learner->user->full_name, 'house' => (int) $house->id, 'point_category' => (int) $category->id, 'grade' => $grade, 'definition' => (int) $award->id];
});
