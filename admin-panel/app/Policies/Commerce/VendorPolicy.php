<?php

declare(strict_types=1);

namespace App\Policies\Commerce;

use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class VendorPolicy
{
    public const MANAGE = 'vendors-manage';

    public function actor(User $actor, bool $write = false): User
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        abort_unless(DB::table('schools')->where('id', $context->id())->lockForUpdate()->first()?->status === 'active', 403);
        $fresh = User::whereKey($actor->id)->where('status', 1)->lockForUpdate()->first();
        abort_unless($fresh !== null && ! $fresh->password_change_required, 403);
        abort_if(DB::table('platform_staff_identities')->where('user_id', $actor->id)->where('active', true)->lockForUpdate()->exists(), 403);
        abort_if(app(WalletPolicy::class)->roles($fresh) === [], 403);
        app(ModuleGate::class)->forget();
        abort_unless($write ? app(ModuleGate::class)->writable('vendors', locked: true) : app(ModuleGate::class)->state('vendors', locked: true)['enabled'], 403);

        return $fresh;
    }

    public function finance(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $school = app(CurrentSchool::class)->id();
        $roles = app(WalletPolicy::class)->roles($fresh);
        abort_if(array_intersect($roles, ['Student', 'Vendor']) !== [], 403);
        $grants = DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')->join('role_has_permissions as rp', 'rp.role_id', '=', 'r.id')->join('permissions as p', 'p.id', '=', 'rp.permission_id')->where('mr.school_id', $school)->where('r.school_id', $school)->where('mr.model_id', $fresh->id)->where('mr.model_type', $fresh->getMorphClass())->whereIn('r.name', $roles)->where('p.name', self::MANAGE)->where('p.guard_name', 'web')->lockForUpdate()->exists();
        $direct = DB::table('model_has_permissions as mp')->join('permissions as p', 'p.id', '=', 'mp.permission_id')->where('mp.school_id', $school)->where('mp.model_id', $fresh->id)->where('mp.model_type', $fresh->getMorphClass())->where('p.name', self::MANAGE)->where('p.guard_name', 'web')->lockForUpdate()->exists();
        abort_unless($grants || $direct, 403);

        return $fresh;
    }
}
