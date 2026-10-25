<?php

declare(strict_types=1);

namespace App\Services\Cards;

use App\Models\Cards\Card;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;

class CardAccess
{
    public const MANAGE = 'cards-manage';

    public const READERS = 'cards-readers-manage';

    public function actor(User $actor, bool $write = false, bool $revocation = false): User
    {
        $scope = app(CurrentSchool::class);
        $scope->school()?->refresh();
        abort_unless($scope->isSet() && ! $scope->isAcrossSchools() && ! $scope->isImpersonating() && $scope->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        app(ModuleGate::class)->forget();
        if (! $revocation) {
            abort_unless($write ? app(ModuleGate::class)->writable('cards') : app(ModuleGate::class)->allows('cards'), 404);
        }

        return $fresh;
    }

    public function manage(User $actor, bool $readers = false, bool $write = true): User
    {
        $fresh = $this->actor($actor, $write);
        abort_unless(app(ProfilePolicy::class)->permission($fresh, $readers ? self::READERS : self::MANAGE), 403);

        return $fresh;
    }

    public function isManager(User $actor): bool
    {
        return app(ProfilePolicy::class)->permission($actor, self::MANAGE);
    }

    private function currentLearners(): Builder
    {
        return Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))
            ->whereIn('id', Enrolment::where('session_year_id', schoolSetting('session_year'))->onDate(app(AcademicAuthority::class)->today())->select('learner_id'));
    }

    public function person(int $id, string $type): User
    {
        abort_unless(in_array($type, ['learner', 'staff', 'guardian'], true), 422);
        $person = User::where('status', 1)->findOrFail($id);
        abort_if(PlatformStaffIdentity::where('user_id', $id)->where('active', true)->exists(), 404);
        $roles = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $id)->pluck('role')->all();
        if ($type === 'learner') {
            $learner = $this->currentLearners()->where('user_id', $id)->firstOrFail();
            // Card/classroom identity never activates a pending or withdrawn learner,
            // even when an upgraded server still disables a legacy approval flag.
            abort_unless(in_array('Student', $roles, true) && app(ApprovalService::class)->approved($learner), 403);
        } elseif ($type === 'staff') {
            abort_unless(array_diff($roles, ['Parent', 'Student']) !== [], 404);
        } else {
            abort_unless(in_array('Parent', $roles, true), 404);
            $parent = (int) (Parents::where('user_id', $id)->value('id') ?? 0);
            $children = app(GuardianService::class)->childrenFor($id, $parent)->whereIn('id', $this->currentLearners()->select('id'))->get();
            abort_unless($children->contains(fn ($child) => app(ApprovalService::class)->approved($child)), 404);
        }

        return $person;
    }

    public function owner(User $actor, Card $card, bool $revocation = false): User
    {
        $fresh = $this->actor($actor, false, $revocation);
        if ($this->isManager($fresh) || (int) $card->user_id === (int) $fresh->id) {
            return $fresh;
        }
        $roles = app(ProfilePolicy::class)->roles($fresh);
        abort_unless($card->type === 'learner' && in_array('Parent', $roles, true), 404);
        $person = $this->person((int) $card->user_id, 'learner');
        $parent = (int) (Parents::where('user_id', $fresh->id)->value('id') ?? 0);
        abort_unless(app(GuardianService::class)->childrenFor((int) $fresh->id, $parent)->where('user_id', $person->id)->exists(), 404);

        return $fresh;
    }

    public function active(Card $card): User
    {
        abort_unless($card->status === 'active', 409, 'This card is frozen or replaced.');

        return $this->person((int) $card->user_id, $card->type);
    }
}
