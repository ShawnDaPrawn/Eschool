<?php

declare(strict_types=1);

namespace App\Services\Discipline;

use App\Models\Discipline\DisciplineOperation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Private bodies never enter the global plaintext API response cache. */
class DisciplineOperations
{
    public function once(User $actor, string $kind, array $input, callable $authority, callable $write, ?callable $replayAuthority = null): array
    {
        app(DisciplineAuthority::class)->enabled(true);
        $schoolId = app(CurrentSchool::class)->id();

        return app(CurrentSchool::class)->run($schoolId, fn () => $this->execute($actor, $kind, $input, $authority, $write, $schoolId, $replayAuthority));
    }

    private function execute(User $actor, string $kind, array $input, callable $authority, callable $write, int $schoolId, ?callable $replayAuthority): array
    {
        $authority();
        $this->context($schoolId);
        $uuid = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        // HMAC prevents a database-only reader guessing short private discipline text.
        $hash = hash_hmac('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $kind, $uuid, $hash, $schoolId, $authority, $write, $replayAuthority) {
            School::query()->active()->lockForUpdate()->findOrFail($schoolId);
            $this->context($schoolId);
            $authority();
            $this->context($schoolId);
            $row = DisciplineOperation::query()->where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($row !== null) {
                abort_unless(hash_equals($row->request_hash, $hash), 409, 'This operation contains a different change. Compare before retrying.');

                $receipt = $row->receipt;
                if ($replayAuthority !== null) {
                    $replayAuthority($receipt);
                    $this->context($schoolId);
                }

                return $receipt;
            }
            $receipt = $write();
            $this->context($schoolId);
            $authority();
            $this->context($schoolId);
            DisciplineOperation::query()->create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'kind' => $kind, 'request_hash' => $hash, 'receipt' => $receipt]);

            return $receipt;
        }, 3);
    }

    private function context(int $schoolId): void
    {
        app(DisciplineAuthority::class)->enabled(true);
        abort_unless(app(CurrentSchool::class)->id() === $schoolId, 403);
    }
}
