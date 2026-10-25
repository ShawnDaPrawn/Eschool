<?php

declare(strict_types=1);

namespace App\Services\Transfers;

use App\Models\Transfers\TransferOperation;
use App\Models\User;

class TransferOperations
{
    public function hash(string $kind, array $payload): string
    {
        return hash_hmac('sha256', json_encode([$kind, $payload], JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public function replay(User $actor, string $operation, string $hash): ?TransferOperation
    {
        $row = TransferOperation::where('actor_id', $actor->id)->where('operation_id', $operation)->first();
        if ($row !== null) {
            abort_unless(hash_equals($row->request_hash, $hash), 409, 'This operation already contains another change. Compare before retrying.');
        }

        return $row;
    }

    public function remember(User $actor, string $operation, string $hash, string $kind, int $id): void
    {
        TransferOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'request_hash' => $hash, 'kind' => $kind, 'record_id' => $id]);
    }
}
