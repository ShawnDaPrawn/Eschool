<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\TutorCheckpoint;
use App\Models\Learning\TutorSession;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Growth\MasteryRecompute;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/** A deterministic teacher-approved check, independent of model feedback. */
class TutorCheckpoints
{
    private function scope(User $actor, string $sessionId, bool $write): array
    {
        $session = app(TutorAccess::class)->session($actor, $sessionId, $write);
        $group = app(PracticeAccess::class)->learner($actor, (int) $session->learner_id, (int) $session->teaching_group_id, $write);
        abort_if($session->topic_id === null, 422, 'Choose a published topic when starting a checkpoint session.');
        app(TutorTopics::class)->topic($group, (int) $session->topic_id);

        return [$session, $group];
    }

    /** Authorize all metadata/source links before decrypting approved answers. */
    private function definition(User $actor, TutorSession $session, TeachingGroup $group, int $versionId): array
    {
        $meta = PracticeItemVersion::select('id', 'practice_item_id', 'content_version_id', 'revision')->findOrFail($versionId);
        PracticeItem::where('teaching_group_id', $group->id)->where('topic_id', $session->topic_id)->where('active', true)
            ->where('revision', $meta->revision)->findOrFail($meta->practice_item_id);
        abort_unless(in_array((int) $meta->content_version_id, $session->source_version_ids, true), 404);
        app(TutorContext::class)->version($actor, $session, (int) $meta->content_version_id);

        return app(PracticeAnswers::class)->definition(PracticeItemVersion::findOrFail($versionId)->definition);
    }

    private function row(User $actor, string $session, string $id, bool $write = false): array
    {
        [$session, $group] = $this->scope($actor, $session, $write);
        $meta = TutorCheckpoint::select('id', 'tutor_session_id', 'user_id', 'learner_id', 'topic_id', 'practice_item_version_id', 'definition_hash')
            ->where('tutor_session_id', $session->id)->where('user_id', $actor->id)->where('learner_id', $session->learner_id)->findOrFail($id);
        abort_unless((int) $meta->topic_id === (int) $session->topic_id, 404);
        $definition = $this->definition($actor, $session, $group, (int) $meta->practice_item_version_id);
        abort_unless(hash_equals($meta->definition_hash, hash('sha256', json_encode($definition, JSON_THROW_ON_ERROR))), 409, 'The approved checkpoint source changed.');

        return [TutorCheckpoint::findOrFail($id), $session, $group, $definition];
    }

    public function issue(User $actor, string $sessionId, string $operation): array
    {
        Validator::make(['operation_id' => $operation], ['operation_id' => ['required', 'uuid']])->validate();
        $this->scope($actor, $sessionId, true);
        $school = app(CurrentSchool::class)->id();
        $hash = hash('sha256', $sessionId);
        $id = DB::transaction(function () use ($actor, $sessionId, $operation, $hash, $school) {
            School::lockForUpdate()->findOrFail($school);
            [$session, $group] = $this->scope($actor, $sessionId, true);
            $old = TutorCheckpoint::where('operation_id', $operation)->first();
            if ($old !== null) {
                abort_unless((int) $old->user_id === (int) $actor->id && hash_equals($old->request_hash, $hash), 409);
                $this->row($actor, $sessionId, $old->id, true);

                return $old->id;
            }
            abort_if(TutorCheckpoint::where('tutor_session_id', $sessionId)->count() >= 10, 422, 'This session has reached its checkpoint limit.');
            abort_if(TutorCheckpoint::where('user_id', $actor->id)->whereDate('created_at', now()->toDateString())->count() >= 20, 422, 'The daily checkpoint allowance is used up.');
            $items = PracticeItem::where('teaching_group_id', $group->id)->where('topic_id', $session->topic_id)->where('active', true);
            $candidate = PracticeItemVersion::whereIn('id', PracticeItemVersion::selectRaw('MAX(id)')->whereIn('practice_item_id', $items->select('id'))->groupBy('practice_item_id'))
                ->whereIn('content_version_id', $session->source_version_ids)
                ->whereNotIn('id', TutorCheckpoint::where('learner_id', $session->learner_id)->select('practice_item_version_id'))
                ->orderBy('id')->first(['id']);
            abort_if($candidate === null, 422, 'No unused teacher-approved checkpoint is available for this topic and selected material. Continue with your tutor or daily practice.');
            $definition = $this->definition($actor, $session, $group, (int) $candidate->id);
            $row = TutorCheckpoint::create(['id' => (string) Str::uuid(), 'tutor_session_id' => $sessionId, 'user_id' => $actor->id,
                'learner_id' => $session->learner_id, 'practice_item_version_id' => $candidate->id, 'topic_id' => $session->topic_id,
                'operation_id' => $operation, 'request_hash' => $hash, 'definition_hash' => hash('sha256', json_encode($definition, JSON_THROW_ON_ERROR))]);
            $this->row($actor, $sessionId, $row->id, true);

            return $row->id;
        });

        return $this->read($actor, $sessionId, $id);
    }

