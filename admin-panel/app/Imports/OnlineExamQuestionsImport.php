<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\OnlineExamQuestion;
use App\Models\OnlineExamQuestionAnswer;
use App\Models\OnlineExamQuestionOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithReadFilter;
use Maatwebsite\Excel\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use Throwable;

class OnlineExamQuestionsImport extends DefaultValueBinder implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithReadFilter
{
    // Maximum number of option columns supported in the CSV (option1..option6).
    private const MAX_OPTIONS = 6;

    /** The CSV's heading row, in the order the downloadable sample file lists it. */
    public const COLUMNS = [
        'question_type', 'question',
        'option1', 'option2', 'option3', 'option4', 'option5', 'option6',
        'correct_options', 'note',
    ];

    public $class_subject_id;

    public $session_year_id;

    public function __construct($class_subject_id, $session_year_id)
    {
        $this->class_subject_id = $class_subject_id;
        $this->session_year_id = $session_year_id;
    }

    /** Refuses a file over RowLimit::MAX_ROWS rows while it is read. */
    public function readFilter(): IReadFilter
    {
        return new RowLimit;
    }

    public function collection(Collection $rows): void
    {
        DB::beginTransaction();
        try {
            // WithHeadingRow consumes the header line, so the first data row is line 2 in the file.
            $rowNo = 1;
            foreach ($rows as $row) {
                $rowNo++;

                $question_type = (int) ($row['question_type'] ?? 0);
                $question_type = $question_type === 1 ? 1 : 0;
                $question = trim((string) ($row['question'] ?? ''));
                $note = trim((string) ($row['note'] ?? ''));

                // Build a 1-based map of non-blank options, preserving the column number as the key.
                $option_texts = [];
                for ($i = 1; $i <= self::MAX_OPTIONS; $i++) {
                    $value = trim((string) ($row['option'.$i] ?? ''));
                    if ($value !== '') {
                        $option_texts[$i] = $value;
                    }
                }

                // Parse "1|3" into unique positive 1-based indexes.
                $correct_indexes = collect(explode('|', (string) ($row['correct_options'] ?? '')))
                    ->map(fn ($value) => (int) trim($value))
                    ->filter(fn ($value) => $value > 0)
                    ->unique()
                    ->values()
                    ->all();

                // Row-level validation with human-friendly messages.
                if ($question === '') {
                    $this->fail($rowNo, __('question_is_required'));
                }
                if (count($option_texts) < 2) {
                    $this->fail($rowNo, __('at_least_two_options_required'));
                }
                if (empty($correct_indexes)) {
                    $this->fail($rowNo, __('at_least_one_correct_option_required'));
                }
                foreach ($correct_indexes as $index) {
                    if (! array_key_exists($index, $option_texts)) {
                        $this->fail($rowNo, __('correct_option_must_point_to_a_non_blank_option'));
                    }
                }

                // Create the question (same columns for simple and equation based).
                // Equation rows must store the math-tex markup the question editor uses, so the maths renders on display.
                $question_store = new OnlineExamQuestion;
                $question_store->class_subject_id = $this->class_subject_id;
                $question_store->session_year_id = $this->session_year_id;
                $question_store->question_type = $question_type;
                $question_store->question = safe_htmlspecialchars($question_type === 1 ? $this->wrapEquations($question) : $question);
                $question_store->note = safe_htmlspecialchars($note);
                $question_store->save();

                // Create options, mapping the CSV column number to the new option id.
                $option_id_by_index = [];
                foreach ($option_texts as $index => $text) {
                    $option_store = new OnlineExamQuestionOption;
                    $option_store->question_id = $question_store->id;
                    $option_store->option = safe_htmlspecialchars($question_type === 1 ? $this->wrapEquations($text) : $text);
                    $option_store->save();
                    $option_id_by_index[$index] = $option_store->id;
                }

                // Create answers - the answer column stores the option's id, not the index.
                foreach ($correct_indexes as $index) {
                    $answer_store = new OnlineExamQuestionAnswer;
                    $answer_store->question_id = $question_store->id;
                    $answer_store->answer = $option_id_by_index[$index];
                    $answer_store->save();
                }
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Throw a row-aware validation error so the controller can surface it to the user.
     */
    private function fail(int $rowNo, string $message): void
    {
        throw ValidationException::withMessages([
            'file' => __('row').' '.$rowNo.': '.$message,
        ]);
    }

    /**
     * Wrap each LaTeX segment (\( ... \) or \[ ... \]) in the "math-tex" span that the question
     * editor and the apps render, mirroring how equation questions are stored from the UI. Plain text
     * around the equations (and rows already containing math-tex markup) is left untouched.
     */
    private function wrapEquations(string $text): string
    {
        if (str_contains($text, 'math-tex')) {
            return $text;
        }

        $wrapped = preg_replace_callback(
            '/\\\\\((.+?)\\\\\)|\\\\\[(.+?)\\\\\]/s',
            fn ($matches) => '<span class="math-tex">'.$matches[0].'</span>',
            $text
        );

        return $wrapped ?? $text;
    }
}
