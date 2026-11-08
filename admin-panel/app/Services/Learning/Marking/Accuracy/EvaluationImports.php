<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

use App\Models\Learning\Marking\MarkingEvaluationPaper;
use App\Models\Learning\Marking\MarkingEvaluationScript;
use App\Models\Learning\PastPaperVersion;
use App\Models\User;
use App\Services\Academics\AcademicNumbers;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\PastPaperMemos;
use App\Services\Platform\Audit\Audit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use JsonException;

/**
 * Imports evaluation papers and teacher-marked scripts (Action Plan 3.18),
 * from HQ only, with a dry run that checks everything and writes nothing.
 *
 * A paper (JSON) is a subject, a code, a title, a source and its questions,
 * each with mark points in the structured memo editor's shape; they are
 * validated by the same rules as a school's memo (PastPaperMemos::normalize).
 * Papers do not change once imported: a corrected memo is a new code.
 *
 * Scripts (CSV, one row per answered question, or JSON) are de-identified:
 * only the allowed fields are accepted, and a field that identifies a person
 * or a school refuses the whole file; an e-mail address, identity number or
 * phone number in any text refuses the script. The source's own script
 * reference is kept only as a keyed hash, to refuse duplicates. An import is
 * all or nothing.
 */
class EvaluationImports
{
    public const PAPER_FIELDS = ['subject', 'code', 'title', 'source', 'questions'];

    public const SCRIPT_FIELDS = ['reference', 'marker', 'source', 'total', 'questions'];

    public const ANSWER_FIELDS = ['number', 'answer', 'marks'];

    public const CSV_COLUMNS = ['reference', 'marker', 'source', 'question', 'answer', 'marks', 'question_total'];

    public const CSV_REQUIRED = ['reference', 'marker', 'source', 'question', 'answer', 'marks'];

    private const MAX_ERRORS = 100;

    private const MARKS = '/^\d{1,5}(?:\.\d{1,2})?$/D';

    /** @var list<string> */
    private array $errors = [];

    public static function formRules(string $kind): array
    {
        $kilobytes = (int) ceil((int) config('marking.gate.max_import_bytes', 2097152) / 1024);

        return ($kind === 'paper' ? [] : ['paper_id' => ['required', 'integer', 'min:1'], 'format' => ['required', 'in:csv,json']])
            + ['file' => ['required', 'file', 'max:'.$kilobytes], 'dry_run' => ['sometimes', 'boolean']];
    }

    /** @return array{ok: bool, dry_run: bool, kind: string, errors: list<string>, summary: array<string, mixed>} */
    public function paper(User $actor, string $content, bool $dryRun): array
    {
        $actor = app(AccuracyAccess::class)->hq($actor, AccuracyAccess::EVALUATE);
        $this->errors = [];
        $data = $this->json($content);
        $paper = $data === null ? null : $this->validatePaper($data);
        if ($paper === null && $this->errors === []) {
            $this->error(__('The paper must be one JSON object.'));
        }
        if ($paper === null || $this->errors !== [] || $dryRun) {
            return $this->report('paper', $dryRun, $paper === null ? [] : $this->paperSummary($paper));
        }
        try {
            $row = DB::transaction(function () use ($paper, $actor) {
                $row = MarkingEvaluationPaper::create($paper + ['created_by' => $actor->id]);
                Audit::record('marking_evaluation_paper_imported', $row, [], ['subject' => $row->subject, 'code' => $row->code, 'fingerprint' => $row->fingerprint, 'changed_by' => $actor->id]);

                return $row;
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }
            $this->error(__('A paper with this code already exists for the subject. Papers do not change once imported: use a new code.'));

            return $this->report('paper', false, $this->paperSummary($paper));
        }

        return $this->report('paper', false, $this->paperSummary($paper) + ['id' => $row->id]);
    }

    /** @return array{ok: bool, dry_run: bool, kind: string, errors: list<string>, summary: array<string, mixed>} */
    public function scripts(User $actor, int $paperId, string $format, string $content, bool $dryRun): array
    {
        $actor = app(AccuracyAccess::class)->hq($actor, AccuracyAccess::EVALUATE);
        $paper = MarkingEvaluationPaper::query()->findOrFail($paperId);
        AccuracyAccess::subject($paper->subject);
        $this->errors = [];
        $scripts = match ($format) {
            'csv' => $this->csv($content),
            'json' => $this->jsonScripts($content),
            default => throw ValidationException::withMessages(['format' => __('Choose CSV or JSON.')]),
        };
        $limit = (int) config('marking.gate.max_scripts_per_import', 1000);
        if ($scripts !== null && count($scripts) > $limit) {
            $this->error(__('One import takes at most :limit scripts.', ['limit' => (string) $limit]));
            $scripts = null;
        }
        if ($scripts === [] && $this->errors === []) {
            $this->error(__('The file has no scripts.'));
        }
        if ($scripts === null && $this->errors === []) {
            $this->error(__('The file could not be read.'));
        }
        $rows = $scripts === null ? [] : $this->validateScripts($paper, $scripts);
        $summary = ['paper' => $paper->code, 'scripts' => count($rows)];
        if ($this->errors !== [] || $dryRun || $rows === []) {
            return $this->report('scripts', $dryRun, $summary);
        }
        $batch = (string) Str::uuid();
        try {
            DB::transaction(function () use ($rows, $paper, $actor, $batch) {
                foreach ($rows as $row) {
                    MarkingEvaluationScript::create($row + ['marking_evaluation_paper_id' => $paper->id, 'import_batch' => $batch, 'imported_by' => $actor->id]);
                }
                Audit::record('marking_evaluation_scripts_imported', $paper, [], ['paper' => $paper->code, 'scripts' => count($rows), 'import_batch' => $batch, 'changed_by' => $actor->id]);
            });
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1062) {
                throw $e;
            }
            $this->error(__('Some of these scripts were imported meanwhile. Nothing was imported: check the paper and try again.'));

            return $this->report('scripts', false, $summary);
        }

        return $this->report('scripts', false, $summary + ['import_batch' => $batch]);
    }

