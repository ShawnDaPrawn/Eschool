<?php

declare(strict_types=1);

namespace App\Policies\People;

use App\Models\ClassTeacher;
use App\Models\Parents;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\User;
use App\Services\People\Leavers\LeaverState;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

/** Resolve person/role authority before any field values are loaded. */
class ProfilePolicy
{
    public function actor(User $actor): User
    {
        $fresh = User::query()->where('status', 1)->findOrFail($actor->id);
        abort_unless($this->roles($fresh) !== [], 403);

        return $fresh;
    }

    /**
     * Roles that may reach the school's records today. A leaver's role counts
     * for reading until their leaver_until (Action Plan 1.21), never for a
     * write: a former learner or guardian who is also staff writes as staff.
     */
    public function roles(User $person, ?bool $acting = null): array
    {
        // Acting: the person is the one making the request. Checking someone else's
        // roles (a consenting guardian, a profile's subject) always counts a leaver's.
        $acting ??= app()->bound('request') && (int) request()->user()?->getAuthIdentifier() === (int) $person->id;

        return SchoolMembership::query()->when($acting && LeaverState::writing(), fn ($q) => $q->active(), fn ($q) => $q->reachable())
            ->where('school_id', app(CurrentSchool::class)->id())
            ->where('user_id', $person->id)->pluck('role')->unique()->values()->all();
    }

    /** The profiles a person has here (a leaver's learner profile stays one, for the office to read and edit). */
    public function types(User $person): array
    {
        $types = [];
        foreach ($this->roles($person, false) as $role) {
            $types[] = self::typeForRole($role);
        }

        return array_values(array_unique($types));
    }

    public static function typeForRole(string $role): string
    {
        return match ($role) {
            'Student' => 'learner', 'Parent' => 'parent', 'Teacher' => 'teacher',
            'Facilitator' => 'facilitator', 'Counsellor' => 'counsellor', default => 'staff',
        };
    }

    /** The actor is freshly loaded by resolve/manageDefinitions before this check. */
    public function permission(User $actor, string $permission): bool
    {
        $roles = $this->roles($actor);
        if ($roles === []) {
            return false;
        }
        if ($actor->getDirectPermissions()->contains('name', $permission)) {
            return true;
        }

        return $actor->roles()->whereIn('roles.name', $roles)
            ->whereHas('permissions', fn ($query) => $query->where('name', $permission))->exists();
    }

    /** @return array{actor: User, person: User, role: string, write: bool, sensitive: bool} */
    public function resolve(User $actor, User $person, string $profile): array
    {
        $actor = $this->actor($actor); // Never use roles cached under another school.
        $person = User::query()->where('status', 1)->findOrFail($person->id);
        abort_unless(in_array($profile, $this->types($person), true), 404);
        $learner = null;
        $role = null;
        $write = false;
        $sensitive = false;
        if ($actor->id === $person->id) {
            $role = 'owner';
            $write = true;
            $sensitive = true;
        } elseif ($this->permission($actor, 'profile-person-view') || $this->permission($actor, 'profile-person-edit')) {
            $actorRoles = $this->roles($actor);
            $role = in_array('Super Admin', $actorRoles, true) ? 'school_admin'
                : (in_array('Counsellor', $actorRoles, true) ? 'counsellor'
                    : (in_array('Facilitator', $actorRoles, true) ? 'facilitator' : 'school_admin'));
            $write = $this->permission($actor, 'profile-person-edit');
            $sensitive = $this->permission($actor, 'profile-sensitive-view');
        } elseif ($profile === 'learner') {
            $learner = Students::query()->where('user_id', $person->id)->firstOrFail();
            if (in_array('Parent', $this->roles($actor), true)) {
                $link = GuardianLink::query()->where('learner_id', $learner->id)->where('guardian_user_id', $actor->id)->first();
                $parentId = Parents::query()->where('user_id', $actor->id)->value('id');
                $legacy = $parentId !== null && in_array((int) $parentId, [(int) $learner->father_id, (int) $learner->mother_id, (int) $learner->guardian_id], true);
                if (($link !== null && $link->status === 'active') || ($link === null && $legacy)) {
                    $role = 'guardian';
                    $write = $actor->email_verified_at !== null && ($link?->can_approve ?? true);
                    $sensitive = true;
                }
            }
            if ($role === null && in_array('Teacher', $this->roles($actor), true) && $this->teaches($actor, $learner)) {
                $role = 'teacher';
                $write = true; // Each field must also explicitly permit teachers.
                $sensitive = $this->permission($actor, 'profile-sensitive-view');
            }
        }
        abort_unless($role !== null, 404);
        // A leaver's profile is read-only to the leaver and their guardians.
        if ($write && $profile === 'learner' && in_array($role, ['owner', 'guardian'], true)) {
            $learner ??= Students::query()->where('user_id', $person->id)->first();
            $write = $learner === null || ! app(LeaverState::class)->isLeaver((int) $learner->id);
        }

        return ['actor' => $actor, 'person' => $person, 'role' => $role, 'write' => $write,
            'sensitive' => $sensitive && ! app(CurrentSchool::class)->isImpersonating()];
    }

