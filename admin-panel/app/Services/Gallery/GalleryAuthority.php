<?php

declare(strict_types=1);

namespace App\Services\Gallery;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Gallery\GalleryPhoto;
use App\Models\Gallery\PhotoTag;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Activities\CommunityAuthority;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GalleryAuthority
{
    public function actor(User $actor, bool $write = false, bool $removal = false): User
    {
        $s = app(CurrentSchool::class);
        abort_unless($s->isSet() && ! $s->isAcrossSchools() && ! $s->isImpersonating() && $s->school()?->refresh()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor, $removal);
        abort_if($fresh->password_change_required, 403);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('gallery') : $gate->allows('gallery'), 403);

        return $fresh;
    }

    public function manage(User $actor, bool $write = false): User
    {
        $fresh = $this->actor($actor, $write);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $academic = app(AcademicAuthority::class);
        abort_unless((array_intersect($roles, ['Admin', 'Super Admin']) !== [] && $academic->manages($fresh)) || $academic->currentLeadership($fresh, 'principal', 0), 403);

        return $fresh;
    }

    /** Staff permissions can never substitute for an actual family relationship. */
    public function family(User $actor, int $learnerId, bool $write = false, bool $approve = false, bool $removal = false): Students
    {
        $fresh = $this->actor($actor, $write, $removal);
        $roles = app(ProfilePolicy::class)->roles($fresh, $removal ? false : null);
        abort_unless(array_intersect($roles, ['Parent', 'Student']) !== [], 403);
        $learner = Students::findOrFail($learnerId);
        $own = ! $approve && in_array('Student', $roles, true) && (int) $learner->user_id === (int) $fresh->id;
        $parent = in_array('Parent', $roles, true) && $fresh->email_verified_at !== null
            && app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id);
        if ($approve) {
            $parent = $parent && GuardianLink::where('learner_id', $learnerId)->where('guardian_user_id', $fresh->id)->where('status', 'active')->where('can_approve', true)->exists();
        }
        abort_unless($own || $parent, 404);
        // A guardian may still remove a leaver's photo tag; nothing else is written for them (1.21).
        if ($write && ! $removal) {
            app(LeaverState::class)->guardFamilyWrite($learner, $fresh);
        }
        if ($own) {
            abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval is required.');
        }
        $current = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $learner->user_id)->where('role', 'Student')->exists();
        if (! $current) {
            // A retained tag is a narrow existing guardian archive. It grants no
            // ended learner membership, group feed, staff access or other files.
            abort_unless($parent && ! $approve && ($removal || PhotoTag::where('learner_id', $learnerId)->where('status', 'approved')->where('retained_on_transfer', true)->exists()), 404);
        }

        return $learner;
    }

    public function candidates(User $actor): Builder
    {
        $fresh = $this->actor($actor);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $parents = in_array('Parent', $roles, true) ? Parents::where('user_id', $fresh->id)->pluck('id')->all() : [];
        $school = app(CurrentSchool::class)->id();

        return Students::where(function ($q) use ($fresh, $roles, $parents, $school): void {
            $q->whereRaw('1 = 0');
            if (in_array('Student', $roles, true)) {
                $q->orWhere('user_id', $fresh->id);
            }
            if (in_array('Parent', $roles, true)) {
                $q->orWhereExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $school)->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $fresh->id)->where('guardian_links.status', 'active'));
                if ($parents !== []) {
                    $q->orWhere(fn ($legacy) => $legacy->where(fn ($p) => $p->whereIn('father_id', $parents)->orWhereIn('mother_id', $parents)->orWhereIn('guardian_id', $parents))
                        ->whereNotExists(fn ($links) => $links->selectRaw('1')->from('guardian_links')->where('guardian_links.school_id', $school)->whereColumn('guardian_links.learner_id', 'students.id')->where('guardian_links.guardian_user_id', $fresh->id)));
                }
            }
        });
    }

    public function group(User $actor, int $id, bool $write = false): ActivityGroup
    {
        $fresh = $this->actor($actor, $write);
        $a = app(CommunityAuthority::class);
        $group = $write ? $a->groupStaff($fresh, $id, true) : $a->read($fresh, $id);
        if (! $write && ! $a->canStaff($fresh, $id)) {
            $owned = false;
            foreach (app(GalleryQueries::class)->children($fresh) as $child) {
                if (! $child['archived'] && in_array($id, $this->eligibleGroups($child['id']), true)) {
                    $owned = true;
                    break;
                }
            }
            abort_unless($owned, 404);
        }
        foreach ($a->lineage($group) as $ancestor) {
            abort_unless(ActivityGroup::whereKey($ancestor)->where('active', true)->exists(), 404);
        }
        abort_unless($group->starts_on->toDateString() <= $a->today() && $group->ends_on->toDateString() >= $a->today(), 404);

        return $group;
    }

    public function eligible(GalleryPhoto $photo, int $learner): void
    {
        $person = Students::findOrFail($learner);
        abort_unless(app(GalleryQueries::class)->currentLearner($person), 404);
        if ($photo->scope === 'family') {
            abort_unless((int) $photo->owner_learner_id === $learner, 404);

            return;
        }
        abort_unless(in_array((int) $photo->activity_group_id, $this->eligibleGroups($learner), true), 404);
    }

    public function eligibleGroups(int $learner): array
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows('groups')) {
            return [];
        }
        $today = app(CommunityAuthority::class)->today();
        $query = ActivityMembership::where('learner_id', $learner)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('activity_group_id');
        $groups = ActivityGroup::whereIn('id', $query)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->orderBy('id')->limit(1001)->get();
        abort_if($groups->count() > 1000, 422);
        $ids = [];
        foreach ($groups as $group) {
            $lineage = app(CommunityAuthority::class)->lineage($group);
            if (ActivityGroup::whereIn('id', $lineage)->where('active', true)->count() === count($lineage)) {
                $ids[] = (int) $group->id;
            }
        }

        return $ids;
    }

    public function approved(PhotoTag $tag): bool
    {
        return $tag->status === 'approved' && ($tag->approval_kind === 'guardian' || ! app(GallerySettings::class)->current()->guardian_required);
    }

    /** Metadata only until a current group or family capability is proven. */
    public function photo(User $actor, int $id): GalleryPhoto
    {
        $fresh = $this->actor($actor);
        $photo = GalleryPhoto::where('active', true)->select(['id', 'scope', 'activity_group_id', 'owner_learner_id'])->findOrFail($id);
        if ($photo->scope === 'group') {
            try {
                $this->group($fresh, (int) $photo->activity_group_id);

                return GalleryPhoto::findOrFail($id);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                // Approved personal history is independent of current group membership.
            }
        }
        foreach (PhotoTag::whereIn('learner_id', $this->candidates($fresh)->select('students.id'))->where('gallery_photo_id', $id)->whereIn('status', ['proposed', 'approved'])->get(['id', 'learner_id', 'status', 'approval_kind', 'retained_on_transfer']) as $tag) {
            try {
                $this->family($fresh, (int) $tag->learner_id);
                if (! $this->approved($tag)) {
                    $this->eligible($photo, (int) $tag->learner_id);
                }

                return GalleryPhoto::findOrFail($id);
            } catch (HttpExceptionInterface|ModelNotFoundException) {
            }
        }
        abort(404);
    }
}
