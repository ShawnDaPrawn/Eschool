<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Library\LibraryAiDraft;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibrarySource;
use App\Models\Library\LibraryTopic;
use App\Models\Library\LibraryTopicPrerequisite;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Ai\Inference\PlatformInference;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use JsonException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * AI drafts for the platform content library (Action Plan 3.4). Content
 * staff ask for practice questions or a lesson for one library topic, naming
 * the source the drafts are written from and a brief. The request goes
 * through the AI gateway's HQ path (PlatformInference: the platform switch, a
 * qualified provider, moderation of input and output, metering against HQ's
 * own daily caps). Each draft becomes a library item whose version 1 is a
 * draft labelled as an AI draft; it then goes through the same review as any
 * other item, so nothing AI-written is published until a reviewer approves
 * it, and never by the person who asked for it (they are its first editor).
 * The model sees only library data: the framework, subject code, grade, topic,
 * its prerequisites, the source and the brief. Never a school's material or
 * people. While inference is off, a request is refused with a clear message
 * and nothing is written.
 */
class LibraryDrafting
{
    public const FEATURE = 'library_drafting';

    public const PROMPT = 'library-drafting';

    /** What AI may draft: questions in the daily practice format, or a lesson. Past-paper questions and diagnostics stay human work. */
    public const KINDS = ['practice_question' => 'Practice questions', 'lesson' => 'A lesson'];

    public const MAXIMUM = 5;

