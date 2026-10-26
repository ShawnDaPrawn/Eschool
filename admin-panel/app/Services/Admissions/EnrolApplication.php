<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\Admissions\Offer;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\SchoolMembership;
use App\Models\Recognition\HouseMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\People\EnrolmentService;
use App\Services\People\Profiles\ProfileForms;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\GuardianInvitations;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class EnrolApplication
{
    private function sources(Intake $intake, Application $application, string $profile): array
    {
        $values = [];
        foreach (app(ApplicationForms::class)->definitions($intake)->where('profile_type', $profile) as $field) {
            if (str_starts_with($field->source, 'user.') && array_key_exists((string) $field->id, $application->answers)) {
                $values[substr($field->source, 5)] = $application->answers[(string) $field->id];
            }
        }

        return $values;
    }

    /** Import only applicant-visible, school-editable selected definitions. */
    private function customValues(Intake $intake, Application $application, string $profile, User $person): void
    {
        foreach (app(ApplicationForms::class)->definitions($intake)->where('profile_type', $profile) as $field) {
            if ($field->source !== 'custom' || ! array_key_exists((string) $field->id, $application->answers)) {
                continue;
            }
            $value = app(ProfileForms::class)->validateValue($person, $field, $application->answers[(string) $field->id]);
            ProfileFieldValue::updateOrCreate(['user_id' => $person->id, 'definition_id' => $field->id], ['value' => $value === null ? null : Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR)), 'encrypted' => true]);
        }
    }

    public function enrol(User $actor, int $id, array $input): Application
    {
        app(IntakeAuthority::class)->application($actor, $id, true);
        $d = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1'],
            'starts_on' => ['required', 'date_format:Y-m-d'], 'class_section_id' => ['nullable', 'integer', 'min:1'], 'house_id' => ['nullable', 'integer', 'min:1'], 'category_id' => ['required', 'integer', 'min:1']])->validate();
        $d['application_id'] = $id;
        $result = app(AdmissionOperations::class)->once($actor, 'application.enrol', $d, function () use ($actor, $id, $d): array {
            $initial = Application::findOrFail($id);
            $grade = IntakeGrade::lockForUpdate()->findOrFail($initial->intake_grade_id);
            $row = Application::lockForUpdate()->findOrFail($id);
            app(IntakeAuthority::class)->application($actor, $id, true);
            abort_unless($row->state === 'accepted' && (int) $row->revision === (int) $d['revision'] && $row->learner_id === null && $row->email_verified_at !== null, 409, 'Only the current accepted application can be enrolled.');
            $offer = Offer::where('application_id', $id)->where('state', 'accepted')->lockForUpdate()->firstOrFail();
            abort_unless($offer->accepted_at !== null && $offer->accepted_at->lessThanOrEqualTo($offer->expires_at), 409, 'The offer needs review before enrolment.');
            abort_unless((int) $row->office_fee_cents === 0 || $row->office_fee_paid, 422, 'Record the office application-fee acknowledgement first.');
            $intake = Intake::findOrFail($row->intake_id);
            $year = SessionYear::findOrFail($intake->session_year_id);
            abort_unless($d['starts_on'] >= substr((string) $year->start_date, 0, 10) && $d['starts_on'] <= substr((string) $year->end_date, 0, 10), 422, 'Choose an enrolment date within the intake school year.');
            app(ApplicationForms::class)->validate($intake, $row->answers, true);
            Category::findOrFail($d['category_id']);
            $choices = app(AdmissionAllocation::class)->choices($row, $d['starts_on']);
            $roomId = $d['class_section_id'] ?? $choices['proposed_homeroom_id'];
            abort_unless($roomId !== null && in_array((int) $roomId, array_column($choices['homerooms'], 'id'), true), 422, 'Choose an available homeroom for the intake grade.');
            $room = ClassSection::with('class')->lockForUpdate()->findOrFail($roomId);
            abort_unless((int) $room->class->grade_level_id === (int) $grade->grade_level_id, 422);
            $houseId = $d['house_id'] ?? $choices['proposed_house_id'];
            abort_if($houseId !== null && ! in_array((int) $houseId, array_column($choices['houses'], 'id'), true), 422, 'Choose an available house or leave house allocation for later.');
            $contact = $row->parent_contact;
            $identity = $this->sources($intake, $row, 'learner');
            $parentIdentity = array_filter($this->sources($intake, $row, 'parent'), fn ($value) => $value !== null && $value !== '');
            $guardianDefaults = array_replace(['first_name' => $contact['first_name'], 'last_name' => $contact['last_name'],
                'mobile' => $contact['mobile'] ?? null, 'gender' => 'Not specified'], $parentIdentity);
            // createOrFirst handles a concurrent global email insertion without
            // overwriting identity data held by another school.
            $guardian = User::acrossSchools()->withTrashed()->firstOrCreate(['email' => $contact['email']], $guardianDefaults + ['password' => bin2hex(random_bytes(32)), 'status' => 1]);
            $newGuardian = $guardian->wasRecentlyCreated;
            $guardian = User::acrossSchools()->withTrashed()->whereKey($guardian->id)->lockForUpdate()->firstOrFail();
            abort_if($guardian->trashed() || (int) $guardian->status !== 1, 409, 'The guardian identity needs review before enrolment.');
            $school = app(CurrentSchool::class)->id();
            abort_if(SchoolMembership::where('school_id', $school)->where('user_id', $guardian->id)->where('role', 'Parent')->where('status', SchoolMembership::SUSPENDED)->exists(), 409, 'Review the existing school membership before enrolment.');
            if ($newGuardian) {
                $guardian->forceFill(['password_change_required' => true])->save();
            }
            $guardian->unsetRelation('roles')->unsetRelation('permissions')->assignRole('Parent');
            app(MembershipService::class)->forget($guardian);
            $parent = Parents::firstOrCreate(['user_id' => $guardian->id], ['first_name' => $guardian->first_name, 'last_name' => $guardian->last_name, 'email' => $guardian->email, 'mobile' => $guardian->mobile ?? '', 'gender' => $guardian->gender ?? 'Not specified']);
            $person = User::create(['first_name' => $identity['first_name'], 'last_name' => $identity['last_name'], 'dob' => $identity['dob'], 'gender' => $identity['gender'] ?? 'Not specified',
                'email' => 'intake-'.$row->public_id.'@learners.invalid', 'password' => bin2hex(random_bytes(32)), 'status' => 1]);
            $person->forceFill(['password_change_required' => true])->save();
            $person->assignRole('Student');
            SchoolMembership::where('school_id', $school)->where('user_id', $person->id)->where('role', 'Student')->update(['starts_on' => $d['starts_on'], 'ends_on' => null]);
            app(MembershipService::class)->forget($person);
            $learner = Students::create(['user_id' => $person->id, 'class_id' => $room->class_id, 'class_section_id' => $room->id, 'category_id' => $d['category_id'], 'guardian_id' => $parent->id,
                'admission_no' => 'IN-'.$row->public_id, 'admission_date' => $d['starts_on'], 'is_new_admission' => true, 'application_type' => 'online']);
            GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id], ['relationship' => $contact['relationship'], 'status' => 'active', 'can_approve' => true, 'can_see_fees' => true]);
            app(EnrolmentService::class)->place((int) $learner->id, (int) $room->id, (int) $year->id, $d['starts_on'], 'Accepted digital intake application');
            if ($houseId !== null) {
                HouseMembership::create(['learner_id' => $learner->id, 'house_id' => $houseId, 'session_year_id' => $year->id, 'starts_on' => $d['starts_on'], 'ends_on' => substr((string) $year->end_date, 0, 10),
                    'active' => true, 'reason' => 'Balanced digital intake allocation', 'revision' => 1]);
            }
            $this->customValues($intake, $row, 'learner', $person);
            $this->customValues($intake, $row, 'parent', $guardian);
            $row->update(['state' => 'enrolled', 'learner_id' => $learner->id, 'enrolled_at' => now(), 'revision' => $row->revision + 1]);
            $offer->update(['state' => 'enrolled', 'revision' => $offer->revision + 1]);
            // Occupancy outlives the private application and is committed under
            // the same grade lock as the accepted-to-enrolled transition.
            $grade->increment('occupied_places');
            app(AdmissionEvents::class)->record($row, 'enrolled', $actor, ['learner_id' => (int) $learner->id, 'class_section_id' => (int) $room->id, 'house_id' => $houseId]);
            // Email proof for an application never creates learner approval.
            app(GuardianInvitations::class)->issue($guardian, $actor);
            Outbox::record(new ContextChanged($school, 'academics.learner_changed'));

            return ['id' => (int) $row->id, 'learner_id' => (int) $learner->id];
        });

        return app(IntakeAuthority::class)->application($actor, $result['id']);
    }
}
