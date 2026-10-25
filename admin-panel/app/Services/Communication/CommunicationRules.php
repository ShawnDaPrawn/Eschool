<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Academics\AcademicLeadership;
use App\Models\Communication\CommunicationRoleRule;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class CommunicationRules
{
    public function actor(User $actor): User
    {
        abort_unless(app(CurrentSchool::class)->school()?->status === 'active', 404);

        return app(ProfilePolicy::class)->actor($actor);
    }

    public function staff(User $actor): User
    {
        $fresh = $this->actor($actor);
        abort_if(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Student', 'Parent']) === [], 403);

        return $fresh;
    }

    public function authorAvailable(User $actor): bool
    {
        $fresh = User::where('status', 1)->find($actor->id);
        if ($fresh === null || array_diff(app(ProfilePolicy::class)->roles($fresh), ['Student', 'Parent']) === []) {
            return false;
        }
        if (in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true)) {
            return true;
        }

        if (in_array('Facilitator', app(ProfilePolicy::class)->roles($fresh), true)) {
            try {
                $groups = app(CommunityAuthority::class);
                if ($groups->staffAssignments($fresh)->exists()) {
                    return true;
                }
            } catch (HttpExceptionInterface|ModelNotFoundException $error) {
                if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                    throw $error;
                }
            }
        }

        return $this->manages($fresh) || $this->principal($fresh) || $this->heads($fresh) !== [] || $this->effective($fresh)['allow_school'];
    }

    public function manages(User $actor): bool
    {
        return app(ProfilePolicy::class)->permission($this->staff($actor), 'communication-manage');
    }

    public function heads(User $actor): array
    {
        return AcademicLeadership::where('user_id', $actor->id)->where('kind', 'grade_head')
            ->whereDate('starts_on', '<=', app(AcademicAuthority::class)->today())->whereDate('ends_on', '>=', app(AcademicAuthority::class)->today())
            ->pluck('grade_level_id')->filter()->map(fn ($id) => (int) $id)->unique()->all();
    }

    public function principal(User $actor): bool
    {
        return AcademicLeadership::where('user_id', $actor->id)->where('kind', 'principal')->whereNull('grade_level_id')
            ->whereDate('starts_on', '<=', app(AcademicAuthority::class)->today())->whereDate('ends_on', '>=', app(AcademicAuthority::class)->today())->exists();
    }

    public function effective(User $actor): array
    {
        $fresh = $this->staff($actor);
        $roles = Role::whereIn('name', app(ProfilePolicy::class)->roles($fresh))->get();
        $rows = CommunicationRoleRule::whereIn('role_id', $roles->pluck('id'))->get()->keyBy('role_id');
        $broadDefault = $this->manages($fresh) || $this->principal($fresh);
        $allowSchool = false;
        $approval = true;
        $versions = [];
        foreach ($roles as $role) {
            $rule = $rows->get($role->id);
            $allowSchool = $allowSchool || ($rule?->allow_school ?? $broadDefault);
            $approval = $approval && ($rule?->requires_approval ?? ! $broadDefault);
            $versions[] = [(int) $role->id, $rule?->revision ?? 0];
        }
        if (app(CommunityAuthority::class)->facilitatorOnly($fresh)) {
            $allowSchool = false;
        }
        $heads = $this->heads($fresh);

        return ['allow_school' => $allowSchool, 'requires_approval' => $heads === [] && $approval,
            'grade_head_exempt' => $heads !== [], 'head_grades' => $heads, 'versions' => $versions];
    }

    public function listing(User $actor): array
    {
        abort_unless($this->manages($actor), 403);
        $rows = CommunicationRoleRule::whereNotNull('role_id')->get()->keyBy('role_id');

        return Role::orderBy('name')->whereNotIn('name', ['Student', 'Parent'])->get()->map(function ($role) use ($rows) {
            $rule = $rows->get($role->id);
            $defaultWide = $role->permissions()->where('name', 'communication-manage')->exists();

            return ['role_id' => (int) $role->id, 'role' => $role->name, 'allow_school' => $rule?->allow_school ?? $defaultWide,
                'requires_approval' => $rule?->requires_approval ?? ! $defaultWide, 'revision' => $rule?->revision ?? 0];
        })->all();
    }

    public function save(User $actor, int $role, array $data): array
    {
        abort_unless($this->manages($actor), 403);

        return DB::transaction(function () use ($actor, $role, $data) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            abort_unless($this->manages($actor), 403);
            $target = Role::findOrFail($role);
            if (in_array($target->name, ['Student', 'Parent'], true)) {
                throw ValidationException::withMessages(['role_id' => __('Only staff author roles have message rules.')]);
            }
            $row = CommunicationRoleRule::firstOrNew(['role_id' => $role]);
            abort_unless((int) ($row->revision ?? 0) === (int) $data['revision'], 409, __('The role rule changed. Refresh before saving.'));
            $before = ['allow_school' => $row->allow_school, 'requires_approval' => $row->requires_approval];
            $row->fill(['role_label' => $target->name, 'allow_school' => $data['allow_school'], 'requires_approval' => $data['requires_approval'], 'revision' => (int) ($row->revision ?? 0) + 1])->save();
            Audit::record('communication.rule_changed', $row, $before, ['allow_school' => $row->allow_school, 'requires_approval' => $row->requires_approval]);

            return ['role_id' => (int) $target->id, 'role' => $target->name, 'allow_school' => $row->allow_school, 'requires_approval' => $row->requires_approval, 'revision' => $row->revision];
        });
    }
}
