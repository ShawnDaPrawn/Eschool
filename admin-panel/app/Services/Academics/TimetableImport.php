<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** Bounded CSV adapter; exported labels map explicitly to school-owned IDs. */
class TimetableImport
{
    public static function rules(): array
    {
        return ['csv' => ['required', 'string', 'max:524288'], 'session_year_id' => ['required', 'integer', 'min:1'],
            'defaults' => ['sometimes', 'array'], 'columns' => ['sometimes', 'array'], 'values' => ['sometimes', 'array']];
    }

    public function preview(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::rules())->validate();
        if (strlen($data['csv']) > 524288 || str_contains($data['csv'], "\0")) {
            $this->invalid('The CSV is too large or contains a binary value.');
        }
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $data['csv']);
        rewind($stream);
        try {
            $line = strtok($data['csv'], "\n");
            $delimiter = substr_count($line ?: '', "\t") > substr_count($line ?: '', ',') ? "\t" : ',';
            $header = fgetcsv($stream, 0, $delimiter, '"', '');
            if ($header === false) {
                $this->invalid('The CSV needs a header.');
            }
            $header = array_map(fn ($value) => trim((string) $value, "\xEF\xBB\xBF \t\r\n"), $header);
            if (count($header) > 40 || count(array_unique($header)) !== count($header)) {
                $this->invalid('CSV columns must be unique (at most 40).');
            }
            $allowed = array_keys(TimetableEngine::rules());
            $columns = $data['columns'] ?? array_combine($allowed, $allowed);
            if (array_diff(array_keys($columns), $allowed) !== []) {
                $this->invalid('A column mapping contains an unsupported lesson field.');
            }
            foreach ($columns as $field => $column) {
                if (! is_string($column) || strlen($column) > 100) {
                    $this->invalid('Column mappings must name a CSV column.');
                }
                if (! in_array($column, $header, true) && isset($data['columns'])) {
                    $this->invalid('A mapped column is missing from the CSV.');
                }
            }
            $defaults = array_intersect_key($data['defaults'] ?? [], array_flip($allowed));
            $defaults['session_year_id'] = (int) $data['session_year_id'];
            $rows = [];
            while (($cells = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                if ($cells === [null]) {
                    continue;
                }
                if (count($rows) >= 300 || count($cells) !== count($header)) {
                    $this->invalid('Import at most 300 rows, each with exactly the header’s columns.');
                }
                $source = array_combine($header, $cells);
                $row = $defaults + ['active' => true, 'week_pattern' => 'all'];
                foreach ($columns as $field => $column) {
                    if (! array_key_exists($column, $source)) {
                        continue;
                    }
                    $value = trim((string) $source[$column]);
                    if ($value === '') {
                        continue;
                    }
                    if (isset($data['values'][$field])) {
                        if (! is_array($data['values'][$field]) || ! array_key_exists($value, $data['values'][$field])) {
                            $this->invalid('An exported label needs an explicit school value mapping.');
                        }
                        $value = $data['values'][$field][$value];
                    }
                    $row[$field] = $value;
                }
                // Exported day names are mapped through `values.weekday`; there
                // is no locale, teacher-name or ambiguous-class guess.
                if ((int) $row['session_year_id'] !== (int) $data['session_year_id']) {
                    $this->invalid('Every row must use the selected school year.');
                }
                $rows[] = $row;
            }
            $preview = app(TimetableEngine::class)->preview($actor, $rows);
            $preview['imported_rows'] = count($rows);

            return $preview;
        } finally {
            fclose($stream);
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['csv' => __($message)]);
    }
}