    // ---- papers ----

    private function validatePaper(mixed $data): ?array
    {
        if (! is_array($data) || array_is_list($data)) {
            $this->error(__('The paper must be one JSON object.'));

            return null;
        }
        $this->unknownFields($data, self::PAPER_FIELDS, __('The paper'));
        $subject = $data['subject'] ?? null;
        if (! is_string($subject) || ! in_array($subject, AccuracyAccess::subjects(), true)) {
            $this->error(__('Choose a subject the accuracy gate covers: :subjects.', ['subjects' => implode(', ', AccuracyAccess::subjects())]));
        }
        $code = $data['code'] ?? null;
        if (! is_string($code) || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{1,63}$/D', $code)) {
            $this->error(__('The paper code is 2 to 64 letters, numbers, dots, dashes or underscores.'));
        } elseif (is_string($subject) && MarkingEvaluationPaper::query()->where('subject', $subject)->where('code', $code)->exists()) {
            $this->error(__('A paper with this code already exists for the subject. Papers do not change once imported: use a new code.'));
        }
        foreach (['title' => [191, __('The paper\'s title')], 'source' => [500, __('The paper\'s source')]] as $field => [$max, $label]) {
            $value = $data[$field] ?? null;
            if (! is_string($value) || mb_strlen(trim($value)) < 3 || mb_strlen($value) > $max) {
                $this->error(__(':field: use 3 to :max characters.', ['field' => $label, 'max' => (string) $max]));
            } elseif (($kind = IdentifyingData::find($value)) !== null) {
                $this->error(__(':field contains :what. Evaluation data never identifies anyone.', ['field' => $label, 'what' => IdentifyingData::describe($kind)]));
            }
        }
        $questions = $data['questions'] ?? null;
        if (! is_array($questions) || ! array_is_list($questions) || $questions === [] || count($questions) > 20) {
            $this->error(__('The paper has 1 to 20 questions.'));

            return null;
        }
        $definition = [];
        $input = [];
        foreach ($questions as $i => $question) {
            $where = __('Question :position', ['position' => (string) ($i + 1)]);
            if (! is_array($question) || array_is_list($question)) {
                $this->error($where.': '.__('each question is an object with a number, its marks and its mark points.'));

                return null;
            }
            $this->unknownFields($question, ['number', 'max_marks', 'points'], $where);
            $max = $question['max_marks'] ?? null;
            if (! is_string($max) || ! preg_match(self::MARKS, $max) || AcademicNumbers::units($max) < 1) {
                $this->error($where.': '.__('give the question\'s marks as a number, like "3" or "2.5".'));

                return null;
            }
            if (! is_array($question['points'] ?? null) || $question['points'] === []) {
                $this->error($where.': '.__('every question needs its mark points.'));

                return null;
            }
            $definition[] = ['number' => (string) ($question['number'] ?? ''), 'max_units' => AcademicNumbers::units($max)];
            $input[] = ['number' => $question['number'] ?? null, 'points' => $question['points']];
        }
        if ($this->errors !== []) {
            return null;
        }
        if (count(array_unique(array_column($definition, 'number'))) !== count($definition)) {
            $this->error(__('Each question number appears once.'));

            return null;
        }
        $version = new PastPaperVersion;
        $version->definition = ['questions' => $definition];
        try {
            $normalized = app(PastPaperMemos::class)->normalize($version, ['expected_revision' => 0, 'questions' => $input]);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return null;
        }
        $stored = [];
        foreach ($normalized as $i => $question) {
            $stored[] = ['number' => $question['number'], 'max_units' => $definition[$i]['max_units'], 'points' => $question['points']];
        }

        return ['subject' => $subject, 'code' => $code, 'title' => trim((string) $data['title']), 'source' => trim((string) $data['source']), 'questions' => $stored,
            'max_units' => array_sum(array_column($stored, 'max_units')), 'fingerprint' => hash('sha256', json_encode($stored, JSON_THROW_ON_ERROR))];
    }

