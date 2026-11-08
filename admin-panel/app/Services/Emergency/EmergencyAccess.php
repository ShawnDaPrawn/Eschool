<?php

declare(strict_types=1);

namespace App\Services\Emergency;

use App\Models\Parents;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Who may do what in emergency management. Every check reloads the person
 * and their roles at the current school, so an old role or another school's
 * membership never grants anything. engineering/specs/community/emergency-management.md.
 */
class EmergencyAccess
{
    /** Send critical alerts, lockdowns and the all-clear: only roles the school named. */
    public const ALERT = 'emergency-alert-send';

    /** Roll call for every class, visitor sign-in and releasing children. */
    public const RESPOND = 'emergency-respond';

    /** The drill log and visitor retention. */
    public const MANAGE = 'emergency-manage';

    public function actor(User $actor, bool $write = false): User
    {
        $scope = app(CurrentSchool::class);
        $scope->school()?->refresh();
        abort_unless($scope->isSet() && ! $scope->isAcrossSchools() && ! $scope->isImpersonating() && $scope->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        app(ModuleGate::class)->forget();
        abort_unless($write ? app(ModuleGate::class)->writable('emergency') : app(ModuleGate::class)->allows('emergency'), 404);

        return $fresh;
    }

    /** A member of staff here: any role other than learner or guardian. */
    public function staff(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless($this->isStaff($fresh), 403);

        return $fresh;
    }

    public function alerter(User $actor, bool $write = true): User
    {
        $fresh = $this->staff($actor, $write);
        abort_unless($this->may($fresh, self::ALERT), 403, 'Only the roles your school named can send emergency alerts.');

        return $fresh;
    }

    public function responder(User $actor, bool $write = true): User
    {
        $fresh = $this->staff($actor, $write);
        abort_unless($this->may($fresh, self::RESPOND), 403);

        return $fresh;
    }

    public function manager(User $actor, bool $write = true): User
    {
        $fresh = $this->staff($actor, $write);
        abort_unless($this->may($fresh, self::MANAGE), 403);

        return $fresh;
    }

    public function guardian(User $actor): User
    {
        $fresh = $this->actor($actor);
        abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 403);

        return $fresh;
    }

    public function isStaff(User $fresh): bool
    {
        return array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) !== [];
    }

    public function may(User $fresh, string $permission): bool
    {
        return app(ProfilePolicy::class)->permission($fresh, $permission);
    }

    /** What the signed-in person may do here, for the panel and the app. */
    public function abilities(User $fresh): array
    {
        $staff = $this->isStaff($fresh);

        return [
            'staff' => $staff,
            'guardian' => in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true),
            'can_alert' => $staff && $this->may($fresh, self::ALERT),
            'can_respond' => $staff && $this->may($fresh, self::RESPOND),
            'can_manage' => $staff && $this->may($fresh, self::MANAGE),
            'class_sections' => $staff ? $this->classSections($fresh) : [],
            'writable' => app(ModuleGate::class)->writable('emergency'),
        ];
    }

    /** The classes this person is class teacher of this year: they take that class's roll call. */
    public function classSections(User $fresh): array
    {
        if (! in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true)) {
            return [];
        }

        return DB::table('class_teachers')->join('teachers', 'teachers.id', '=', 'class_teachers.class_teacher_id')
            ->where('class_teachers.school_id', app(CurrentSchool::class)->id())->where('teachers.school_id', app(CurrentSchool::class)->id())->where('teachers.user_id', $fresh->id)
            ->where('class_teachers.session_year_id', (int) schoolSetting('session_year'))
            ->pluck('class_teachers.class_section_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** The guardian's own children here, as learner ids. */
    public function children(User $fresh): array
    {
        $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);

        return app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Whether the panel page (and its sidebar entry) is for this person. */
    public function available(User $actor): bool
    {
        try {
            return $this->isStaff($this->actor($actor));
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }
}
