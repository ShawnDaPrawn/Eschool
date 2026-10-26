<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Cards\Card;
use App\Models\Cards\CardReaderDevice;
use App\Models\Classroom\ClassroomEligibility;
use App\Models\Classroom\ClassroomLease;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Cards\CardAccess;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

class ClassroomAccess
{
    public function manager(User $actor, bool $write = true): User
    {
        $fresh = app(CardAccess::class)->manage($actor, true, $write);
        app(AcademicAuthority::class)->manage($fresh);

        return $fresh;
    }

    public function teacher(User $actor, int $group): TeachingGroup
    {
        $fresh = app(CardAccess::class)->actor($actor);
        abort_unless(in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true), 403);
        $row = app(AcademicAuthority::class)->group($fresh, $group, true);
        $today = app(AcademicAuthority::class)->today();
        abort_unless($row->active && $row->year !== null && ! $row->year->trashed() && (int) $row->session_year_id === (int) schoolSetting('session_year')
            && substr((string) $row->year?->start_date, 0, 10) <= $today
            && substr((string) $row->year?->end_date, 0, 10) >= $today, 404);

        return $row;
    }

    public function eligible(int $group): ClassroomEligibility
    {
        return ClassroomEligibility::where('teaching_group_id', $group)->where('eligible', true)->firstOrFail();
    }

    public function device(string $id): CardReaderDevice
    {
        $device = CardReaderDevice::where('kind', 'classroom')->findOrFail($id);
        abort_unless($device->status === 'active' && $device->revoked_at === null && $device->expires_at->isFuture(), 403);
        // Registration remains delegated by a currently authorized school manager.
        $this->manager(User::findOrFail($device->issued_by), false);

        return $device;
    }

    public function lease(ClassroomLease $lease): TeachingGroup
    {
        $this->device($lease->device_id);
        abort_unless($lease->revoked_at === null && $lease->expires_at->isFuture(), 403);
        $row = $this->teacher(User::findOrFail($lease->teacher_user_id), (int) $lease->teaching_group_id);
        abort_unless((int) $this->eligible((int) $row->id)->revision === (int) $lease->eligibility_revision, 403);

        return $row;
    }

    public function learner(ClassroomLease $lease, Card $card): Students
    {
        $group = $this->lease($lease);
        abort_unless($card->type === 'learner', 403);
        $person = app(CardAccess::class)->active($card);
        $learner = Students::where('user_id', $person->id)->firstOrFail();
        $today = app(AcademicAuthority::class)->today();
        abort_unless(app(ApprovalService::class)->approved($learner)
            && Enrolment::where('learner_id', $learner->id)->where('session_year_id', $group->session_year_id)->onDate($today)->exists()
            && TeachingGroupMembership::where('teaching_group_id', $group->id)->where('learner_id', $learner->id)
                ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 403);

        return $learner;
    }

    public function approvalRevision(Students $learner): string
    {
        $rows = DB::table('learner_approvals')
            ->where('school_id', app(CurrentSchool::class)->id())->where('learner_id', $learner->id)
            ->orderBy('id')->get(['id', 'consent_version', 'consented_at', 'withdrawn_at'])->all();

        return hash_hmac('sha256', json_encode($rows, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