    private function paperSummary(array $paper): array
    {
        return ['code' => $paper['code'], 'subject' => $paper['subject'], 'questions' => count($paper['questions']),
            'points' => array_sum(array_map(fn ($q) => count($q['points']), $paper['questions'])), 'max_marks' => AcademicNumbers::decimal($paper['max_units'])];
    }

    // ---- scripts: reading the file ----

    /** @return ?list<array{position: int, fields: array<string, mixed>}> */
    private function jsonScripts(string $content): ?array
    {
        $data = $this->json($content);
        if ($data === null) {
            return null;
        }
        if (! is_array($data) || array_is_list($data) || ! is_array($data['scripts'] ?? null) || ! array_is_list($data['scripts'])) {
            $this->error(__('The file must be a JSON object with a list of "scripts".'));

            return null;
        }
        $this->unknownFields($data, ['scripts'], __('The file'));
        $scripts = [];
        foreach ($data['scripts'] as $i => $script) {
            $where = __('Script :position', ['position' => (string) ($i + 1)]);
            if (! is_array($script) || array_is_list($script)) {
                $this->error($where.': '.__('each script is an object.'));

                continue;
            }
            $this->unknownFields($script, self::SCRIPT_FIELDS, $where);
            $answers = [];
            foreach (is_array($script['questions'] ?? null) && array_is_list($script['questions']) ? $script['questions'] : [] as $answer) {
                if (! is_array($answer) || array_is_list($answer)) {
                    $this->error($where.': '.__('each answered question is an object with a number, an answer and marks.'));

                    continue;
                }
                $this->unknownFields($answer, self::ANSWER_FIELDS, $where);
                $answers[] = ['number' => $answer['number'] ?? null, 'answer' => $answer['answer'] ?? null, 'marks' => $answer['marks'] ?? null, 'total' => null];
            }
            $scripts[] = ['position' => $i + 1, 'reference' => $script['reference'] ?? null, 'marker' => $script['marker'] ?? null,
                'source' => $script['source'] ?? null, 'total' => $script['total'] ?? null, 'questions' => $answers];
        }

        return $this->errors === [] ? $scripts : null;
    }

