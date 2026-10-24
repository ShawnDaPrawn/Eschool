<?php

declare(strict_types=1);

namespace App\Services\Academics\Transitions;

use App\Models\Academics\AcademicTransition;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\StudentSessions;
use App\Models\User;
use App\Policies\Academics\TransitionPolicy;
use App\Services\Academics\AcademicProjections;
use App\Services\People\EnrolmentService;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\ContextChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransitionFlow
{
    public function preview(User $actor, array $input): array
    {
        return DB::transaction(function () use ($actor, $input) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $context = app(TransitionContext::class)->prepare($actor, $input);

            return app(TransitionContext::class)->preview($context);
        });
    }

    public function commit(User $actor, array $input): array
    {
        $data = app(TransitionInput::class)->normalise($input);
        $operation = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'fingerprint' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']])->validate();
        app(TransitionPolicy::class)->learner($actor, $data['learner_id']);
        $hash = hash('sha256', json_encode([$data, $operation['fingerprint']], JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($actor, $data, $operation, $hash) {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(TransitionPolicy::class)->learner($actor, $data['learner_id']);
            Students::whereKey($data['learner_id'])->lockForUpdate()->firstOrFail();
            $previous = AcademicTransition::where('actor_id', $actor->id)->where('operation_id', $operation['operation_id'])->first();
            if ($previous !== null) {
                abort_unless(hash_equals($previous->request_hash, $hash), 409, __('This operation was already used for another change. Review before retrying.'));

                return $this->data($previous);
            }
            $context = app(TransitionContext::class)->prepare($actor, $data);
            abort_unless(hash_equals($context['fingerprint'], $operation['fingerprint']), 409, __('Enrolments, memberships or grade defaults changed. Review the current state.'));
            $row = AcademicTransition::create(['learner_id' => $data['learner_id'], 'session_year_id' => $data['session_year_id'], 'grade_level_id' => $context['grade'], 'actor_id' => $actor->id,
                'operation_id' => $operation['operation_id'], 'request_hash' => $hash, 'kind' => $data['kind'], 'effective_on' => $data['effective_on'], 'reason' => $data['reason'], 'payload' => $data]);
            $result = match ($data['kind']) {
                'join' => app(JoinLearner::class)->apply($actor, $context, (int) $row->id),
                'leave' => app(LeaveLearner::class)->apply($context),
                'subject_change' => app(ChangeLearnerSubject::class)->apply($actor, $context, (int) $row->id),
            };
            $row->forceFill(['result' => $result])->save();
            foreach (StudentSessions::where('student_id', $data['learner_id'])->pluck('session_year_id')->unique() as $year) {
                app(EnrolmentService::class)->syncProjection($data['learner_id'], (int) $year);
            }
            app(AcademicProjections::class)->refresh();
            app(TransitionPolicies::class)->retainHistory();
            Audit::record('academics.learner_'.$data['kind'], $row, [], ['kind' => $row->kind, 'learner_id' => (int) $row->learner_id, 'effective_on' => $data['effective_on'], 'reason_ciphertext' => $row->getRawOriginal('reason'), 'result_ciphertext' => $row->getRawOriginal('result')]);
            Outbox::record(new ContextChanged(app(CurrentSchool::class)->id(), 'academics.learner_changed'));

            return $this->data($row);
        }, 3);
    }

    public function data(AcademicTransition $row): array
    {
        return ['id' => (int) $row->id, 'learner_id' => (int) $row->learner_id, 'session_year_id' => (int) $row->session_year_id, 'grade_level_id' => $row->grade_level_id === null ? null : (int) $row->grade_level_id,
            'kind' => $row->kind, 'effective_on' => $row->effective_on->toDateString(), 'reason' => $row->reason, 'operation_id' => $row->operation_id, 'result' => $row->result,
            'created_at' => $row->created_at->toISOString()];
    }
}
