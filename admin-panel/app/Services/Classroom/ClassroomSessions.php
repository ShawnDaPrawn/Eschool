<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\Cards\Card;
use App\Models\Cards\CardClassroomSession;
use App\Models\Classroom\ClassroomLease;
use App\Models\Students;
use App\Services\Cards\CardOperations;
use App\Services\Cards\CardReaders;
use App\Services\Platform\Tenancy\CurrentSchool;

class ClassroomSessions
{
    public function authorization(string $deviceCredential, string $code): ClassroomLease
    {
        $device = app(CardReaders::class)->authenticate($deviceCredential, 'classroom');
        [$id, $hash] = app(ClassroomSecrets::class)->reference($code, 'NECL1');
        $lease = ClassroomLease::where('device_id', $device->id)->findOrFail($id);
        abort_unless(hash_equals($lease->secret_hash, $hash), 401);
        app(ClassroomAccess::class)->lease($lease);

        return $lease;
    }

    public function select(string $deviceCredential, array $input): array
    {
        $data = app(ClassroomAuthority::class)->input($input, ['operation_id' => ['required', 'uuid'],
            'classroom_authorization' => ['required', 'string', 'max:159'], 'locator' => ['required', 'string', 'max:90']]);
        $lease = $this->authorization($deviceCredential, $data['classroom_authorization']);
        abort_unless(preg_match('/^NELOC1\.([1-9][0-9]*)\.([a-f0-9-]{36})$/D', $data['locator'], $parts) === 1
            && (int) $parts[1] === app(CurrentSchool::class)->id(), 422);
        $cardId = $parts[2];
        $authorize = function () use ($deviceCredential, $data, $cardId, $lease): void {
            app(ClassroomLocks::class)->acquire($lease, $cardId);
            $fresh = $this->authorization($deviceCredential, $data['classroom_authorization']);
            $card = Card::whereKey($cardId)->lockForUpdate()->firstOrFail();
            $learner = app(ClassroomAccess::class)->learner($fresh, $card);
            Students::whereKey($learner->id)->lockForUpdate()->firstOrFail();
            app(ClassroomAccess::class)->learner($fresh, $card->fresh());
        };
        // The operation hash binds the submitted secret, but the receipt holds
        // only a session reference. No Teacher/learner credential is persisted.
        $receipt = app(CardOperations::class)->once('device', $lease->device_id, 'classroom.select', $data, $authorize, function () use ($lease, $cardId) {
            $card = Card::findOrFail($cardId);
            $learner = app(ClassroomAccess::class)->learner($lease, $card);
            CardClassroomSession::where('device_id', $lease->device_id)->whereNull('revoked_at')->update(['revoked_at' => now()->utc()]);
            $row = CardClassroomSession::create(['device_id' => $lease->device_id, 'card_id' => $card->id, 'scan_id' => null,
                'classroom_lease_id' => $lease->id, 'card_revision' => $card->revision,
                'approval_revision' => app(ClassroomAccess::class)->approvalRevision($learner), 'user_id' => $card->user_id,
                'secret_hash' => str_repeat('0', 64), 'expires_at' => now()->utc()->addMinutes(15)->min($lease->expires_at)]);
            $row->update(['secret_hash' => hash('sha256', app(ClassroomSecrets::class)->value('learner', $row->id))]);

            return ['session_id' => $row->id];
        });
        $row = CardClassroomSession::findOrFail($receipt['session_id']);
        $credential = app(ClassroomSecrets::class)->credential('learner', $row->id);
        $this->authenticate($deviceCredential, $credential);

        return $this->data($row) + ['classroom_session' => $credential];
    }

    public function authenticate(string $deviceCredential, string $credential): CardClassroomSession
    {
        $device = app(CardReaders::class)->authenticate($deviceCredential, 'classroom');
        [$id, $hash] = app(ClassroomSecrets::class)->reference($credential, 'NEF1');
        $row = CardClassroomSession::where('device_id', $device->id)->whereNotNull('classroom_lease_id')->findOrFail($id);
        abort_unless(hash_equals($row->secret_hash, $hash), 401);
        abort_unless($row->scan_id === null && $row->revoked_at === null && $row->expires_at->isFuture(), 403);
        $lease = ClassroomLease::where('device_id', $device->id)->findOrFail($row->classroom_lease_id);
        $card = Card::findOrFail($row->card_id);
        abort_unless((int) $card->revision === (int) $row->card_revision && (int) $row->user_id === (int) $card->user_id, 403);
        $learner = app(ClassroomAccess::class)->learner($lease, $card);
        abort_unless(is_string($row->approval_revision) && hash_equals($row->approval_revision, app(ClassroomAccess::class)->approvalRevision($learner)), 403);

        return $row;
    }

    public function data(CardClassroomSession $row): array
    {
        $lease = ClassroomLease::findOrFail($row->classroom_lease_id);
        $learner = app(ClassroomAccess::class)->learner($lease, Card::findOrFail($row->card_id));

        return ['school_id' => app(CurrentSchool::class)->id(), 'learner_id' => (int) $learner->id,
            'name' => (string) $learner->user->first_name, 'teaching_group_id' => (int) $lease->teaching_group_id,
            'expires_at' => $row->expires_at->toIso8601String(), 'restricted' => true, 'capabilities' => ['daily_practice']];
    }

    public function leave(string $deviceCredential, string $credential): void
    {
        $row = $this->authenticate($deviceCredential, $credential);
        $row->update(['revoked_at' => now()->utc()]);
    }
}