    /** One row per answered question; rows of one script share its reference, marker and source. */
    private function csv(string $content): ?array
    {
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8') || str_contains($content, "\0")) {
            $this->error(__('The file must be UTF-8 text.'));

            return null;
        }
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        $header = fgetcsv($handle, 0, ',', '"', '');
        if (! is_array($header) || $header === [null]) {
            fclose($handle);
            $this->error(__('The file needs a header row: :columns.', ['columns' => implode(', ', self::CSV_REQUIRED)]));

            return null;
        }
        $header = array_map(fn ($h) => Str::lower(trim((string) $h)), $header);
        foreach ($header as $column) {
            if (! in_array($column, self::CSV_COLUMNS, true)) {
                $this->fieldRefused($column, __('The file'));
            }
        }
        foreach (self::CSV_REQUIRED as $column) {
            if (! in_array($column, $header, true)) {
                $this->error(__('The file needs a column ":column".', ['column' => $column]));
            }
        }
        if ($this->errors === [] && count(array_unique($header)) !== count($header)) {
            $this->error(__('Each column appears once.'));
        }
        if ($this->errors !== []) {
            fclose($handle);

            return null;
        }
        $scripts = [];
        $line = 1;
        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;
            if ($row === [null]) {
                continue;
            }
            if (count($row) !== count($header)) {
                $this->error(__('Row :row: it has :count values, but the header has :columns.', ['row' => (string) $line, 'count' => (string) count($row), 'columns' => (string) count($header)]));

                continue;
            }
            $fields = array_combine($header, $row);
            $reference = (string) $fields['reference'];
            $key = 'r'.$reference;
            if (! isset($scripts[$key])) {
                $scripts[$key] = ['position' => count($scripts) + 1, 'reference' => $reference, 'marker' => $fields['marker'], 'source' => $fields['source'], 'total' => null, 'questions' => []];
            } elseif ($scripts[$key]['marker'] !== $fields['marker'] || $scripts[$key]['source'] !== $fields['source']) {
                $this->error(__('Row :row: every row of one script has the same marker and source.', ['row' => (string) $line]));
            }
            $marks = trim((string) $fields['marks']);
            $scripts[$key]['questions'][] = ['number' => $fields['question'], 'answer' => $fields['answer'],
                'marks' => $marks === '' ? [] : array_map('trim', preg_split('/[;|]/', $marks) ?: []), 'total' => ($fields['question_total'] ?? '') === '' ? null : $fields['question_total']];
        }
        fclose($handle);

        return $this->errors === [] ? array_values($scripts) : null;
    }

    // ---- scripts: checking each one ----

    /** @return list<array<string, mixed>> the rows to store, when every script is valid */
    private function validateScripts(MarkingEvaluationPaper $paper, array $scripts): array
    {
        $questions = collect($paper->questions)->keyBy('number')->all();
        $existing = array_flip(MarkingEvaluationScript::query()->where('marking_evaluation_paper_id', $paper->id)->pluck('reference_hash')->all());
        $seen = [];
        $rows = [];
        foreach ($scripts as $script) {
            $where = __('Script :position', ['position' => (string) $script['position']]);
            $before = count($this->errors);
            $reference = $script['reference'];
            if (! is_string($reference) || ! preg_match('/^[A-Za-z0-9][A-Za-z0-9._\/-]{0,39}$/D', $reference)) {
                $this->error($where.': '.__('the reference is the source\'s own script code: 1 to 40 letters, numbers, dots, dashes, slashes or underscores.'));
                $reference = null;
            }
            $marker = $script['marker'];
            if (! is_string($marker) || ! preg_match('/^[\pL\pN][\pL\pN .,\'()_-]{0,59}$/uD', $marker)) {
                $this->error($where.': '.__('the marker is a marker code like "Marker A", at most 60 characters.'));
            }
            $source = $script['source'];
            if (! is_string($source) || mb_strlen(trim($source)) < 3 || mb_strlen($source) > 200) {
                $this->error($where.': '.__('the source is 3 to 200 characters.'));
            }
            foreach ([[$script['reference'], __('the reference')], [$marker, __('the marker')], [$source, __('the source')]] as [$value, $label]) {
                if (is_string($value) && ($kind = IdentifyingData::find($value)) !== null) {
                    $this->error($where.': '.__(':field contains :what. Evaluation scripts never identify anyone.', ['field' => $label, 'what' => IdentifyingData::describe($kind)]));
                }
            }
            $hash = null;
            if ($reference !== null) {
                $hash = $this->referenceHash($paper->id, $reference);
                if (isset($existing[$hash])) {
                    $this->error($where.': '.__('this script was imported before.'));
                } elseif (isset($seen[$hash])) {
                    $this->error($where.': '.__('this script appears twice in the file.'));
                }
                $seen[$hash] = true;
            }
            [$answers, $teacher, $max] = $this->answers($where, $questions, $script['questions']);
            if ($script['total'] !== null && (! is_string($script['total']) || ! preg_match(self::MARKS, $script['total']) || AcademicNumbers::units($script['total']) !== $teacher)) {
                $this->error($where.': '.__('the total does not match the marks per mark point.'));
            }
            if (count($this->errors) === $before && $hash !== null) {
                $rows[] = ['reference_hash' => $hash, 'marker' => trim((string) $marker), 'source' => trim((string) $source), 'questions' => $answers,
                    'teacher_units' => $teacher, 'max_units' => $max];
            }
        }

        return $this->errors === [] ? $rows : [];
    }

    /** @return array{0: list<array{number: string, answer: string, marks: list<int>}>, 1: int, 2: int} */
    private function answers(string $where, array $questions, array $answered): array
    {
        if ($answered === [] || count($answered) > count($questions)) {
            $this->error($where.': '.__('a script answers 1 to :count of the paper\'s questions.', ['count' => (string) count($questions)]));

            return [[], 0, 0];
        }
        $rows = [];
        $teacher = 0;
        $max = 0;
        foreach ($answered as $answer) {
            $number = is_string($answer['number']) || is_int($answer['number']) ? (string) $answer['number'] : '';
            $question = $questions[$number] ?? null;
            $at = $where.', '.__('question :number', ['number' => Str::limit($number, 16)]);
            if ($question === null) {
                $this->error($at.': '.__('the paper has no such question.'));

                continue;
            }
            if (isset($rows[$number])) {
                $this->error($at.': '.__('the question appears twice.'));

                continue;
            }
            $text = $answer['answer'];
            if (! is_string($text)) {
                $this->error($at.': '.__('the answer is text (it may be empty).'));

                continue;
            }
            $text = str_replace("\r\n", "\n", $text);
            try {
                LearnerAnswer::fromText($text);
            } catch (InvalidArgumentException) {
                $this->error($at.': '.__('use at most :lines lines of at most :characters characters each.', ['lines' => (string) LearnerAnswer::MAX_LINES, 'characters' => (string) LearnerAnswer::MAX_LINE]));

                continue;
            }
            if (($kind = IdentifyingData::find($text)) !== null) {
                $this->error($at.': '.__('the answer contains :what. Evaluation scripts never identify anyone.', ['what' => IdentifyingData::describe($kind)]));

                continue;
            }
            $marks = $answer['marks'];
            if (! is_array($marks) || ! array_is_list($marks) || count($marks) !== count($question['points'])) {
                $this->error($at.': '.__('give the teacher\'s mark for each of the :count mark points, in order.', ['count' => (string) count($question['points'])]));

                continue;
            }
            $units = [];
            foreach ($marks as $i => $mark) {
                $mark = is_int($mark) ? (string) $mark : $mark;
                if (! is_string($mark) || ! preg_match(self::MARKS, $mark) || AcademicNumbers::units($mark) > (int) $question['points'][$i]['mark_units']) {
                    $this->error($at.': '.__('mark point :index: give a mark from 0 to :max.', ['index' => (string) ($i + 1), 'max' => AcademicNumbers::decimal((int) $question['points'][$i]['mark_units'])]));
                    $units = null;

                    break;
                }
                $units[] = AcademicNumbers::units($mark);
            }
            if ($units === null) {
                continue;
            }
            if ($answer['total'] !== null && (! is_string($answer['total']) || ! preg_match(self::MARKS, $answer['total']) || AcademicNumbers::units($answer['total']) !== array_sum($units))) {
                $this->error($at.': '.__('the question total does not match the marks per mark point.'));

                continue;
            }
            $rows[$number] = ['number' => $number, 'answer' => $text, 'marks' => $units];
            $teacher += array_sum($units);
            $max += (int) $question['max_units'];
        }

        return [array_values($rows), $teacher, $max];
    }

    /** The source's reference is never stored: only this keyed hash, to refuse a script imported twice. */
    private function referenceHash(int $paperId, string $reference): string
    {
        return hash_hmac('sha256', 'marking-evaluation-script:'.$paperId.':'.$reference, (string) config('app.key'));
    }

    // ---- shared ----

    /** @return ?array<string, mixed> the decoded JSON object, or null with an error recorded */
    private function json(string $content): ?array
    {
        if ($content === '' || strlen($content) > (int) config('marking.gate.max_import_bytes', 2097152)) {
            $this->error(__('The file is empty or too large.'));

            return null;
        }
        try {
            $data = json_decode((string) preg_replace('/^\xEF\xBB\xBF/', '', $content), true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error(__('The file is not valid JSON.'));

            return null;
        }
        // Valid JSON that is not an object (null, a number, a string, a list) is refused here, so nothing downstream sees it.
        if (! is_array($data) || array_is_list($data)) {
            $this->error(__('The file must be one JSON object.'));

            return null;
        }

        return $data;
    }

    private function unknownFields(array $data, array $allowed, string $where): void
    {
        foreach (array_keys($data) as $field) {
            if (! in_array($field, $allowed, true)) {
                $this->fieldRefused((string) $field, $where);
            }
        }
    }

    private function fieldRefused(string $field, string $where): void
    {
        $name = Str::limit($field, 40);
        $this->error(IdentifyingData::identifyingField($field)
            ? $where.': '.__('the field ":field" identifies a person or a school. Evaluation scripts are de-identified: remove it.', ['field' => $name])
            : $where.': '.__('the field ":field" is not part of the format.', ['field' => $name]));
    }

    private function error(string $message): void
    {
        if (count($this->errors) < self::MAX_ERRORS) {
            $this->errors[] = $message;
        } elseif (count($this->errors) === self::MAX_ERRORS) {
            $this->errors[] = __('More problems were found; fix these first.');
        }
    }

    private function report(string $kind, bool $dryRun, array $summary): array
    {
        return ['ok' => $this->errors === [], 'dry_run' => $dryRun, 'kind' => $kind, 'errors' => array_values(array_unique($this->errors)), 'summary' => $summary];
    }
}
