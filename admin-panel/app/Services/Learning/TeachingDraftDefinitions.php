<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TeachingDraft;
use App\Models\User;
use App\Services\Academics\AcademicNumbers;
use App\Services\Ai\Inference\AiUnavailable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class TeachingDraftDefinitions
{
    private function exact(array $data, array $keys): void
    {
        abort_unless(count($data) === count($keys) && array_diff(array_keys($data), $keys) === [], 422, 'Use the expected draft structure.');
    }

    private function text(mixed $text, int $maximum): string
    {
        abort_unless(is_string($text) && trim($text) !== '' && strlen($text) <= $maximum && mb_check_encoding($text, 'UTF-8')
            && ! str_contains($text, "\0") && ! preg_match('~(?:https?://|mailto:|<script|<iframe)~iu', $text), 422, 'Use bounded plain teaching text.');

        return trim($text);
    }

    public function normalize(User $actor, TeachingDraft $draft, array $definition): array
    {
        $context = app(TeachingDraftContext::class);
        $clean = fn ($text, $maximum) => $context->minimize($actor, $draft, $this->text($text, $maximum));
        if (in_array($draft->kind, ['lesson', 'worksheet', 'reteach'], true)) {
            $this->exact($definition, ['title', 'sections']);
            abort_unless(is_array($definition['sections']) && array_is_list($definition['sections']) && count($definition['sections']) === 3, 422);
            $sections = [];
            foreach (['support', 'core', 'extension'] as $i => $level) {
                $row = $definition['sections'][$i];
                abort_unless(is_array($row), 422);
                $this->exact($row, ['level', 'body']);
                abort_unless($row['level'] === $level, 422, 'Keep each of the three teaching levels.');
                $sections[] = ['level' => $level, 'body' => $clean($row['body'], 6000)];
            }

            return ['title' => $clean($definition['title'], 240), 'sections' => $sections];
        }
        if ($draft->kind === 'report_comment') {
            $this->exact($definition, ['text']);

            return ['text' => $clean($definition['text'], 1600)];
        }
        if (in_array($draft->kind, ['practice', 'follow_up_quiz'], true)) {
            $this->exact($definition, ['title', 'questions']);
            abort_unless(is_array($definition['questions']) && array_is_list($definition['questions']) && count($definition['questions']) >= 1 && count($definition['questions']) <= 5, 422);
            $questions = [];
            foreach ($definition['questions'] as $row) {
                abort_unless(is_array($row), 422);
                $this->exact($row, ['topic_id', 'estimated_seconds', 'definition']);
                abort_unless(in_array($row['topic_id'], $draft->request['topic_ids'], true), 422);
                Validator::make($row, ['topic_id' => ['required', 'integer', 'min:1'], 'estimated_seconds' => ['required', 'integer', 'between:60,300'], 'definition' => ['required', 'array']])->validate();
                $group = app(TeachingDraftAccess::class)->group($actor, (int) $draft->teaching_group_id);
                app(TeachingDraftAccess::class)->topic($group, (int) $row['topic_id']);
                $question = app(PracticeAnswers::class)->definition($row['definition']);
                $question['prompt'] = $clean($question['prompt'], 8000);
                $question['explanation'] = $clean($question['explanation'], 8000);
                foreach ($question['options'] as &$option) {
                    $option['text'] = $clean($option['text'], 2000);
                }
                unset($option);
                $questions[] = ['topic_id' => (int) $row['topic_id'], 'estimated_seconds' => (int) $row['estimated_seconds'], 'definition' => $question];
            }

            return ['title' => $clean($definition['title'], 240), 'questions' => $questions];
        }
        abort_unless($draft->kind === 'script_marks', 422);
        $this->exact($definition, ['question_marks']);
        $questions = $draft->source_manifest['task']['questions'];
        abort_unless(is_array($definition['question_marks']) && array_is_list($definition['question_marks']) && count($definition['question_marks']) === count($questions), 422);
        $bounds = collect($questions)->keyBy('question_id');
        $seen = [];
        $result = [];
        foreach ($definition['question_marks'] as $row) {
            abort_unless(is_array($row), 422);
            $this->exact($row, ['question_id', 'mark', 'confidence_bp', 'reason']);
            Validator::make($row, ['question_id' => ['required', 'integer', 'min:1'], 'mark' => ['present', 'nullable', 'string', 'max:12'], 'confidence_bp' => ['required', 'integer', 'between:0,10000']])->validate();
            $id = (int) $row['question_id'];
            abort_unless($bounds->has($id) && ! in_array($id, $seen, true), 422);
            $seen[] = $id;
            $mark = $row['mark'] === null ? null : AcademicNumbers::decimal(AcademicNumbers::units($row['mark'], 'mark', AcademicNumbers::units($bounds[$id]['max_mark'])));
            $result[] = ['question_id' => $id, 'mark' => $mark, 'confidence_bp' => (int) $row['confidence_bp'], 'reason' => $clean($row['reason'], 1000)];
        }

        return ['question_marks' => $result];
    }

    public function parse(User $actor, TeachingDraft $draft, string $output): array
    {
        try {
            $data = json_decode($output, true, 16, JSON_THROW_ON_ERROR);
            abort_unless(is_array($data), 422);
            $this->exact($data, ['subject_token', 'kind', 'definition']);
            $input = json_decode($draft->context_text, true, 16, JSON_THROW_ON_ERROR);
            abort_unless($data['subject_token'] === $input['subject_token'] && $data['kind'] === $draft->kind && is_array($data['definition']), 422);

            return $this->normalize($actor, $draft, $data['definition']);
        } catch (Throwable $error) {
            if ($error instanceof ValidationException || $error instanceof HttpExceptionInterface || $error instanceof \InvalidArgumentException || $error instanceof \JsonException) {
                throw new AiUnavailable('teaching_output_invalid');
            }
            throw $error;
        }
    }
}
