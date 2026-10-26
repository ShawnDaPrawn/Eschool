<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\PeerTutoring\PeerTutoringOperation;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Recognition\RecognitionOperations;
use Illuminate\Support\Facades\DB;

class PeerOperations
{
    /** Lock source authority before the transaction's normal read snapshot. */
    public function lock(User $actor, ?int $group = null): void
    {
        app(RecognitionOperations::class)->lock($actor);
        $school = app(CurrentSchool::class)->id();
        DB::table('school_modules')->where('school_id', $school)->whereIn('module', ['peer_tutoring', 'mastery'])->orderBy('id')->lockForUpdate()->get(['id']);
        if ($group !== null) {
            DB::table('teaching_groups')->where('school_id', $school)->where('id', $group)->lockForUpdate()->get(['id']);
            foreach (['teaching_allocations', 'teaching_group_memberships'] as $table) {
                DB::table($table)->where('school_id', $school)->where('teaching_group_id', $group)->orderBy('id')->lockForUpdate()->get(['id']);
            }
            $ids = DB::table('teaching_group_memberships')->where('school_id', $school)->where('teaching_group_id', $group)->pluck('learner_id');
            DB::table('students')->where('school_id', $school)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
            foreach (['learner_approvals', 'enrolments', 'mastery_records'] as $table) {
                DB::table($table)->where('school_id', $school)->whereIn('learner_id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
            }
        }
    }

    public function once(User $actor, string $kind, array $input, int $group, callable $authorize, callable $write): PeerTutoringSession
    {
        $authorize();
        $hash = hash_hmac('sha256', json_encode([$kind, $input], JSON_THROW_ON_ERROR), (string) config('app.key'));
        $school = app(CurrentSchool::class)->id();

        return DB::transaction(function () use ($actor, $kind, $input, $group, $authorize, $write, $hash, $school) {
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);
            $this->lock($actor, $group);
            $authorize();
            $prior = PeerTutoringOperation::where('actor_id', $actor->id)->where('operation_id', $input['operation_id'])->first();
            if ($prior !== null) {
                abort_unless(hash_equals($prior->request_hash, $hash), 409, 'This operation contains a different peer-session change.');
                $row = PeerTutoringSession::findOrFail($prior->peer_tutoring_session_id);
                app(PeerAccess::class)->staffSession($actor, $row);

                return $row;
            }
            $row = $write();
            $authorize();
            PeerTutoringOperation::create(['actor_id' => $actor->id, 'operation_id' => $input['operation_id'], 'kind' => $kind,
                'request_hash' => $hash, 'peer_tutoring_session_id' => $row->id, 'revision' => $row->revision]);

            return $row;
        }, 3);
    }
}
