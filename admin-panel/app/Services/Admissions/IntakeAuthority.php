<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Admissions\Application;
use App\Models\Admissions\Intake;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

class IntakeAuthority
{
    public function enabled(bool $write = false): void
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating() && $school->school()?->refresh()?->isActive(), 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('intake') : $gate->allows('intake'), 403);
    }

    public function staff(User $actor, bool $write = false): User
    {
        $this->enabled($write);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required || PlatformStaffIdentity::where('user_id', $fresh->id)->where('active', true)->exists(), 403);
        $a = app(AcademicAuthority::class);
        abort_unless((array_intersect(app(ProfilePolicy::class)->roles($fresh), ['Admin', 'Super Admin']) !== [] && $a->manages($fresh)) || $a->currentLeadership($fresh, 'principal', 0), 403);

        return $fresh;
    }

    /** Check field privacy before any encrypted application answers are read. */
    public function application(User $actor, int $id, bool $write = false): Application
    {
        $fresh = $this->staff($actor, $write);
        $row = Application::select(['id', 'intake_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at'])->findOrFail($id);
        abort_if($row->state === 'draft' || app(IntakeRetention::class)->due($row), 404, 'The application is not available.');
        $intake = Intake::findOrFail($row->intake_id);
        if (($intake->restricted_field_ids ?? []) !== [] || ProfileFieldDefinition::whereIn('id', $intake->field_ids)->where('sensitive', true)->exists()) {
            abort_unless(app(ProfilePolicy::class)->permission($fresh, 'profile-sensitive-view'), 403);
        }

        return Application::findOrFail($id);
    }

    public function open(Intake $intake): void
    {
        $this->enabled(true);
        $current = Intake::findOrFail($intake->id);
        abort_unless($current->state === 'open' && $current->opens_at->lessThanOrEqualTo(now()) && $current->closes_at->greaterThan(now()), 409, 'This intake is not open for applications.');
    }

    public function owner(int $id, string $key, bool $write = false): Application
    {
        $this->enabled($write);
        abort_unless(strlen($key) === 64 && preg_match('/^[a-f0-9]{64}$/D', $key) === 1, 404);
        $row = Application::select(['id', 'intake_id', 'state', 'terminal_at', 'enrolled_at', 'updated_at', 'draft_key_hash'])->findOrFail($id);
        abort_unless(hash_equals($row->draft_key_hash, hash('sha256', $key)), 404);
        abort_if(app(IntakeRetention::class)->due($row), 404);

        return Application::findOrFail($id);
    }
}
