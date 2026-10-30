<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Academics\AcademicTransition;
use App\Models\Category;
use App\Models\ClassSection;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\Transfers\TransferPackage;
use App\Models\User;
use App\Services\Academics\AcademicProjections;
use App\Services\Academics\Transitions\LeaveLearner;
use App\Services\People\EnrolmentService;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;

class TransferEnrollment
{
    public function closeSource(User $signer, TransferPackage $package): void
    {
        app(TransferAuthority::class)->staff($signer, true);
        $learner = Students::with('user')->lockForUpdate()->findOrFail($package->learner_id);
        $date = $package->last_attended_on->toDateString();
        $cover = Enrolment::with('homeroom.class')->where('learner_id', $learner->id)->onDate($date)->lockForUpdate()->firstOrFail();
        $data = ['kind' => 'leave', 'learner_id' => (int) $learner->id, 'session_year_id' => (int) $cover->session_year_id, 'effective_on' => $date, 'reason' => 'Consented school transfer '.$package->public_id];
        $transition = AcademicTransition::create(['learner_id' => $learner->id, 'session_year_id' => $cover->session_year_id, 'grade_level_id' => $cover->homeroom?->class?->grade_level_id,
            'actor_id' => $signer->id, 'operation_id' => $package->public_id, 'request_hash' => hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)),
            'kind' => 'leave', 'effective_on' => $date, 'reason' => $data['reason'], 'payload' => $data + ['transfer_package_id' => $package->public_id]]);
        $result = app(LeaveLearner::class)->apply(['data' => $data]);
        $transition->update(['result' => $result]);
        foreach (StudentSessions::where('student_id', $learner->id)->pluck('session_year_id')->unique() as $year) {
            app(EnrolmentService::class)->syncProjection((int) $learner->id, (int) $year);
        }
        foreach (SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')->lockForUpdate()->get() as $membership) {
            if ($membership->starts_on !== null && $membership->starts_on->toDateString() > $date) {
                $membership->update(['status' => SchoolMembership::ENDED, 'ends_on' => $membership->starts_on->toDateString()]);
            } else {
                $membership->update(['ends_on' => $membership->ends_on === null ? $date : min($date, $membership->ends_on->toDateString())]);
            }
        }
        if ($learner->user !== null) {
            app(MembershipService::class)->forget($learner->user);
        }
        // Guardians left with only leavers here, or none (Action Plan 1.21). A
        // transfer dated later is settled only once the learner's membership has ended.
        app(LeaverState::class)->settleGuardians(app(GuardianService::class)->guardianUserIds($learner)->all());
        app(AcademicProjections::class)->refresh();
        Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'academics.learner_changed'));
        Audit::record('transfer.source_enrolment_closed', $transition, [], ['transfer_package_id' => $package->public_id, 'last_attended_on' => $date, 'released_by' => (int) $signer->id]);
    }

    public function placement(array $d, string $date): void
    {
        $year = SessionYear::findOrFail($d['session_year_id']);
        $room = ClassSection::with('class')->findOrFail($d['class_section_id']);
        Category::findOrFail($d['category_id']);
        abort_unless($room->class?->grade_level_id !== null && $year->start_date !== null && $year->end_date !== null && $date >= substr((string) $year->start_date, 0, 10) && $date <= substr((string) $year->end_date, 0, 10), 422, 'Choose a receiving homeroom and year covering the consented start date.');
    }

    /** Existing identities are shared; destination approval is never copied. */
    public function receive(User $actor, User $identity, User $guardian, TransferPackage $package, array $d): Students
    {
        $date = $package->starts_on->toDateString();
        $this->placement($d, $date);
        $room = ClassSection::findOrFail($d['class_section_id']);
        $learner = Students::where('user_id', $identity->id)->lockForUpdate()->first();
        if ($learner !== null) {
            abort_if(Enrolment::where('learner_id', $learner->id)->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))->exists(), 409, 'The receiving learner already has a current or planned enrolment.');
        }
        if ($learner !== null) {
            $knownLink = GuardianLink::where('learner_id', $learner->id)->where('guardian_user_id', $guardian->id)->first();
            abort_if($knownLink !== null && ($knownLink->status !== 'active' || ! $knownLink->can_approve), 409, 'Review the existing receiving-school guardianship before accepting.');
        }
        foreach ([[$identity, 'Student'], [$guardian, 'Parent']] as [$person, $role]) {
            abort_if(SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $person->id)->where('role', $role)->where('status', SchoolMembership::SUSPENDED)->exists(), 409, 'Review the existing receiving-school suspension before accepting.');
        }
        $year = SessionYear::findOrFail($d['session_year_id']);
        // Only detect an overlap for this already-authorized shared identity;
        // no third institution's name, pupil record or dates leave this check.
        $otherLearners = Students::acrossSchools()->where('user_id', $identity->id)->whereNotIn('school_id', [(int) $package->school_id, app(CurrentSchool::class)->id()])->pluck('id');
        abort_if(Enrolment::acrossSchools()->whereIn('learner_id', $otherLearners)->whereDate('starts_on', '<=', substr((string) $year->end_date, 0, 10))->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))->exists(), 409, 'Another enrolment needs institutional review before accepting this transfer.');
        $parent = Parents::where('user_id', $guardian->id)->first();
        if ($parent === null) {
            $parent = Parents::forceCreate(['user_id' => $guardian->id, 'first_name' => $guardian->first_name, 'last_name' => $guardian->last_name, 'gender' => $guardian->gender, 'email' => $guardian->email, 'mobile' => $guardian->mobile]);
        }
        if ($learner === null) {
            $learner = Students::create(['user_id' => $identity->id, 'class_id' => $room->class_id, 'class_section_id' => $room->id, 'category_id' => $d['category_id'],
                'admission_no' => 'TR-'.$package->public_id, 'admission_date' => $date, 'guardian_id' => $parent->id, 'is_new_admission' => true, 'application_type' => 'offline']);
        }
        foreach ([[$identity, 'Student'], [$guardian, 'Parent']] as [$person,$role]) {
            $person->unsetRelation('roles')->unsetRelation('permissions');
            $person->assignRole($role);
            $membership = SchoolMembership::where('school_id', app(CurrentSchool::class)->id())->where('user_id', $person->id)->where('role', $role)->firstOrFail();
            // An existing active affiliation is preserved; a new future place
            // does not grant early school access.
            $hasExisting = $role === 'Parent' ? GuardianLink::where('guardian_user_id', $person->id)->where('status', 'active')->where('learner_id', '!=', $learner->id)->exists() : false;
            if (! $hasExisting) {
                $membership->update(['starts_on' => $date, 'ends_on' => null, 'status' => SchoolMembership::ACTIVE]);
            }
            app(MembershipService::class)->forget($person);
        }
        GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $guardian->id], ['relationship' => (int) $learner->guardian_id === (int) $parent->id ? 'guardian' : 'other', 'status' => 'active', 'can_approve' => true, 'can_see_fees' => false]);
        $join = AcademicTransition::create(['learner_id' => $learner->id, 'session_year_id' => $d['session_year_id'], 'grade_level_id' => $room->class->grade_level_id,
            'actor_id' => $actor->id, 'operation_id' => $package->public_id, 'request_hash' => hash('sha256', json_encode([$package->public_id, $d], JSON_THROW_ON_ERROR)),
            'kind' => 'join', 'effective_on' => $date, 'reason' => 'Accepted consented transfer '.$package->public_id, 'payload' => ['transfer_package_id' => $package->public_id, 'starts_on' => $date]]);
        $entry = app(EnrolmentService::class)->place((int) $learner->id, (int) $room->id, (int) $d['session_year_id'], $date, 'Accepted consented transfer '.$package->public_id);
        $join->update(['result' => ['enrolment_id' => (int) $entry->id, 'learner_activation_copied' => false]]);
        Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'academics.learner_changed'));

        return $learner;
    }
}
