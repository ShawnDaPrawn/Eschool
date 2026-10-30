<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Transfers\TransferPackage;
use App\Models\Transfers\TransferReceipt;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TransferAuthority
{
    public function actor(User $actor, bool $write = false): User
    {
        $s = app(CurrentSchool::class);
        abort_unless($s->isSet() && ! $s->isAcrossSchools() && ! $s->isImpersonating() && $s->school()?->refresh()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        $g = app(ModuleGate::class);
        $g->forget();
        abort_unless($write ? $g->writable('school_transfers') : $g->allows('school_transfers'), 403);

        return $fresh;
    }

    public function staff(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $a = app(AcademicAuthority::class);
        $admin = array_intersect(app(ProfilePolicy::class)->roles($fresh), ['Admin', 'Super Admin']) !== [] && $a->manages($fresh);
        abort_unless($admin || $a->currentLeadership($fresh, 'principal', 0), 403);

        return $fresh;
    }

    public function guardian(User $actor, int $learner, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless($fresh->email_verified_at !== null && in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 403);
        abort_unless(GuardianLink::where('learner_id', $learner)->where('guardian_user_id', $fresh->id)->where('status', 'active')->where('can_approve', true)->exists(), 404);
        // Nothing is written for a leaver (Action Plan 1.21).
        app(LeaverState::class)->guardFamilyWrite($learner);

        return $fresh;
    }

    public function outgoing(User $actor, int $id, bool $write = false): TransferPackage
    {
        $this->staff($actor, $write);

        return TransferPackage::findOrFail($id);
    }

    public function ownConsent(User $actor, int $id, bool $write = false): TransferPackage
    {
        $this->actor($actor, $write);
        $row = TransferPackage::where('guardian_id', $actor->id)->findOrFail($id);
        $this->guardian($actor, (int) $row->learner_id, $write);

        return $row;
    }

    /** Deliberate, exact destination hand-off; no general across-school listing. */
    public function incoming(User $actor, int $id, bool $write = false): TransferPackage
    {
        $this->staff($actor, $write);
        $school = app(CurrentSchool::class)->id();

        return TransferPackage::acrossSchools()->where('to_school_id', $school)->whereIn('state', ['released', 'accepted'])->findOrFail($id);
    }

    public function receipt(User $actor, int $id): TransferReceipt
    {
        $this->staff($actor);

        return TransferReceipt::findOrFail($id);
    }

    public function available(User $actor): bool
    {
        try {
            $this->staff($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
