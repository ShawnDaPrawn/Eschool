<?php

declare(strict_types=1);

namespace App\Services\Consultations;

use App\Models\Consultations\ConfigurationEvent;
use App\Models\Consultations\Consultation;
use App\Models\Consultations\Operation;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Configuration only. Anonymous ballots must never enter actor-linked receipts. */
class ConsultationOperations
{
    private function canonical(array $input): array
    {
        if (! array_is_list($input)) {
            ksort($input);
        }
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = $this->canonical($value);
            }
        }

        return $input;
    }

    public function once(User $actor, string $kind, array $input, callable $authority, callable $work): array
    {
        $uuid = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate()['operation_id'];
        $hash = hash_hmac('sha256', json_encode($this->canonical([$kind, $input]), JSON_THROW_ON_ERROR), (string) config('app.key'));

        return DB::transaction(function () use ($actor, $uuid, $hash, $authority, $work) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authority();
            $old = Operation::where('actor_id', $actor->id)->where('operation_id', $uuid)->first();
            if ($old !== null) {
                abort_unless(hash_equals($old->request_hash, $hash), 409, 'Review the original consultation change before sending different values.');

                return $old->receipt;
            }
            $result = $work();
            Operation::create(['actor_id' => $actor->id, 'operation_id' => $uuid, 'request_hash' => $hash, 'receipt' => $result]);

            return $result;
        }, 3);
    }

    public function event(?User $actor, Consultation $row, string $action, ?string $reason = null, array $questions = []): void
    {
        ConfigurationEvent::create(['consultation_id' => $row->id, 'actor_id' => $actor?->id, 'action' => $action, 'revision' => $row->revision,
            'snapshot' => ['type' => $row->type, 'title' => $row->title, 'instructions' => $row->instructions, 'session_year_id' => (int) $row->session_year_id, 'audience' => $row->audience, 'anonymous' => $row->anonymous,
                'results_visibility' => $row->results_visibility, 'status' => $row->status, 'opens_at' => $row->opens_at->toISOString(),
                'closes_at' => $row->closes_at->toISOString(), 'reason' => $reason, 'questions' => $questions]]);
    }
}
