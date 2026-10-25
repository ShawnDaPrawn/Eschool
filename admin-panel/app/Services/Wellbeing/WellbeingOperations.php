<?php

declare(strict_types=1);

namespace App\Services\Wellbeing;

use App\Models\Platform\School;
use App\Models\User;
use App\Models\Wellbeing\Event;
use App\Models\Wellbeing\Operation;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WellbeingOperations
{
    public function digest(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    public function locked(callable $work): mixed
    {
        return DB::transaction(function () use ($work) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();

            return $work();
        }, 3);
    }

    public function once(User $actor, string $kind, array $input, callable $authority, callable $work): array
    {
        $uuid = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = $this->digest(json_encode([$kind, $input], JSON_THROW_ON_ERROR));

        return $this->locked(function () use ($actor, $uuid, $hash, $authority, $work) {
            $authority();
            $old = Operation::where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'Review the saved wellbeing change before submitting different values.');

                return $old->receipt;
            }
            $result = json_decode(json_encode($work(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            Operation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        });
    }

    public function revision(Model $row, array $input): void
    {
        abort_unless((int) ($row->revision ?? 0) === (int) $input['revision'], 409, 'This wellbeing record changed. Refresh and review before saving.');
    }

    public function event(?User $actor, string $event, Model $row): void
    {
        // Never include report tokens, bodies, identity consent or private notes
        // in generic audit/search infrastructure or plaintext event metadata.
        $snapshot = $row->only(['id', 'revision', 'status', 'active', 'user_id', 'starts_on', 'ends_on', 'category_id', 'support_case_id', 'professional_user_id', 'starts_at', 'ends_at']);
        if (in_array($row->getTable(), ['wellbeing_team_assignments', 'support_case_assignments'], true)) {
            $snapshot['reason'] = $row->reason;
        }
        Event::create(['snapshot' => $snapshot, 'actor_id' => $actor?->id, 'event' => $event, 'subject_type' => $row->getTable(), 'subject_id' => $row->id, 'revision' => (int) ($row->revision ?? 1)]);
    }
}