    public function view(User $actor, User $person, string $profile): bool
    {
        $this->resolve($actor, $person, $profile);

        return true;
    }

    public function update(User $actor, User $person, string $profile): bool
    {
        return $this->resolve($actor, $person, $profile)['write'];
    }

    public function manageDefinitions(User $actor, string $operation): User
    {
        $actor = $this->actor($actor);
        abort_unless($this->permission($actor, 'form-field-'.$operation), 403);

        return $actor;
    }

    public function field(array $context, ProfileFieldDefinition $field, bool $write = false): bool
    {
        // The generic profile reader/editor must not bypass the private health boundary.
        $medical = $field->category?->key === 'health' || in_array($field->key, ['medical_alerts', 'allergies'], true);
        if ($medical && (app(CurrentSchool::class)->isAcrossSchools() || app(CurrentSchool::class)->isImpersonating()
            || PlatformStaffIdentity::where('user_id', $context['actor']->id)->where('active', true)->exists())) {
            return false;
        }
        if ($field->hidden || ! in_array($context['role'], $field->visible_to, true)
            || ($field->sensitive && ! $context['sensitive'])) {
            return false;
        }
        if (! $write) {
            return true;
        }
        if (! $context['write'] || ! in_array($context['role'], $field->editable_by, true) || $field->source === 'guardian.relationship') {
            return false;
        }
        if ($field->profile_type === 'learner' && $context['role'] === 'owner') {
            // A learner must not change the age used by guardian/handle rules.
            if ($field->source === 'user.dob') {
                return false;
            }
            $adult = $context['person']->dob !== null && CarbonImmutable::parse($context['person']->dob)->age >= 18;
            if (! $adult && in_array($field->key, ['identity_number', 'medical_alerts', 'allergies'], true)) {
                return false;
            }
        }

        return true;
    }

    private function teaches(User $actor, Students $learner): bool
    {
        $teacher = Teacher::query()->where('user_id', $actor->id)->first();
        if ($teacher === null) {
            return false;
        }
        $year = (int) schoolSetting('session_year');
        $sessions = StudentSessions::query()->where('student_id', $learner->id)->where('session_year_id', $year)->pluck('class_section_id');
        if ($sessions->isEmpty()) {
            $sessions->push($learner->class_section_id);
        }

        return ClassTeacher::query()->where('class_teacher_id', $teacher->id)->where('session_year_id', $year)->whereIn('class_section_id', $sessions)->exists()
            || SubjectTeacher::query()->where('teacher_id', $teacher->id)->where('session_year_id', $year)->whereIn('class_section_id', $sessions)->exists();
    }
}
