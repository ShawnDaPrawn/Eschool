<?php

declare(strict_types=1);

namespace App\Imports\SchoolOnboarding;

use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\Staff;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Identity\StaffInvitations;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Collection;

/**
 * Staff from a sheet: one row per person, with their email address and the
 * role they hold here, Teacher or one of the school's own roles (never Super
 * Admin or a dedicated profile such as Counsellor or Vendor). Each gets an
 * account with no password anyone knows, the role, and a teacher or staff
 * record. When the school chooses, each is emailed a staff invitation to set
 * their own password (StaffInvitations). A person already on the staff here
 * is left alone. An account that already exists elsewhere is given the role
 * here; nothing about it is shown. engineering/specs/people/school-imports.md.
 */
final class StaffImport implements OnboardingImport
{
    public function key(): string
    {
        return 'staff';
    }

    public function permission(): string
    {
        return 'staff-create';
    }

    public function fields(): array
    {
        return [
            'first_name' => ['label' => 'First name', 'required' => true, 'aliases' => ['first name', 'first names', 'firstname', 'name', 'voornaam']],
            'last_name' => ['label' => 'Surname', 'required' => true, 'aliases' => ['surname', 'last name', 'lastname', 'van']],
            'email' => ['label' => 'Email', 'required' => true, 'aliases' => ['email', 'email address', 'e-mail', 'e-pos']],
            'role' => ['label' => 'Role', 'required' => true, 'aliases' => ['role', 'position', 'post', 'pos', 'job title']],
            'mobile' => ['label' => 'Mobile', 'required' => false, 'aliases' => ['mobile', 'cell', 'cellphone', 'phone', 'selfoon']],
            'gender' => ['label' => 'Gender', 'required' => false, 'aliases' => ['gender', 'sex', 'geslag']],
            'qualification' => ['label' => 'Qualification (teachers)', 'required' => false, 'aliases' => ['qualification', 'qualifications', 'kwalifikasie']],
        ];
    }

    public function plan(CsvSheet $sheet, array $mapping, array $options): array
    {
        $actor = auth()->user();
        $roles = $this->roles();
        $school = app(CurrentSchool::class)->id();
        $seen = [];
        $rows = [];
        foreach ($sheet->rows as $source) {
            $value = fn (string $field) => isset($mapping[$field]) ? trim((string) ($source[$mapping[$field]] ?? '')) : '';
            $line = (int) $source[CsvSheet::LINE];
            $notes = [];
            $first = Values::name($value('first_name'));
            $last = Values::name($value('last_name'));
            $email = Values::email($value('email'));
            $role = $roles->first(fn (Role $role) => mb_strtolower((string) $role->name) === mb_strtolower($value('role')));
            $mobile = $value('mobile') !== '' ? Values::mobile($value('mobile')) : null;
            $label = trim($value('first_name').' '.$value('last_name')).($value('role') !== '' ? ' ('.$value('role').')' : '');
            if ($first === null || $last === null) {
                $notes[] = __('The first name and surname must be letters.');
            }
            if ($email === null) {
                $notes[] = __('A valid email address is needed: staff set their password from an emailed link.');
            } elseif (isset($seen[$email])) {
                $notes[] = __('This email address is already on line :line.', ['line' => $seen[$email]]);
            }
            if ($email !== null) {
                $seen[$email] ??= $line;
            }
            if ($role === null) {
                $notes[] = __('No role ":role". Use Teacher or one of the school\'s own roles: :roles.', ['role' => $value('role'), 'roles' => $roles->pluck('name')->implode(', ')]);
            } elseif ($role->name === SchoolRoles::TEACHER && $actor instanceof User && ! $actor->can('teacher-create')) {
                $notes[] = __('You may not add teachers.');
            }
            if ($value('mobile') !== '' && $mobile === null) {
                $notes[] = __('The mobile number is not valid.');
            }
            if (mb_strlen($value('qualification')) > 191) {
                $notes[] = __('The qualification is too long.');
            }

            $row = ['line' => $line, 'label' => $label, 'notes' => $notes, 'status' => 'error'];
            if ($notes === []) {
                $user = User::acrossSchools()->withTrashed()->where('email', $email)->first();
                $here = $user === null ? collect() : SchoolMembership::query()->where('school_id', $school)->where('user_id', $user->id)->pluck('status', 'role');
                if ($user !== null && $here->except(['Student', 'Parent'])->contains(SchoolMembership::ACTIVE)) {
                    $row['status'] = 'exists';
                    $row['notes'][] = __('Already on the staff.');
                } elseif ($user !== null && ($user->trashed() || (int) $user->status !== 1 || $here->contains(SchoolMembership::SUSPENDED))) {
                    $row['notes'][] = __('The account for :email cannot be added. Check it with the person first.', ['email' => $email]);
                } else {
                    $row['status'] = 'create';
                    $row['data'] = [
                        'first_name' => $first, 'last_name' => $last, 'email' => $email, 'mobile' => $mobile,
                        'gender' => Values::gender($value('gender')), 'role_id' => (int) $role->id,
                        'qualification' => $value('qualification') !== '' ? $value('qualification') : null,
                    ];
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }

    public function commit(User $actor, array $rows, array $options): array
    {
        $counts = ['created' => 0, 'invited' => 0];
        $added = [];
        foreach ($rows as $row) {
            if ($row['status'] !== 'create') {
                continue;
            }
            $data = $row['data'];
            $role = Role::query()->findOrFail($data['role_id']);
            abort_if($role->name === SchoolRoles::TEACHER && ! $actor->can('teacher-create'), 403);
            $user = User::acrossSchools()->withTrashed()->firstOrCreate(['email' => $data['email']], [
                'first_name' => $data['first_name'], 'last_name' => $data['last_name'], 'mobile' => $data['mobile'],
                'gender' => $data['gender'] ?? 'Not specified', 'password' => bin2hex(random_bytes(32)), 'status' => 1,
            ]);
            $created = $user->wasRecentlyCreated;
            $user = User::acrossSchools()->withTrashed()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if($user->trashed() || (int) $user->status !== 1, 409, __('The account for :email cannot be added.', ['email' => $data['email']]));
            if ($created) {
                $user->forceFill(['password_change_required' => true])->save();
            }
            $user->unsetRelation('roles')->unsetRelation('permissions')->assignRole($role);
            app(MembershipService::class)->forget($user);
            if ($role->name === SchoolRoles::TEACHER) {
                if (! Teacher::query()->where('user_id', $user->id)->exists()) {
                    SchoolTable::query('teachers')->insert(['user_id' => $user->id, 'qualification' => $data['qualification'] ?? 'Not recorded', 'created_at' => now(), 'updated_at' => now()]);
                }
            } elseif (! Staff::query()->where('user_id', $user->id)->exists()) {
                SchoolTable::query('staffs')->insert(['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            $added[] = $user;
            $counts['created']++;
        }
        if (! empty($options['invite'])) {
            foreach ($added as $user) {
                app(StaffInvitations::class)->issue($user, $actor);
                $counts['invited']++;
            }
        }

        return $counts;
    }

    /** @return Collection<int, Role> Teacher and the school's own roles, never Super Admin. */
    private function roles(): Collection
    {
        return Role::query()->where(fn ($q) => $q->where('custom_role', 1)->orWhere('name', SchoolRoles::TEACHER))
            ->where('name', '!=', SchoolRoles::SUPER_ADMIN)->orderBy('name')->get();
    }
}
