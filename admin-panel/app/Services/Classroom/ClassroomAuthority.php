<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\Academics\TeachingGroup;
use App\Models\Classroom\ClassroomEligibility;
use App\Models\Classroom\ClassroomLease;
use App\Models\User;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardOperations;
use Illuminate\Support\Facades\Validator;

class ClassroomAuthority
{
    public function configure(User $actor, int $group, array $input): array
    {
        $data = $this->input($input, ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'],
            'eligible' => ['required', 'boolean'], 'reason' => ['required', 'string', 'max:1000']]);
        $data['revision'] = (int) $data['revision'];
        $data['eligible'] = (bool) $data['eligible'];
        $data['group_id'] = $group;
        $authority = function () use ($actor, $group): void {
            app(ClassroomLocks::class)->staff((int) $actor->id, $group);
            app(ClassroomAccess::class)->manager($actor);
            $row = TeachingGroup::findOrFail($group);
            abort_unless($row->active && (int) $row->session_year_id === (int) schoolSetting('session_year'), 404);
        };

        return app(CardOperations::class)->once('user', (string) $actor->id, 'classroom.eligibility', $data, $authority, function () use ($actor, $group, $data) {
            $row = ClassroomEligibility::where('teaching_group_id', $group)->lockForUpdate()->first();
            abort_unless(($row?->revision ?? 0) === $data['revision'], 409, 'Refresh the Foundation eligibility before changing it.');
            $row ??= new ClassroomEligibility(['teaching_group_id' => $group]);
            $row->fill(['eligible' => $data['eligible'], 'revision' => $data['revision'] + 1, 'reviewed_by' => $actor->id, 'review_reason' => $data['reason']])->save();

            return ['teaching_group_id' => $group, 'eligible' => $row->eligible, 'revision' => $row->revision];
        });
    }

    public function grant(User $actor, array $input): array
    {
        $data = $this->input($input, ['operation_id' => ['required', 'uuid'], 'device_id' => ['required', 'uuid'],
            'teaching_group_id' => ['required', 'integer', 'min:1'], 'minutes' => ['required', 'integer', 'between:1,60']]);
        $data['teaching_group_id'] = (int) $data['teaching_group_id'];
        $data['minutes'] = (int) $data['minutes'];
        $authority = function () use ($actor, $data): void {
            app(ClassroomLocks::class)->staff((int) $actor->id, $data['teaching_group_id'], $data['device_id']);
            app(CardAccess::class)->actor($actor, true);
            app(ClassroomAccess::class)->teacher($actor, $data['teaching_group_id']);
            app(ClassroomAccess::class)->eligible($data['teaching_group_id']);
            app(ClassroomAccess::class)->device($data['device_id']);
        };
        $receipt = app(CardOperations::class)->once('user', (string) $actor->id, 'classroom.lease', $data, $authority, function () use ($actor, $data) {
            $row = ClassroomLease::create(['device_id' => $data['device_id'], 'teacher_user_id' => $actor->id,
                'teaching_group_id' => $data['teaching_group_id'], 'eligibility_revision' => app(ClassroomAccess::class)->eligible($data['teaching_group_id'])->revision,
                'secret_hash' => str_repeat('0', 64), 'expires_at' => now()->utc()->addMinutes($data['minutes'])]);
            $row->update(['secret_hash' => hash('sha256', app(ClassroomSecrets::class)->value('lease', $row->id))]);

            return ['lease_id' => $row->id];
        });
        $row = ClassroomLease::where('teacher_user_id', $actor->id)->findOrFail($receipt['lease_id']);
        app(ClassroomAccess::class)->lease($row);
        abort_unless(hash_equals($row->secret_hash, hash('sha256', app(ClassroomSecrets::class)->value('lease', $row->id))), 409, 'Create a fresh classroom authorization after the school key changes.');

        return ['id' => $row->id, 'device_id' => $row->device_id, 'teaching_group_id' => (int) $row->teaching_group_id,
            'classroom_authorization' => app(ClassroomSecrets::class)->credential('lease', $row->id), 'expires_at' => $row->expires_at->toIso8601String()];
    }

    public function revoke(User $actor, string $id): void
    {
        app(CardAccess::class)->actor($actor, revocation: true);
        $row = ClassroomLease::findOrFail($id);
        if ((int) $row->teacher_user_id !== (int) $actor->id) {
            app(ClassroomAccess::class)->manager($actor, false);
        }
        $row->update(['revoked_at' => now()->utc()]);
    }

    public function input(array $input, array $rules): array
    {
        abort_if(array_diff(array_keys($input), array_keys($rules)) !== [], 422);

        return Validator::make($input, $rules)->validate();
    }
}
