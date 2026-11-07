<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryTopic;
use App\Services\Learning\PracticeAnswers;
use App\Services\Learning\PracticeMedia;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The content of each kind of library item (Action Plan 3.3), checked the same
 * way whoever submits it. Practice and past-paper questions use the daily
 * practice question format (PracticeAnswers), so a published one can be
 * offered to a learner exactly as a school's own approved question is.
 */
class LibraryContent
{
    public const KINDS = ['practice_question' => 'Practice question', 'past_paper_question' => 'Past-paper question', 'lesson' => 'Lesson', 'diagnostic' => 'Diagnostic'];

    /** Past-paper questions need a source that can licence them. */
    public const PAST_PAPER_SOURCES = ['dbe', 'ieb', 'licensed'];

    public const QUESTIONS = ['practice_question', 'past_paper_question'];

    /** The answer types a question may have (Action Plan 3.11), as the forms name them. */
    public const QUESTION_KINDS = ['number' => 'A number', 'choice' => 'Choose an option', 'steps' => 'Worked steps, each marked', 'short_text' => 'A short written answer',
        'diagram_label' => 'Label a diagram', 'audio' => 'A spoken answer'];

    /** The form fields an item's content may arrive in; the kind decides which are required. */
    public static function rules(): array
    {
        return [
            'question_kind' => ['nullable', 'string', 'in:'.implode(',', PracticeAnswers::KINDS)],
            'prompt' => ['nullable', 'string', 'max:4000'],
            'explanation' => ['nullable', 'string', 'max:4000'],
            'options' => ['nullable', 'string', 'max:7000'],
            'answers' => ['nullable', 'string', 'max:4500'],
            'steps' => ['nullable', 'string', 'max:12000'],
            'regions' => ['nullable', 'string', 'max:400'],
            'labels' => ['nullable', 'string', 'max:4000'],
            'mapping' => ['nullable', 'string', 'max:400'],
            'max_seconds' => ['nullable', 'integer', 'between:10,180'],
            'diagram_id' => ['nullable', 'uuid'],
            'diagram' => ['nullable', 'file', 'mimes:jpeg,png', 'extensions:jpg,jpeg,png', 'max:4096'],
            'estimated_seconds' => ['nullable', 'integer', 'between:60,300'],
            'paper_year' => ['nullable', 'integer', 'between:2000,2100'],
            'paper_code' => ['nullable', 'string', 'max:32'],
            'question_number' => ['nullable', 'string', 'max:16'],
            'body' => ['nullable', 'string', 'max:20000'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'item_ids' => ['nullable', 'array', 'max:30'],
            'item_ids.*' => ['integer', 'distinct', 'min:1'],
        ];
    }

    /**
     * @return array{content: array<string, mixed>, estimated_seconds: ?int}
     */
    public function normalise(string $kind, LibraryTopic $topic, array $input): array
    {
        $data = Validator::make($input, self::rules())->validate();
        if (in_array($kind, self::QUESTIONS, true)) {
            Validator::make($data, ['question_kind' => ['required'], 'prompt' => ['required'], 'explanation' => ['required'], 'estimated_seconds' => ['required'],
                'answers' => ['required_if:question_kind,choice,number,short_text'], 'steps' => ['required_if:question_kind,steps'],
                'regions' => ['required_if:question_kind,diagram_label'], 'labels' => ['required_if:question_kind,diagram_label'], 'mapping' => ['required_if:question_kind,diagram_label'],
                'diagram_id' => ['required_if:question_kind,diagram_label'], 'max_seconds' => ['required_if:question_kind,audio']])->validate();
            try {
                // The same question types and checks as a school's own questions (Action Plan 3.11).
                $image = $data['question_kind'] === 'diagram_label'
                    ? app(PracticeMedia::class)->withLibraryDiagram(['kind' => 'diagram_label', 'diagram' => ['id' => $data['diagram_id']]])['diagram'] : null;
                $definition = app(PracticeAnswers::class)->fromForm($data, $image);
            } catch (InvalidArgumentException $error) {
                throw ValidationException::withMessages(['prompt' => $error->getMessage()]);
            }
            $content = ['definition' => $definition];
            if ($kind === 'past_paper_question') {
                Validator::make($data, ['paper_year' => ['required'], 'paper_code' => ['required'], 'question_number' => ['required']])->validate();
                $content['paper'] = ['year' => (int) $data['paper_year'], 'code' => trim((string) $data['paper_code']), 'question' => trim((string) $data['question_number'])];
            }

            return ['content' => $content, 'estimated_seconds' => (int) $data['estimated_seconds']];
        }
        if ($kind === 'lesson') {
            Validator::make($data, ['body' => ['required', 'string', 'min:20']])->validate();

            return ['content' => ['body' => trim((string) $data['body'])], 'estimated_seconds' => null];
        }
        // A diagnostic gathers published practice questions of the same framework and subject.
        Validator::make($data, ['item_ids' => ['required', 'array', 'min:1']])->validate();
        $ids = array_map('intval', $data['item_ids']);
        $found = LibraryItem::query()->whereIn('id', $ids)->where('kind', 'practice_question')->whereNotNull('published_version_id')
            ->whereHas('topic', fn ($q) => $q->where('library_framework_id', $topic->library_framework_id)->where('subject_code', $topic->subject_code))->count();
        if ($found !== count($ids)) {
            throw ValidationException::withMessages(['item_ids' => 'A diagnostic gathers published practice questions of the same framework and subject.']);
        }

        return ['content' => ['instructions' => trim((string) ($data['instructions'] ?? '')), 'item_ids' => $ids], 'estimated_seconds' => null];
    }

    /** The content as the form fields that would produce it, for editing a draft. */
    public function fields(LibraryItemVersion $version): array
    {
        $content = $version->content;
        $definition = $content['definition'] ?? null;
        if (is_array($definition)) {
            return app(PracticeAnswers::class)->toForm($definition) + ['estimated_seconds' => $version->estimated_seconds, 'paper_year' => $content['paper']['year'] ?? null,
                'paper_code' => $content['paper']['code'] ?? null, 'question_number' => $content['paper']['question'] ?? null];
        }

        return ['body' => $content['body'] ?? null, 'instructions' => $content['instructions'] ?? null, 'item_ids' => $content['item_ids'] ?? []];
    }
}
