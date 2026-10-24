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
            'defaults' => ['sometimes', 'array', 'max:16'], 'columns' => ['sometimes', 'array', 'max:16'], 'values' => ['sometimes', 'array', 'max:16']];
    }

    public function inspect(User $actor, string $csv): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $sheet = $this->read($csv);
        $values = [];
        foreach ($sheet['headers'] as $header) {
            $values[$header] = array_values(array_unique(array_filter(array_map(fn ($row) => trim((string) $row[$header]), $sheet['rows']), fn ($value) => $value !== '')));
        }

        return ['headers' => $sheet['headers'], 'values' => $values, 'count' => count($sheet['rows'])];
    }

    public function preview(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::rules())->validate();
        $sheet = $this->read($data['csv']);
        $allowed = array_keys(TimetableEngine::rules());
        $columns = $data['columns'] ?? array_combine($allowed, $allowed);
        if (array_diff(array_keys($columns), $allowed) !== []) {
            $this->invalid('A column mapping contains an unsupported lesson field.');
        }
        foreach ($columns as $column) {
            if (! is_string($column) || strlen($column) > 100) {
                $this->invalid('Column mappings must name a CSV column.');
            }
            if (! in_array($column, $sheet['headers'], true) && isset($data['columns'])) {
                $this->invalid('A mapped column is missing from the CSV.');
            }
        }
        $defaults = array_intersect_key($data['defaults'] ?? [], array_flip($allowed));
        $defaults['session_year_id'] = (int) $data['session_year_id'];
        $rows = [];
        foreach ($sheet['rows'] as $source) {
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
            if ((int) $row['session_year_id'] !== (int) $data['session_year_id']) {
                $this->invalid('Every row must use the selected school year.');
            }
            $rows[] = $row;
        }
        $preview = app(TimetableEngine::class)->preview($actor, $rows);
        $preview['imported_rows'] = count($rows);

        return $preview;
    }

    private function read(string $csv): array
    {
        if ($csv === '' || strlen($csv) > 524288 || str_contains($csv, "\0") || ! mb_check_encoding($csv, 'UTF-8')) {
            $this->invalid('The CSV is empty, too large or contains a binary value.');
        }
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $csv);
        rewind($stream);
        try {
            $line = strtok($csv, "\n");
            $delimiter = substr_count($line ?: '', "\t") > substr_count($line ?: '', ',') ? "\t" : ',';
            $headers = fgetcsv($stream, 0, $delimiter, '"', '');
            if ($headers === false) {
                $this->invalid('The CSV needs a header.');
            }
            $headers = array_map(fn ($value) => trim(str_starts_with((string) $value, "\xEF\xBB\xBF") ? substr((string) $value, 3) : (string) $value), $headers);
            if (count($headers) > 40 || in_array('', $headers, true) || count(array_unique($headers)) !== count($headers)) {
                $this->invalid('CSV columns must be named and unique (at most 40).');
            }
            foreach ($headers as $header) {
                if (strlen($header) > 100) {
                    $this->invalid('CSV column names may have at most 100 bytes.');
                }
            }
            $rows = [];
            while (($cells = fgetcsv($stream, 0, $delimiter, '"', '')) !== false) {
                if ($cells === [null]) {
                    continue;
                }
                if (count($rows) >= 300 || count($cells) !== count($headers)) {
                    $this->invalid('Import at most 300 rows, each with exactly the header’s columns.');
                }
                foreach ($cells as $cell) {
                    if (strlen((string) $cell) > 1024) {
                        $this->invalid('Each timetable cell must fit within 1,024 bytes.');
                    }
                }
                $rows[] = array_combine($headers, $cells);
            }
            if ($rows === []) {
                $this->invalid('The CSV needs at least one lesson row.');
            }

            return ['headers' => $headers, 'rows' => $rows];
        } finally {
            fclose($stream);
        }
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['csv' => __($message)]);
    }
}
