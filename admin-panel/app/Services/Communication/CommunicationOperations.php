<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Communication\CommunicationOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommunicationOperations
{
    public function once(User $actor, string $kind, array $data, callable $authority, callable $write): array
    {
        $uuid = Validator::make($data, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash('sha256', json_encode([$kind, $data], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $kind, $uuid, $hash, $authority, $write) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authority();
            $previous = CommunicationOperation::where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($previous !== null) {
                abort_unless(hash_equals($previous->request_hash, $hash), 409, __('This operation already contains a different message change. Review before retrying.'));

                return $previous->receipt;
            }
            $receipt = $write();
            CommunicationOperation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $receipt]);

            return $receipt;
        }, 3);
    }
}
