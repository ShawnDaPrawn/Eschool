<?php

declare(strict_types=1);

namespace App\Policies\Money;

use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class ParentFeePolicy
{
    public function authorize(User $actor): User
    {
        $context = app(CurrentSchool::class);
        abort_unless($context->isSet() && ! $context->isAcrossSchools() && ! $context->isImpersonating(), 403);
        abort_unless(DB::table('schools')->where('id', $context->id())->lockForUpdate()->first()?->status === 'active', 403);
        $fresh = User::whereKey($actor->id)->where('status', 1)->lockForUpdate()->first();
        abort_unless($fresh !== null && ! $fresh->password_change_required, 403);
        abort_if(DB::table('platform_staff_identities')->where('user_id', $actor->id)->where('active', true)->lockForUpdate()->first() !== null, 403);
        abort_unless(SchoolMembership::active()->where('school_id', $context->id())->where('user_id', $actor->id)->where('role', 'Parent')->lockForUpdate()->first() !== null, 403);
        $role = DB::table('model_has_roles as mr')->join('roles as r', 'r.id', '=', 'mr.role_id')
            ->where('mr.school_id', $context->id())->where('r.school_id', $context->id())->where('mr.model_id', $actor->id)
            ->where('mr.model_type', $fresh->getMorphClass())->where('r.name', 'Parent')->lockForUpdate()->first();
        abort_unless($role !== null, 403);
        app(ModuleGate::class)->forget();
        abort_unless(app(ModuleGate::class)->state('fees', locked: true)['enabled'], 403);

        return $fresh;
    }

    public function learners(User $actor): array
    {
        $school = app(CurrentSchool::class)->id();

        return GuardianLink::join('students as s', function ($j) {
            $j->on('s.school_id', '=', 'guardian_links.school_id')->on('s.id', '=', 'guardian_links.learner_id');
        })->join('users as u', 'u.id', '=', 's.user_id')->where('s.school_id', $school)->whereNull('s.deleted_at')->whereNull('u.deleted_at')
            ->where('guardian_links.guardian_user_id', $actor->id)->where('guardian_links.status', 'active')->where('guardian_links.can_see_fees', true)
            ->orderBy('guardian_links.learner_id')->lockForUpdate()->pluck('guardian_links.learner_id')->map(fn ($id) => (int) $id)->all();
    }
}