    /** Why a request could not run, in words for HQ. */
    public const MESSAGES = [
        'inference_disabled' => 'AI drafting is switched off: model calls are off across the platform until a provider is qualified. Write items by hand for now.',
        'provider_not_qualified' => 'AI drafting is switched off: no model provider is qualified yet. Write items by hand for now.',
        'platform_daily_cap' => 'HQ\'s AI drafting budget for today is used up. Try again tomorrow.',
        'actor_daily_cap' => 'Your AI drafting budget for today is used up. Try again tomorrow.',
        'inference_quarantined' => 'Moderation held this request back. Nothing was added to the library.',
        'library_output_invalid' => 'The drafts did not fit the library\'s format, so nothing was added. Try again with a clearer brief.',
        'budget_not_configured' => 'AI drafting has no price or budget set yet, so it cannot run.',
        'pending' => 'This request is still being drafted. Reload the library page in a moment to see its outcome.',
    ];

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'library_topic_id' => ['required', 'integer', 'min:1'], 'library_source_id' => ['required', 'integer', 'min:1'],
            'kind' => ['required', Rule::in(array_keys(self::KINDS))], 'count' => ['required', 'integer', 'between:1,'.self::MAXIMUM],
            'brief' => ['required', 'string', 'min:10', 'max:1500']];
    }

    public static function message(?string $code): string
    {
        return self::MESSAGES[$code] ?? 'AI drafting is not available right now. Nothing was added to the library.';
    }

    /**
     * Whether HQ can ask for drafts now, and today's spend against HQ's cap.
     *
     * @return array{available: bool, message: ?string, spent: int, cap: ?int, currency: string}
     */
    public function status(): array
    {
        $reason = app(PlatformInference::class)->unavailable(self::FEATURE);

        return ['available' => $reason === null, 'message' => $reason === null ? null : self::message($reason)] + app(PlatformInference::class)->today(self::FEATURE);
    }

    public function request(User $actor, array $input): LibraryAiDraft
    {
        $actor = app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $data = Validator::make($input, self::rules())->validate();
        if ($data['kind'] === 'lesson' && (int) $data['count'] !== 1) {
            throw ValidationException::withMessages(['count' => 'Ask for one lesson at a time.']);
        }
        abort_unless(mb_check_encoding($data['brief'], 'UTF-8') && ! str_contains($data['brief'], "\0"), 422);
        $topic = LibraryTopic::query()->with('framework')->find($data['library_topic_id']);
        if ($topic === null) {
            throw ValidationException::withMessages(['library_topic_id' => 'Choose a library topic.']);
        }
        $source = LibrarySource::query()->find($data['library_source_id']);
        if ($source === null) {
            throw ValidationException::withMessages(['library_source_id' => 'Choose the source the drafts are written from.']);
        }
        $request = ['library_topic_id' => (int) $topic->id, 'library_source_id' => (int) $source->id, 'kind' => $data['kind'], 'count' => (int) $data['count'], 'brief' => trim($data['brief'])];
        $hash = hash('sha256', json_encode($request, JSON_THROW_ON_ERROR));
        $old = LibraryAiDraft::query()->where('operation_id', $data['operation_id'])->first();
        if ($old !== null) {
            return $this->replay($old, $actor, $hash);
        }
        $reason = app(PlatformInference::class)->unavailable(self::FEATURE);
        if ($reason !== null) {
            throw ValidationException::withMessages(['drafting' => self::message($reason)]);
        }
        try {
            $draft = DB::transaction(function () use ($actor, $data, $request, $hash) {
                $row = LibraryAiDraft::query()->create(['operation_id' => $data['operation_id'], 'request_hash' => $hash, 'requested_by' => $actor->id, 'state' => 'pending'] + $request);
                Audit::record('library_ai_draft_requested', $row, [], ['topic_id' => $row->library_topic_id, 'source_id' => $row->library_source_id, 'kind' => $row->kind,
                    'count' => $row->count, 'changed_by' => $actor->id]);

                return $row;
            });
        } catch (UniqueConstraintViolationException) {
            // A second submission of the same form raced the first past the lookup: the first one won, so answer as a replay of it.
            return $this->replay(LibraryAiDraft::query()->where('operation_id', $data['operation_id'])->firstOrFail(), $actor, $hash);
        }
        try {
            $result = app(AiGateway::class)->runPlatform($actor, Capability::Complete, new InferenceRequest(self::FEATURE, $draft->operation_id, self::PROMPT,
                $this->input($draft, $topic, $source), ['library-topic:'.$topic->topic_key, 'library-source:'.$source->id]));
            $items = $this->parse($draft, $topic, (string) $result->output);
            DB::transaction(function () use ($actor, $draft, $topic, $source, $items) {
                // Still Content staff, from HQ, for a request still waiting.
                app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
                $fresh = LibraryAiDraft::query()->lockForUpdate()->findOrFail($draft->id);
                abort_unless($fresh->state === 'pending', 409);
                $ids = [];
                foreach ($items as $content) {
                    $item = LibraryItem::query()->create(['library_topic_id' => $topic->id, 'library_source_id' => $source->id, 'kind' => $fresh->kind,
                        'title' => $content['title'], 'revision' => 1, 'created_by' => $actor->id]);
                    $version = LibraryItemVersion::query()->create(['library_item_id' => $item->id, 'number' => 1, 'state' => 'draft', 'content' => $content['content'],
                        'estimated_seconds' => $content['estimated_seconds'], 'created_by' => $actor->id, 'editor_ids' => [(int) $actor->id],
                        'origin' => 'ai_draft', 'library_ai_draft_id' => $fresh->id]);
                    Audit::record('library_item_created', $item, [], ['kind' => $item->kind, 'topic_id' => $topic->id, 'source_id' => $source->id, 'version_id' => $version->id,
                        'origin' => 'ai_draft', 'ai_draft_id' => $fresh->id, 'changed_by' => $actor->id]);
                    $ids[] = (int) $item->id;
                }
                $usage = app(PlatformInference::class)->usage(self::FEATURE, $fresh->operation_id);
                $fresh->update(['state' => 'completed', 'item_ids' => $ids, 'platform_ai_usage_id' => $usage?->id]);
                Audit::record('library_ai_drafted', $fresh, ['state' => 'pending'], ['state' => 'completed', 'item_ids' => $ids, 'changed_by' => $actor->id]);
            });
        } catch (Throwable $error) {
            $code = $error instanceof AiUnavailable ? $error->reason : 'drafting_refused';
            $usage = app(PlatformInference::class)->usage(self::FEATURE, $draft->operation_id);
            $state = $code === 'inference_quarantined' ? 'held' : 'failed';
            $draft->update(['state' => $state, 'failure_code' => $code, 'platform_ai_usage_id' => $usage?->id]);
            Audit::record('library_ai_drafted', $draft, ['state' => 'pending'], ['state' => $state, 'failure_code' => $code, 'changed_by' => $actor->id]);
            if (! $error instanceof AiUnavailable) {
                throw $error;
            }
        }

        return $draft->fresh();
    }

    /**
     * The same form sent again: the first request's outcome (still pending,
     * completed, failed or held), never a second model call. Anyone else, or a
     * different request under the same id, is refused.
     */
    private function replay(LibraryAiDraft $old, User $actor, string $hash): LibraryAiDraft
    {
        abort_unless((int) $old->requested_by === (int) $actor->id && hash_equals($old->request_hash, $hash), 409, 'This request was already used for something else. Reload the page.');

        return $old;
    }

    /** What the model is given: library data and the brief only. */
    public function input(LibraryAiDraft $draft, LibraryTopic $topic, LibrarySource $source): string
    {
        $prerequisites = LibraryTopic::query()->whereIn('id', LibraryTopicPrerequisite::query()->where('library_topic_id', $topic->id)->select('prerequisite_id'))
            ->orderBy('name')->limit(20)->pluck('name')->all();

        return json_encode(['task' => 'library_draft', 'kind' => $draft->kind, 'count' => (int) $draft->count, 'framework' => $topic->framework->code,
            'subject_code' => $topic->subject_code, 'grade' => (string) GradeLevel::query()->whereKey($topic->grade_level_id)->value('name'), 'topic' => $topic->name,
            'prerequisites' => $prerequisites, 'source' => ['title' => $source->title, 'kind' => $source->kind, 'licence' => $source->licence, 'attribution' => $source->attribution],
            'brief' => $draft->brief], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    /**
     * The model's answer as library content, checked exactly as a form a
     * person typed would be (LibraryContent). Anything else is refused whole.
     *
     * @return list<array{title: string, content: array<string, mixed>, estimated_seconds: ?int}>
     */
    public function parse(LibraryAiDraft $draft, LibraryTopic $topic, string $output): array
    {
        try {
            $data = json_decode($output, true, 16, JSON_THROW_ON_ERROR);
            $this->exact($data, ['kind', 'items']);
            abort_unless($data['kind'] === $draft->kind && is_array($data['items']) && array_is_list($data['items']) && count($data['items']) === (int) $draft->count, 422);
            $items = [];
            foreach ($data['items'] as $row) {
                if ($draft->kind === 'lesson') {
                    $this->exact($row, ['title', 'body']);
                    $fields = ['body' => $this->text($row['body'], 20000)];
                } else {
                    $this->exact($row, ['title', 'question_kind', 'prompt', 'explanation', 'options', 'answers', 'estimated_seconds']);
                    abort_unless(is_array($row['options']) && array_is_list($row['options']) && count($row['options']) <= 6
                        && is_array($row['answers']) && array_is_list($row['answers']) && count($row['answers']) >= 1 && count($row['answers']) <= 10
                        && is_int($row['estimated_seconds']), 422);
                    $choice = $row['question_kind'] === 'choice';
                    $options = [];
                    foreach ($row['options'] as $option) {
                        $this->exact($option, ['id', 'text']);
                        // Option codes are lower case in the practice format (a, b, c); a model may write A, B, C.
                        $id = strtolower($this->line($option['id'], 16));
                        abort_if(str_contains($id, ':'), 422);
                        $options[] = $id.': '.$this->line($option['text'], 500);
                    }
                    $fields = ['question_kind' => $row['question_kind'], 'prompt' => $this->text($row['prompt'], 4000), 'explanation' => $this->text($row['explanation'], 4000),
                        'options' => implode("\n", $options), 'answers' => implode("\n", array_map(fn ($answer) => $choice ? strtolower($this->line($answer, 100)) : $this->line($answer, 100), $row['answers'])),
                        'estimated_seconds' => $row['estimated_seconds']];
                }
                $content = app(LibraryContent::class)->normalise($draft->kind, $topic, $fields);
                $items[] = ['title' => $this->line($row['title'], 191, 3), 'content' => $content['content'], 'estimated_seconds' => $content['estimated_seconds']];
            }

            return $items;
        } catch (Throwable $error) {
            if ($error instanceof ValidationException || $error instanceof HttpExceptionInterface || $error instanceof JsonException || $error instanceof \InvalidArgumentException || $error instanceof \TypeError) {
                throw new AiUnavailable('library_output_invalid');
            }
            throw $error;
        }
    }

    /**
     * The latest requests, newest first, for the library page.
     *
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 10): array
    {
        $rows = LibraryAiDraft::query()->with('topic.framework')->orderByDesc('id')->limit($limit)->get();
        $names = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->whereIn('id', $rows->pluck('requested_by')->unique())->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn ($u) => [(int) $u->id => trim($u->first_name.' '.$u->last_name)])->all());

        return $rows->map(fn (LibraryAiDraft $row) => ['id' => (int) $row->id, 'kind' => self::KINDS[$row->kind] ?? $row->kind, 'count' => (int) $row->count,
            'topic' => $row->topic->framework->code.' · '.$row->topic->subject_code.' · '.$row->topic->name, 'state' => $row->state,
            'message' => in_array($row->state, ['failed', 'held'], true) ? self::message($row->failure_code) : null, 'item_ids' => (array) ($row->item_ids ?? []),
            'by' => $names[$row->requested_by] ?? 'Former staff member', 'at' => $row->created_at?->format('Y-m-d H:i')])->all();
    }

    private function exact(mixed $data, array $keys): void
    {
        abort_unless(is_array($data) && count($data) === count($keys) && array_diff(array_keys($data), $keys) === [], 422);
    }

    /** Bounded plain text: no links, no markup, no control bytes. */
    private function text(mixed $text, int $maximum, int $minimum = 1): string
    {
        abort_unless(is_string($text) && mb_check_encoding($text, 'UTF-8') && ! str_contains($text, "\0") && mb_strlen(trim($text)) >= $minimum && mb_strlen($text) <= $maximum
            && ! preg_match('~(?:https?://|www\.|mailto:|<[a-z!/][^<>]*>)~iu', $text), 422);

        return trim($text);
    }

    private function line(mixed $text, int $maximum, int $minimum = 1): string
    {
        $text = $this->text($text, $maximum, $minimum);
        abort_if(preg_match('/\R/u', $text) === 1, 422);

        return $text;
    }
}
