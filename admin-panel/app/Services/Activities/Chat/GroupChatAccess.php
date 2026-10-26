<?php

declare(strict_types=1);

namespace App\Services\Activities\Chat;

use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Activities\ActivityStaff;
use App\Models\Activities\Chat\GroupChatMessage;
use App\Models\Activities\Chat\GroupChatThread;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class GroupChatAccess
{
    public function identity(User $actor, bool $write = false): User
    {
        $school = app(CurrentSchool::class);
        abort_unless($school->isSet() && ! $school->isAcrossSchools() && ! $school->isImpersonating(), 403);
        $school->school()?->refresh();
        abort_unless($school->school()?->isActive(), 403);
        abort_if(PlatformStaffIdentity::where('user_id', $actor->id)->where('active', true)->exists(), 403);
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_if($fresh->password_change_required, 403);
        app(ApprovalService::class)->assertAccess($fresh);
        $modules = app(ModuleGate::class);
        $modules->forget();
        foreach (['groups', 'chat'] as $module) {
            abort_unless($write ? $modules->writable($module) : $modules->allows($module), 403);
        }

        return $fresh;
    }

    public function actor(User $actor, string $persona, bool $write = false): User
    {
        $fresh = $this->identity($actor, $write);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        abort_unless(match ($persona) {
            'staff' => array_intersect($roles, ['Teacher', 'Facilitator']) !== [],
            'parent' => in_array('Parent', $roles, true),
            'student' => in_array('Student', $roles, true),
            default => false,
        }, 403);

        return $fresh;
    }

    /** Purpose-specific IDs: a dual role never borrows its other persona's family. */
    private function learners(User $actor, string $persona): array
    {
        $current = Students::whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))
            ->whereIn('id', Enrolment::where('session_year_id', schoolSetting('session_year'))->onDate(app(CommunityAuthority::class)->today())->select('learner_id'));
        if ($persona === 'student') {
            $current->where('user_id', $actor->id);
        } else {
            $parent = (int) (Parents::where('user_id', $actor->id)->value('id') ?? 0);
            $current->whereIn('id', app(GuardianService::class)->childrenFor((int) $actor->id, $parent)->select('id'));
        }

        return $current->get()->filter(fn (Students $learner) => app(ApprovalService::class)->approved($learner))
            ->map(fn ($learner) => (int) $learner->id)->all();
    }

    public function group(User $actor, int $id, string $persona, bool $write = false): ActivityGroup
    {
        $fresh = $this->actor($actor, $persona, $write);
        $group = ActivityGroup::findOrFail($id);
        $community = app(CommunityAuthority::class);
        $today = $community->today();
        $lineage = $community->lineage($group);
        abort_unless(ActivityGroup::whereIn('id', $lineage)->where('active', true)->count() === count($lineage), 404);
        abort_unless(ActivityGroup::whereKey($id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->exists(), 404);
        if ($write) {
            abort_unless((int) $group->session_year_id === (int) schoolSetting('session_year'), 423);
        }
        if ($persona === 'staff') {
            // No management-permission shortcut: real staff allocations only.
            abort_unless(ActivityStaff::where('user_id', $fresh->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
                ->where(fn ($q) => $q->where('activity_group_id', $id)->orWhere(fn ($q) => $q->whereIn('activity_group_id', $lineage)->where('role', 'admin')->where('cascade', true)))->exists(), 404);
        } else {
            abort_unless(ActivityMembership::where('activity_group_id', $id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
                ->whereIn('learner_id', $this->learners($fresh, $persona))->exists(), 404);
        }

        return $group;
    }

    /** Small explicit candidate set for generic notice SQL; never management scope. */
    public function groupIds(User $actor, string $persona): array
    {
        $fresh = $this->actor($actor, $persona);
        $today = app(CommunityAuthority::class)->today();
        $ids = [];
        if ($persona === 'staff') {
            $assignments = ActivityStaff::where('user_id', $fresh->id)->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select(['activity_group_id', 'role', 'cascade'])->get();
            foreach ($assignments as $assignment) {
                $ids[] = (int) $assignment->activity_group_id;
                if ($assignment->role === 'admin' && $assignment->cascade) {
                    $group = ActivityGroup::find($assignment->activity_group_id);
                    if ($group !== null) {
                        $ids = [...$ids, ...app(CommunityAuthority::class)->descendants($group)];
                    }
                }
            }
        } else {
            $ids = ActivityMembership::whereIn('learner_id', $this->learners($fresh, $persona))->where('active', true)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->pluck('activity_group_id')->map(fn ($id) => (int) $id)->all();
        }
        $allowed = [];
        foreach (array_unique($ids) as $id) {
            try {
                $this->group($fresh, $id, $persona);
                $allowed[] = $id;
            } catch (ModelNotFoundException) {
                continue;
            } catch (HttpExceptionInterface $error) {
                if (! in_array($error->getStatusCode(), [403, 404, 423], true)) {
                    throw $error;
                }
            }
        }
        $this->actor($actor, $persona);

        return $allowed;
    }

    public function available(User $actor, int $group, string $persona): bool
    {
        if (! app(ModuleGate::class)->allows('chat')) {
            return false;
        }
        try {
            $this->group($actor, $group, $persona);

            return $this->audiences($persona, app(GroupChatRules::class)->current()) !== [];
        } catch (ModelNotFoundException) {
            return false;
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404, 423], true)) {
                throw $error;
            }

            return false;
        }
    }

    public function writable(User $actor, int $group, string $persona): bool
    {
        try {
            $this->group($actor, $group, $persona, true);

            return true;
        } catch (ModelNotFoundException) {
            $this->group($actor, $group, $persona);

            return false;
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404, 423], true)) {
                throw $error;
            }
            // A failed read is not a mere readonly capability change.
            $this->group($actor, $group, $persona);

            return false;
        }
    }

    public function audiences(string $persona, array $policy): array
    {
        return match ($persona) {
            'staff' => array_values(array_filter(['learners', 'guardians', 'staff'], fn ($audience) => $this->audienceAllowed($audience, $policy))),
            'parent' => $policy['staff_guardians'] ? ['guardians'] : [],
            'student' => $policy['staff_learners'] ? ['learners'] : [],
            default => [],
        };
    }

    private function audienceAllowed(string $audience, array $policy): bool
    {
        return match ($audience) {
            'learners' => $policy['staff_learners'],
            'guardians' => $policy['staff_guardians'],
            'staff' => $policy['staff_staff'],
            default => false,
        };
    }

    public function threads(User $actor, int $group, string $persona, bool $write = false): Builder
    {
        $this->group($actor, $group, $persona, $write);
        $policy = app(GroupChatRules::class)->current();
        $query = GroupChatThread::where('activity_group_id', $group)->whereNull('redacted_at')
            ->where('last_message_at', '>=', now()->utc()->subDays(app(GroupChatRules::class)->retentionDays()))->whereIn('audience', $this->audiences($persona, $policy));
        if ($persona !== 'staff') {
            $query->where(fn ($q) => $q->where('private', false)->orWhere(fn ($q) => $q->where('owner_id', $actor->id)->where('owner_persona', $persona)));
        }

        return $query;
    }

    public function thread(User $actor, int $group, string $id, string $persona, bool $write = false): GroupChatThread
    {
        return $this->threads($actor, $group, $persona, $write)->whereKey($id)->firstOrFail();
    }

    public function messages(User $actor, int $group, string $thread, string $persona): Builder
    {
        $this->thread($actor, $group, $thread, $persona);
        $query = GroupChatMessage::where('thread_id', $thread)->where('sent_at', '>=', now()->utc()->subDays(app(GroupChatRules::class)->retentionDays()));
        if ($persona !== 'staff') {
            $policy = app(GroupChatRules::class)->current();
            $peers = $policy[$persona === 'parent' ? 'guardian_shared_replies' : 'learner_shared_replies'];
            $query->where(function ($visible) use ($persona, $actor, $peers) {
                $visible->where(fn ($q) => $q->where('private_user_id', $actor->id)->where('sender_persona', $persona)
                    ->orWhere(fn ($q) => $q->where('private_user_id', $actor->id)->where('sender_persona', 'staff')))
                    ->orWhere(fn ($q) => $q->where('visibility', 'thread')->where(fn ($q) => $q->where('sender_persona', 'staff')
                        ->when($peers, fn ($q) => $q->orWhere('sender_persona', $persona))
                        ->orWhere(fn ($q) => $q->where('sender_id', $actor->id)->where('sender_persona', $persona))));
            });
        }

        return $query;
    }
}