    public function answer(User $actor, string $sessionId, string $id, string $operation, string $answer): array
    {
        Validator::make(compact('operation', 'answer'), ['operation' => ['required', 'uuid'], 'answer' => ['required', 'string', 'max:100']])->validate();
        $this->row($actor, $sessionId, $id, true);
        $hash = hash_hmac('sha256', json_encode([$id, trim($answer)], JSON_THROW_ON_ERROR), config('app.key'));
        $school = app(CurrentSchool::class)->id();
        DB::transaction(function () use ($actor, $sessionId, $id, $operation, $answer, $hash, $school) {
            School::lockForUpdate()->findOrFail($school);
            [$row, $session, $group, $definition] = $this->row($actor, $sessionId, $id, true);
            if ($row->answered_at !== null) {
                abort_unless($row->answer_operation_id === $operation && hash_equals($row->answer_hash, $hash), 409, 'The recorded checkpoint attempt cannot change.');

                return;
            }
            $collision = TutorCheckpoint::where('user_id', $actor->id)->where('answer_operation_id', $operation)->exists();
            abort_if($collision, 409);
            try {
                $correct = app(PracticeAnswers::class)->correct($definition, trim($answer));
            } catch (InvalidArgumentException $error) {
                throw ValidationException::withMessages(['answer' => $error->getMessage()]);
            }
            $row->update(['answer_operation_id' => $operation, 'answer_hash' => $hash, 'answer' => trim($answer),
                'correct' => $correct, 'answered_at' => now('UTC')]);
            app(MasteryRecompute::class)->tutorCheckpoint($row, $group);
            $this->row($actor, $sessionId, $id, true);
        });

        return $this->read($actor, $sessionId, $id);
    }

    public function read(User $actor, string $sessionId, string $id): array
    {
        [$row, $session, , $definition] = $this->row($actor, $sessionId, $id);
        $completed = $row->answered_at !== null;
        $data = ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id,
            'id' => $row->id, 'session_id' => $sessionId, 'learner_id' => (int) $session->learner_id, 'topic_id' => (int) $row->topic_id,
            'operation_id' => $row->operation_id, 'answer_operation_id' => $row->answer_operation_id, 'state' => $completed ? 'answered' : 'ready',
            'question' => array_intersect_key($definition, array_flip(['kind', 'prompt', 'options'])),
            'correct' => $completed ? (bool) $row->correct : null, 'explanation' => $completed ? $definition['explanation'] : null,
            'answered_at' => $row->answered_at?->toIso8601String(), 'verified_by' => $completed ? 'approved_answer_definition' : null];
        $this->scope($actor, $sessionId, false);

        return $data;
    }

    public function summaries(User $actor, TutorSession $session): array
    {
        if ($session->topic_id === null || ! app(ModuleGate::class)->allows('daily_practice')) {
            return [];
        }
        $this->scope($actor, $session->id, false);

        return TutorCheckpoint::where('tutor_session_id', $session->id)->where('user_id', $actor->id)->orderBy('created_at')->limit(10)->get(['id', 'answered_at', 'operation_id'])
            ->map(fn ($row) => ['id' => $row->id, 'operation_id' => $row->operation_id, 'state' => $row->answered_at === null ? 'ready' : 'answered'])->all();
    }
}
