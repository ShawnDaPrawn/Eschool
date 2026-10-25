<?php

declare(strict_types=1);

namespace App\Services\Activities;

use App\Events\Activities\ActivityGroupChanged;
use App\Models\Activities\ActivityOperation;
use App\Models\Activities\ActivitySession;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ActivityOperations
{
    public function once(User $actor, string $kind, array $data, callable $authority, callable $write): array
    {
        $uuid = Validator::make($data, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $authority, $write, $uuid, $hash, $kind) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authority();
            $old = ActivityOperation::where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'This operation already contains a different activity change.');

                return $old->receipt;
            }
            $result = $write();
            $operation = ActivityOperation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'receipt' => $result]);
            $group = str_starts_with($kind, 'group:') ? ($result['id'] ?? null) : ($result['activity_group_id'] ?? null);
            if (str_starts_with($kind, 'attendance:')) {
                $group = ActivitySession::whereKey((int) substr($kind, strlen('attendance:')))->value('activity_group_id');
            }
            if ($group !== null) {
                Outbox::record(new ActivityGroupChanged(app(CurrentSchool::class)->id(), (int) $group, (int) $operation->id));
            }

            return $result;
        }, 3);
    }
}
