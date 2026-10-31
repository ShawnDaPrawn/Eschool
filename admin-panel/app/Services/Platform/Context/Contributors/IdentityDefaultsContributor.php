<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Http\Resources\Platform\ChildResource;
use App\Models\Parents;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\SchoolProfile;
use App\Services\Platform\Tenancy\MemberScope;
use Illuminate\Support\Facades\DB;

/** Identity context, with a constant number of queries across children/schools. */
class IdentityDefaultsContributor implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        return [
            'sign_in_methods' => ['password'],
            'approval' => $scope->user->hasRole('Student') && $scope->user->student !== null
                ? app(ApprovalService::class)->state($scope->user->student) : null,
            'children' => $this->children($scope),
        ];
    }

    private function children(ContextScope $scope): array
    {
        $memberships = app(MembershipService::class)->active($scope->user)
            ->filter(fn (SchoolMembership $membership) => $membership->role === 'Parent');
        if ($memberships->isEmpty()) {
            return [];
        }
        $schools = $memberships->keyBy('school_id');
        $parentIds = Parents::acrossSchools()->where('user_id', $scope->user->id)->select('id');
        $guardianId = $scope->user->id;
        $homeroom = DB::table('student_sessions as ss')
            ->join('class_sections as cs', 'cs.id', '=', 'ss.class_section_id')
            ->join('classes as c', 'c.id', '=', 'cs.class_id')->join('sections as sec', 'sec.id', '=', 'cs.section_id')
            ->join('session_years as sy', 'sy.id', '=', 'ss.session_year_id')
            ->whereColumn('ss.student_id', 'students.id')->whereColumn('ss.school_id', 'students.school_id')
            ->whereColumn('cs.school_id', 'students.school_id')->whereColumn('c.school_id', 'students.school_id')
            ->whereColumn('sec.school_id', 'students.school_id')->whereColumn('sy.school_id', 'students.school_id')
            ->where('sy.default', 1)->whereNull('ss.deleted_at')->selectRaw("CONCAT(c.name, ' ', sec.name)")->limit(1);
        $approved = DB::table('learner_approvals as la')->join('guardian_links as gl', function ($join) {
            $join->on('gl.learner_id', '=', 'la.learner_id')->on('gl.guardian_user_id', '=', 'la.guardian_user_id')->on('gl.school_id', '=', 'la.school_id');
        })->join('users as gu', 'gu.id', '=', 'gl.guardian_user_id')
            ->whereColumn('la.learner_id', 'students.id')->whereColumn('la.school_id', 'students.school_id')
            ->whereNull('la.withdrawn_at')->where('la.consent_version', config('identity.consent_version'))->where('gl.status', 'active')->where('gl.can_approve', true)
            ->whereNotNull('gu.email_verified_at')->where('gu.status', 1)->whereNull('gu.deleted_at')->selectRaw('COUNT(*)');
        $children = Students::acrossSchools()->whereIn('students.school_id', $schools->keys())
            // A leaver is listed until their access ends (Action Plan 1.21).
            ->whereNotExists(fn ($ended) => app(LeaverState::class)->whereAccessEnded($ended))
            ->where(function ($query) use ($guardianId, $parentIds) {
                $query->whereExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')
                    ->whereColumn('guardian_links.learner_id', 'students.id')->whereColumn('guardian_links.school_id', 'students.school_id')
                    ->where('guardian_user_id', $guardianId)->where('status', 'active'))
                    ->orWhere(function ($legacy) use ($guardianId, $parentIds) {
                        $legacy->whereNotExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')
                            ->whereColumn('guardian_links.learner_id', 'students.id')->whereColumn('guardian_links.school_id', 'students.school_id')->where('guardian_user_id', $guardianId))
                            ->where(fn ($parents) => $parents->whereIn('father_id', clone $parentIds)->orWhereIn('mother_id', clone $parentIds)->orWhereIn('guardian_id', clone $parentIds));
                    });
            })
            // IDs are restricted to this guardian's authorised children above;
            // their users can belong to another of this guardian's schools.
            ->with(['user' => fn ($query) => $query->withoutGlobalScope(MemberScope::class)->select(['id', 'first_name', 'last_name', 'image'])])
            ->select(['students.id', 'students.user_id', 'students.school_id', 'students.student_number'])
            ->selectSub($homeroom, 'context_homeroom')->selectSub($approved, 'context_approved')->orderBy('students.id')->get();

        return $children->filter(fn (Students $child) => $child->user !== null)->map(function (Students $child) use ($scope, $schools) {
            $profile = (int) $child->school_id === $scope->school->id ? $scope->profile : SchoolProfile::summary($schools[$child->school_id]->school);

            return new ChildResource($child, $profile);
        })->values()->all();
    }
}
