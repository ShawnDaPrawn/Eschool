<?php

declare(strict_types=1);

namespace App\Policies\Money;

use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class MoneyPolicy
{
    public function authorize(User $actor, string $permission, bool $write = false, bool $locked = false): User
    {
        abort_unless(in_array($permission, ['fee-plans-manage', 'fee-accounts-manage', 'fee-billing-manage'], true), 403);
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        $school = DB::table('schools')->where('id', $context->id());
        $users = User::whereKey($actor->id)->where('status', 1);
        $memberships = SchoolMembership::active()->where('school_id', $context->id())->where('user_id', $actor->id);
        $support = DB::table('platform_staff_identities')->where('user_id', $actor->id)->where('active', true);
        if ($write || $locked) {
            $school->lockForUpdate();
            $users->lockForUpdate();
            $memberships->lockForUpdate();
            $support->lockForUpdate();
        }
        abort_unless($school->first()?->status === 'active', 403);
        $fresh = $users->first();
        abort_unless($fresh !== null && ! $fresh->password_change_required && $support->first() === null, 403);
        $roles = $memberships->pluck('role')->all();
        abort_if($roles === [], 403);
        $grants = DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->join('role_has_permissions as rp', 'rp.role_id', '=', 'r.id')->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('mr.school_id', $context->id())->where('r.school_id', $context->id())->where('mr.model_id', $fresh->id)
            ->where('mr.model_type', $fresh->getMorphClass())->whereIn('r.name', $roles)->where('p.name', $permission)->where('p.guard_name', 'web');
        $direct = DB::table('model_has_permissions as mp')->join('permissions as p', 'p.id', '=', 'mp.permission_id')
            ->where('mp.school_id', $context->id())->where('mp.model_id', $fresh->id)->where('mp.model_type', $fresh->getMorphClass())
            ->where('p.name', $permission)->where('p.guard_name', 'web');
        if ($write || $locked) {
            $grants->lockForUpdate();
            $direct->lockForUpdate();
        }
        abort_unless($grants->first() !== null || $direct->first() !== null, 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('fees', locked: true) : $gate->state('fees', locked: $locked)['enabled'], 403);

        return $fresh;
    }
}
