<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\ContentVersion;
use App\Models\Growth\LearnerNote;
use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorSession;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\Growth\LearnerNotesPolicy;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Growth\MasteryService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TutorContext
{
    public function minimize(User $actor, string $text): string
    {
        $school = app(CurrentSchool::class)->school();
        $tokens = [$actor->first_name, $actor->last_name, $actor->email, $actor->mobile, $school?->name];
        foreach ($tokens as $token) {
            if (is_string($token) && mb_strlen(trim($token)) >= 3) {
                $text = preg_replace('/(?<![\pL\pN])'.preg_quote(trim($token), '/').'(?![\pL\pN])/iu', '[private]', $text) ?? '';
            }
        }
        $text = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email]', $text) ?? '';
        $text = preg_replace('/(?<!\d)(?:\+?\d[\s().-]?){9,15}(?!\d)/', '[number]', $text) ?? '';

        return $text;
    }

    public function version(User $actor, TutorSession $session, int $id): ContentVersion
    {
        app(TutorAccess::class)->session($actor, $session->id);
        // Filter academic identity/audience before loading encrypted content.
        app(ContentPolicy::class)->readableVersions($actor, (int) $session->learner_id)
            ->whereHas('item', fn ($q) => $q->where('subject_id', $session->subject_id)->where('session_year_id', $session->session_year_id)
                ->whereHas('audienceGroups', fn ($g) => $g->where('teaching_group_id', $session->teaching_group_id)))
            ->select('id')->findOrFail($id);

        return app(ContentPolicy::class)->reader($actor, (int) $session->learner_id, $id);
    }

    private function note(User $actor, TutorSession $session, array $reference): LearnerNote
    {
        $meta = LearnerNote::where('learner_id', $session->learner_id)->where('subject_id', $session->subject_id)
            ->where('session_year_id', $session->session_year_id)->where('visibility', 'tutor')->whereNull('archived_at')
            ->select('id', 'author_id', 'revision')->findOrFail($reference['id']);
        abort_unless((int) $meta->revision === $reference['revision'], 409, 'Tutor context changed. Start a fresh turn.');
        app(LearnerNotesPolicy::class)->scope(User::findOrFail($meta->author_id), (int) $session->learner_id, (int) $session->subject_id, (int) $session->session_year_id, 'tutor');
        app(TutorAccess::class)->session($actor, $session->id);

        return LearnerNote::findOrFail($meta->id);
    }

    public function capture(User $actor, TutorSession $session): array
    {
        app(TutorAccess::class)->session($actor, $session->id);
        $manifest = ['versions' => [], 'notes' => [], 'mastery_marks' => null, 'topic' => null];
        $context = ['curriculum' => [], 'learning_guidance' => [], 'mastery_priorities' => []];
        if ($session->topic_id !== null) {
            $group = app(TutorAccess::class)->group($actor, (int) $session->teaching_group_id);
            $topic = app(TutorTopics::class)->topic($group, (int) $session->topic_id);
            $manifest['topic'] = ['id' => (int) $topic->id, 'source_id' => (int) $topic->curriculum_topic_id, 'sha256' => hash('sha256', $topic->topic_key.'|'.$topic->name)];
            $context['selected_topic'] = $this->minimize($actor, $topic->name);
        }
        foreach ($session->source_version_ids as $id) {
            $version = $this->version($actor, $session, $id);
            $manifest['versions'][] = ['id' => $id, 'sha256' => hash('sha256', $version->markdown)];
            $context['curriculum'][] = $this->minimize($actor, mb_strcut($version->markdown, 0, 3000, 'UTF-8'));
        }
        $metas = LearnerNote::where('learner_id', $session->learner_id)->where('subject_id', $session->subject_id)->where('session_year_id', $session->session_year_id)
            ->where('visibility', 'tutor')->whereNull('archived_at')->orderByDesc('id')->limit(8)->get(['id', 'revision']);
        foreach ($metas as $meta) {
            $ref = ['id' => (int) $meta->id, 'revision' => (int) $meta->revision];
            try {
                $note = $this->note($actor, $session, $ref);
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                continue; // A former author's allocation cannot supply current context.
            }
            $manifest['notes'][] = $ref + ['sha256' => hash('sha256', $note->body)];
            $context['learning_guidance'][] = $this->minimize($actor, mb_strcut($note->body, 0, 300, 'UTF-8'));
        }
        $gate = app(ModuleGate::class);
        $gate->forget();
        if ($gate->allows('mastery')) {
            $view = app(MasteryService::class)->view($actor, (int) $session->learner_id, (int) $session->subject_id);
            $manifest['mastery_marks'] = $view['marks_visible'];
            $context['mastery_priorities'] = array_slice(array_map(fn ($row) => array_intersect_key($row, array_flip(['name', 'level', 'reason'])), $view['priorities']), 0, 3);
        }
        $this->reauthorize($actor, $session, $manifest);
        // Reuse only text turns built from this exact still-authorized context.
        // Image-derived earlier turns require their own renewed crop/source approval.
        $context['recent_dialogue'] = [];
        foreach (TutorMessage::where('tutor_session_id', $session->id)->where('state', 'completed')->whereNull('purged_at')->latest('created_at')->limit(2)->get() as $prior) {
            if ($prior->media_ids === [] && $prior->source_manifest === $manifest) {
                $context['recent_dialogue'][] = ['question' => $this->minimize($actor, mb_strcut($prior->learner_text, 0, 1000, 'UTF-8')),
                    'working' => $this->minimize($actor, mb_strcut((string) $prior->working, 0, 1000, 'UTF-8')),
                    'hint' => $this->minimize($actor, (string) ($prior->response['text'] ?? ''))];
            }
        }

        return ['manifest' => $manifest, 'text' => json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    public function reauthorize(User $actor, TutorSession $session, array $manifest): void
    {
        app(TutorAccess::class)->session($actor, $session->id);
        if (($manifest['topic'] ?? null) !== null) {
            $ref = $manifest['topic'];
            $group = app(TutorAccess::class)->group($actor, (int) $session->teaching_group_id);
            $topic = app(TutorTopics::class)->topic($group, (int) $ref['id']);
            abort_unless((int) $session->topic_id === (int) $topic->id && (int) $topic->curriculum_topic_id === $ref['source_id']
                && hash_equals($ref['sha256'], hash('sha256', $topic->topic_key.'|'.$topic->name)), 409, 'Tutor topic context changed.');
        }
        foreach ($manifest['versions'] as $ref) {
            $version = $this->version($actor, $session, $ref['id']);
            if (! hash_equals($ref['sha256'], hash('sha256', $version->markdown))) {
                throw new AiUnavailable('tutor_source_changed');
            }
        }
        foreach ($manifest['notes'] as $ref) {
            $note = $this->note($actor, $session, $ref);
            if (! hash_equals($ref['sha256'], hash('sha256', $note->body))) {
                throw new AiUnavailable('tutor_source_changed');
            }
        }
        if ($manifest['mastery_marks'] !== null) {
            $gate = app(ModuleGate::class);
            $gate->forget();
            abort_unless($gate->allows('mastery'), 403);
            $view = app(MasteryService::class)->view($actor, (int) $session->learner_id, (int) $session->subject_id);
            abort_unless($view['marks_visible'] === $manifest['mastery_marks'], 403);
        }
    }
}
