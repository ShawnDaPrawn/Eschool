<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Ai\AiUsage;
use App\Models\Learning\TeachingDraft;
use App\Models\Learning\TeachingDraftVersion;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Academics\MarkRecording;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class TeachingDrafts
{
    public static function generationRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'content_version_id' => ['required', 'integer', 'min:1'],
            'kind' => ['required', 'in:lesson,worksheet,practice,report_comment,script_marks,reteach,follow_up_quiz'],
            'language' => ['required', 'in:en,af,zu,ar'], 'brief' => ['required', 'string', 'min:3', 'max:1500'],
            'learner_id' => ['present', 'nullable', 'integer', 'min:1'], 'task_id' => ['present', 'nullable', 'integer', 'min:1'],
            'photo_ids' => ['present', 'array', 'max:3'], 'topic_ids' => ['sometimes', 'array', 'max:5'], 'topic_ids.*' => ['required', 'integer', 'min:1', 'distinct'], 'photo_ids.*' => ['required', 'uuid', 'distinct']];
    }

    public static function reviewRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'action' => ['required', 'in:edit,approve,discard,publish_practice,confirm_marks'], 'definition' => ['present', 'nullable', 'array'],
            'confirmed' => ['required', 'boolean']];
    }

    public function generate(User $actor, int $groupId, array $input): array
    {
        $access = app(TeachingDraftAccess::class);
        $group = $access->group($actor, $groupId, true);
        $data = Validator::make($input, self::generationRules())->validate();
        abort_unless(mb_check_encoding($data['brief'], 'UTF-8') && ! str_contains($data['brief'], "\0"), 422);
        $learner = in_array($data['kind'], ['report_comment', 'script_marks'], true);
        $task = in_array($data['kind'], ['script_marks', 'reteach', 'follow_up_quiz'], true);
        abort_unless($learner === ($data['learner_id'] !== null) && $task === ($data['task_id'] !== null)
            && ($data['kind'] === 'script_marks' ? $data['photo_ids'] !== [] : $data['photo_ids'] === []), 422, 'Choose the evidence required by this teaching draft.');
        $data['topic_ids'] = $data['topic_ids'] ?? [];
        abort_unless(in_array($data['kind'], ['practice', 'follow_up_quiz'], true) ? $data['topic_ids'] !== [] : $data['topic_ids'] === [], 422, 'Choose the topic for this practice draft.');
        $request = ['kind' => $data['kind'], 'language' => $data['language'], 'brief' => $data['brief'], 'learner_id' => $data['learner_id'],
            'task_id' => $data['task_id'], 'photo_ids' => $data['photo_ids'], 'topic_ids' => array_map('intval', $data['topic_ids'])];
        $hash = hash_hmac('sha256', json_encode([$groupId, (int) $data['content_version_id'], $request], JSON_THROW_ON_ERROR), config('app.key'));
        $school = app(CurrentSchool::class)->id();
        $attempt = (string) Str::uuid();
        $dispatch = false;
        $draft = DB::transaction(function () use ($actor, $access, $groupId, $data, $request, $hash, $school, $attempt, &$dispatch) {
            School::lockForUpdate()->findOrFail($school);
            $group = $access->group($actor, $groupId, true);
            $access->source($actor, $group, (int) $data['content_version_id']);
            $old = TeachingDraft::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
            if ($old !== null) {
                abort_unless((int) $old->author_id === (int) $actor->id && hash_equals($old->request_hash, $hash), 409);
                $old = $access->draft($actor, $old->id, true);
                app(TeachingDraftContext::class)->reauthorize($actor, $old);
                if ($old->state !== 'pending' || $old->lease_until->isFuture()) {
                    return $old;
                }
                $old->update(['attempt_id' => $attempt, 'lease_until' => now('UTC')->addSeconds(30)]);
                $dispatch = true;

                return $old;
            }
            abort_if(TeachingDraft::where('author_id', $actor->id)->whereNull('purged_at')->where('state', '!=', 'discarded')->count() >= 500, 422, 'Discard unused teaching drafts before requesting more.');
            if ($data['learner_id'] !== null) {
                $access->learner($actor, $group, (int) $data['learner_id']);
            }
            $row = TeachingDraft::create(['id' => (string) Str::uuid(), 'author_id' => $actor->id, 'teaching_group_id' => $groupId,
                'content_version_id' => $data['content_version_id'], 'learner_id' => $data['learner_id'], 'assessment_task_id' => $data['task_id'],
                'operation_id' => $data['operation_id'], 'request_hash' => $hash, 'kind' => $data['kind'], 'request' => $request,
                'attempt_id' => $attempt, 'lease_until' => now('UTC')->addSeconds(30)]);
            $context = app(TeachingDraftContext::class)->capture($actor, $row);
            $row->update(['source_manifest' => $context['manifest'], 'context_text' => $context['text']]);
            if ($row->kind === 'script_marks') {
                app(TeachingDraftPhotos::class)->references($actor, $row);
            }
            $dispatch = true;

            return $row;
        });
        if (! $dispatch) {
            return $this->view($actor, $draft->id);
        }
        try {
            $result = app(AiGateway::class)->runFeature($actor, $draft->kind === 'script_marks' ? Capability::Vision : Capability::Complete,
                new FeatureInferenceRequest('teaching_assistant', $draft->operation_id, ['draft_id' => $draft->id]));
            $usage = AiUsage::where('feature', 'teaching_assistant')->where('user_id', $actor->id)->where('operation_id', $draft->operation_id)->firstOrFail();
            if (! is_string($result->output)) {
                throw new AiUnavailable('teaching_output_invalid');
            }
            $definition = app(TeachingDraftDefinitions::class)->parse($actor, $draft, $result->output);
            DB::transaction(function () use ($actor, $draft, $definition, $usage, $attempt, $school) {
                School::lockForUpdate()->findOrFail($school);
                app(TeachingDraftFeaturePolicy::class)->draft($actor, $draft->id);
                $fresh = TeachingDraft::lockForUpdate()->findOrFail($draft->id);
                $receipt = AiUsage::lockForUpdate()->findOrFail($usage->id);
                abort_unless($fresh->attempt_id === $attempt && $fresh->state === 'pending' && $fresh->purged_at === null
                    && $receipt->state === 'completed' && $receipt->quarantine_purged_at === null, 409);
                TeachingDraftVersion::create(['teaching_draft_id' => $fresh->id, 'author_id' => $actor->id, 'number' => 1, 'definition' => $definition, 'action' => 'generated']);
                $fresh->update(['state' => 'ready', 'revision' => 1, 'ai_usage_id' => $usage->id]);
            });
        } catch (Throwable $error) {
            app(CurrentSchool::class)->run($school, function () use ($actor, $draft, $attempt, $error) {
                $usage = AiUsage::where('feature', 'teaching_assistant')->where('user_id', $actor->id)->where('operation_id', $draft->operation_id)->first();
                $fresh = TeachingDraft::where('attempt_id', $attempt)->find($draft->id);
                if ($fresh !== null && $fresh->state === 'pending' && $fresh->purged_at === null) {
                    $fresh->update(['state' => $usage?->state === 'quarantined' ? 'quarantined' : 'failed', 'ai_usage_id' => $usage?->id,
                        'failure_code' => $error instanceof AiUnavailable ? $error->reason : 'teaching_unavailable']);
                }
            });
            if (! $error instanceof AiUnavailable) {
                throw $error;
            }
        }

        return $this->view($actor, $draft->id);
    }

    public function view(User $actor, string $id): array
    {
        $draft = app(TeachingDraftAccess::class)->draft($actor, $id);
        $usage = AiUsage::where('feature', 'teaching_assistant')->where('user_id', $actor->id)->where('operation_id', $draft->operation_id)->first();
        $withheld = $draft->state === 'quarantined' || $usage?->state === 'quarantined' || $usage?->quarantine_purged_at !== null;
        $result = $this->metadata($draft) + ['request' => null, 'definition' => null, 'question_labels' => []];
        if (! $withheld) {
            app(TeachingDraftContext::class)->reauthorize($actor, $draft);
            $result['request'] = $draft->request;
            $manifest = $draft->source_manifest;
            abort_unless(is_array($manifest), 409);
            $result['question_labels'] = $manifest['task']['questions'] ?? [];
            if (in_array($draft->state, ['ready', 'approved', 'published', 'applied'], true)) {
                abort_unless($usage?->state === 'completed', 409);
                $result['definition'] = TeachingDraftVersion::where('teaching_draft_id', $id)->where('number', $draft->revision)->firstOrFail()->definition;
            }
        }
        app(TeachingDraftAccess::class)->draft($actor, $id);

        return $result;
    }

    public function metadata(TeachingDraft $draft): array
    {
        return ['school_id' => (int) $draft->getRawOriginal('school_id'), 'reader_id' => (int) $draft->author_id, 'id' => $draft->id, 'operation_id' => $draft->operation_id, 'kind' => $draft->kind, 'state' => $draft->state,
            'revision' => (int) $draft->revision, 'group_id' => (int) $draft->teaching_group_id, 'content_version_id' => (int) $draft->content_version_id,
            'learner_id' => $draft->learner_id === null ? null : (int) $draft->learner_id, 'task_id' => $draft->assessment_task_id === null ? null : (int) $draft->assessment_task_id];
    }

    public function change(User $actor, string $id, array $input): array
    {
        $data = Validator::make($input, self::reviewRules())->validate();
        $authorize = function () use ($actor, $id) {
            $draft = app(TeachingDraftAccess::class)->draft($actor, $id, true);
            app(TeachingDraftContext::class)->reauthorize($actor, $draft);
        };

        return app(TeachingDraftOperations::class)->run($actor, $data + ['draft_id' => $id], $authorize, function () use ($actor, $id, $data) {
            $draft = TeachingDraft::lockForUpdate()->findOrFail($id);
            abort_unless((int) $draft->revision === (int) $data['expected_revision'], 409, 'This teaching draft changed. Reload before reviewing it.');
            abort_unless(in_array($draft->state, ['pending', 'failed', 'ready', 'approved'], true), 409);
            if ($data['action'] === 'discard') {
                abort_unless($data['definition'] === null, 422);
                $definition = null;
                $state = 'discarded';
            } else {
                $usage = AiUsage::where('feature', 'teaching_assistant')->where('user_id', $actor->id)->where('operation_id', $draft->operation_id)->firstOrFail();
                abort_unless($usage->state === 'completed' && $usage->quarantine_purged_at === null && in_array($draft->state, ['ready', 'approved'], true), 409);
                $old = TeachingDraftVersion::where('teaching_draft_id', $id)->where('number', $draft->revision)->firstOrFail();
                $definition = app(TeachingDraftDefinitions::class)->normalize($actor, $draft, $data['definition'] ?? $old->definition);
                $state = match ($data['action']) {
                    'edit' => 'ready', 'approve' => 'approved', 'publish_practice' => 'published', 'confirm_marks' => 'applied'
                };
                if (in_array($data['action'], ['publish_practice', 'confirm_marks'], true)) {
                    abort_unless($draft->state === 'approved' && $data['confirmed'] === true && $data['definition'] === null, 422, 'Approve the current proposal before explicitly publishing it.');
                }
                if ($data['action'] === 'publish_practice') {
                    abort_unless(in_array($draft->kind, ['practice', 'follow_up_quiz'], true), 422);
                    foreach ($definition['questions'] as $question) {
                        app(PracticeBank::class)->approve($actor, (int) $draft->teaching_group_id, null, $question + ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
                            'content_version_id' => (int) $draft->content_version_id, 'active' => true]);
                    }
                }
                if ($data['action'] === 'confirm_marks') {
                    abort_unless($draft->kind === 'script_marks', 422);
                    abort_if(collect($definition['question_marks'])->contains(fn ($row) => $row['mark'] === null), 422, 'Review every unknown question before confirming marks.');
                    app(MarkRecording::class)->capture($actor, (int) $draft->teaching_group_id, (int) $draft->assessment_task_id, ['rows' => [[
                        'learner_id' => (int) $draft->learner_id, 'expected_revision' => $draft->source_manifest['task']['mark_revision'],
                        'disposition' => 'recorded', 'mark' => null, 'note' => 'Teacher explicitly confirmed a reviewed script proposal.',
                        'question_marks' => array_map(fn ($row) => ['question_id' => $row['question_id'], 'mark' => $row['mark']], $definition['question_marks']),
                    ]]]);
                }
            }
            $next = (int) $draft->revision + 1;
            TeachingDraftVersion::create(['teaching_draft_id' => $id, 'author_id' => $actor->id, 'number' => $next, 'definition' => $definition, 'action' => $data['action']]);
            $draft->update(['state' => $state, 'revision' => $next]);
            Audit::record('learning.teaching_draft_reviewed', $draft, [], ['action' => $data['action'], 'revision' => $next]);

            return $this->metadata($draft) + ['write_operation_id' => $data['operation_id']];
        });
    }
}
