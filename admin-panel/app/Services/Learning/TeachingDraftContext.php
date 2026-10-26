<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Growth\LearnerNote;
use App\Models\Learning\TeachingDraft;
use App\Models\User;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Academics\AcademicNumbers;
use App\Services\Academics\MarkDefinitions;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\PromptRegistry;
use App\Services\Growth\TopicGraph;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TeachingDraftContext
{
    public function minimize(User $actor, TeachingDraft $draft, string $text): string
    {
        $text = app(TutorContext::class)->minimize($actor, $text);
        if ($draft->learner_id !== null) {
            $group = app(TeachingDraftAccess::class)->group($actor, (int) $draft->teaching_group_id);
            $learner = app(TeachingDraftAccess::class)->learner($actor, $group, (int) $draft->learner_id);
            $person = $learner->user;
            foreach ([$person?->first_name, $person?->last_name, $person?->email, $learner->admission_no, $learner->student_number] as $token) {
                if (is_string($token) && mb_strlen(trim($token)) >= 2) {
                    $text = preg_replace('/(?<![\pL\pN])'.preg_quote(trim($token), '/').'(?![\pL\pN])/iu', '[learner]', $text) ?? '';
                }
            }
        }

        return $text;
    }

    public function capture(User $actor, TeachingDraft $draft): array
    {
        $access = app(TeachingDraftAccess::class);
        $group = $access->group($actor, (int) $draft->teaching_group_id);
        $source = $access->source($actor, $group, (int) $draft->content_version_id);
        $prompt = app(PromptRegistry::class)->get('teaching-assistant');
        $manifest = ['source' => ['id' => (int) $source->id, 'sha256' => hash('sha256', $source->markdown)],
            'prompt_sha256' => $prompt['sha256'], 'notes' => [], 'released_marks' => null, 'task' => null, 'insights' => null];
        $context = ['subject_token' => hash_hmac('sha256', 'subject:'.$group->subject_id, config('app.key')),
            'kind' => $draft->kind, 'language' => $draft->request['language'],
            'brief' => $this->minimize($actor, $draft, $draft->request['brief']),
            'approved_content' => $this->minimize($actor, $draft, mb_strcut($source->markdown, 0, 8000, 'UTF-8'))];
        if ($draft->kind === 'report_comment') {
            $access->learner($actor, $group, (int) $draft->learner_id);
            $entry = MarkReleaseEntry::where('learner_id', $draft->learner_id)->whereHas('release', fn ($q) => $q->where('subject_id', $group->subject_id)
                ->where('grade_level_id', $group->grade_level_id)->where('session_year_id', $group->session_year_id))->with('release', 'snapshot')->orderByDesc('id')->first();
            $context['released_marks'] = null;
            if ($entry !== null) {
                $values = ['percentage_bp' => $entry->release->mode === 'running' ? $entry->snapshot->running_bp : $entry->snapshot->percentage_bp,
                    'coverage_bp' => $entry->snapshot->coverage_bp, 'provisional' => $entry->release->mode === 'running'];
                $manifest['released_marks'] = ['entry_id' => (int) $entry->id, 'snapshot_id' => (int) $entry->snapshot->id, 'sha256' => hash('sha256', json_encode($values, JSON_THROW_ON_ERROR))];
                $context['released_marks'] = $values;
            }
            $context['teacher_guidance'] = [];
            $metas = LearnerNote::where('learner_id', $draft->learner_id)->where('subject_id', $group->subject_id)->where('session_year_id', $group->session_year_id)
                ->whereIn('visibility', ['tutor', 'staff'])->whereNull('archived_at')->latest('id')->limit(8)->get(['id', 'revision', 'visibility', 'author_id']);
            foreach ($metas as $meta) {
                try {
                    app(LearnerNotesPolicy::class)->scope($actor, (int) $draft->learner_id, (int) $group->subject_id, (int) $group->session_year_id, $meta->visibility);
                    app(LearnerNotesPolicy::class)->scope(User::findOrFail($meta->author_id), (int) $draft->learner_id, (int) $group->subject_id, (int) $group->session_year_id, $meta->visibility);
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    continue;
                }
                $note = LearnerNote::findOrFail($meta->id);
                $manifest['notes'][] = ['id' => (int) $note->id, 'revision' => (int) $note->revision, 'sha256' => hash('sha256', $note->body)];
                $context['teacher_guidance'][] = $this->minimize($actor, $draft, mb_strcut($note->body, 0, 500, 'UTF-8'));
            }
            abort_unless($entry !== null || $context['teacher_guidance'] !== [], 422, 'Release marks or record authorized guidance before drafting a comment.');
        }
        if (in_array($draft->kind, ['practice', 'follow_up_quiz'], true)) {
            app(TopicGraph::class)->sync((int) $group->subject_id);
            $plans = app(MarkDefinitions::class)->plans($group);
            $topics = app(TopicGraph::class)->nodes((int) $group->subject_id)->whereIn('id', $draft->request['topic_ids'])->filter(fn ($node) => (int) $node->grade_level_id === (int) $group->grade_level_id
                && $plans->contains(fn ($plan) => (int) $plan->curriculum_plan_id === (int) $node->source?->curriculum_plan_id && (int) $plan->term_id === (int) $node->source?->term_id));
            abort_if($topics->count() !== count($draft->request['topic_ids']) || $topics->isEmpty(), 422, 'Use a bounded published topic set.');
            $context['approved_topics'] = $topics->map(fn ($topic) => ['id' => (int) $topic->id, 'name' => $this->minimize($actor, $draft, $topic->name)])->values()->all();
        }
        if ($draft->assessment_task_id !== null) {
            $task = app(MarkDefinitions::class)->task($group, (int) $draft->assessment_task_id);
            $root = app(MarkDefinitions::class)->canonical($task);
            $questions = $root->questions->sortBy('id')->map(fn ($q) => ['question_id' => (int) $q->id, 'number' => (string) $q->number, 'max_mark' => AcademicNumbers::decimal((int) $q->max_mark_units)])->values()->all();
            abort_if($questions === [] || count($questions) > 100, 422, 'Question-level evidence is required.');
            $manifest['task'] = ['id' => (int) $task->id, 'canonical_id' => (int) $root->id, 'questions' => $questions, 'mark_revision' => null];
            $context['questions'] = $questions;
            if ($draft->kind === 'script_marks') {
                $access->learner($actor, $group, (int) $draft->learner_id);
                $mark = AssessmentMark::where('assessment_task_id', $root->id)->where('learner_id', $draft->learner_id)->first();
                $manifest['task']['mark_revision'] = $draft->state === 'applied' ? $draft->source_manifest['task']['mark_revision'] : (int) ($mark?->revision ?? 0);
            }
            if (in_array($draft->kind, ['reteach', 'follow_up_quiz'], true)) {
                $insights = app(TaskInsights::class)->read($actor, (int) $group->id, (int) $task->id);
                abort_unless($insights['summary']['has_question_evidence'], 422, 'Record question evidence before drafting follow-up materials.');
                $manifest['insights'] = $insights['fingerprint'];
                $context['question_evidence'] = $insights['summary']['questions'];
            }
        }
        $text = json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($text) > 28000) {
            throw new AiUnavailable('teaching_context_limit');
        }

        return ['manifest' => $manifest, 'text' => $text];
    }

    public function reauthorize(User $actor, TeachingDraft $draft): void
    {
        $fresh = $this->capture($actor, $draft);
        abort_unless($draft->source_manifest === $fresh['manifest'] && hash_equals(hash('sha256', $draft->context_text), hash('sha256', $fresh['text'])), 409, 'Teaching source evidence changed. Request a new draft.');
    }
}
