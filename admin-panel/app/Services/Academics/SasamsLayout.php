<?php

declare(strict_types=1);

namespace App\Services\Academics;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use ZipArchive;

/** Explicit external layouts; no guessed provincial codes or status conversions. */
class SasamsLayout
{
    public const FIELDS = ['learner_ref', 'student_number', 'admission_no', 'first_name', 'last_name', 'dob', 'gender', 'grade', 'homeroom', 'starts_on', 'ends_on', 'on_date', 'attendance_code', 'subject_code', 'task_ref', 'score', 'maximum', 'mark_code', 'literal'];

    public function validate(array $input): array
    {
        $p = Validator::make($input, [
            'schema_version' => ['required', 'integer', 'in:1'], 'school_id' => ['required', 'integer', 'min:1'], 'session_year_id' => ['required', 'integer', 'min:1'],
            'kind' => ['required', Rule::in(['learners', 'attendance', 'marks'])], 'format' => ['required', Rule::in(['csv', 'xlsx'])],
            'version' => ['required', 'string', 'max:100'], 'source' => ['required', 'string', 'max:500'], 'import_menu' => ['required', 'string', 'max:191'],
            'on_date' => ['required_unless:kind,marks', 'nullable', 'date_format:Y-m-d'], 'task_id' => ['required_if:kind,marks', 'nullable', 'integer', 'min:1'],
            'group_id' => ['required_if:kind,marks', 'nullable', 'integer', 'min:1'], 'task_ref' => ['required_if:kind,marks', 'nullable', 'string', 'max:100'],
            'subject_code' => ['required_if:kind,marks', 'nullable', 'string', 'max:100'], 'delimiter' => ['required_if:format,csv', Rule::in([',', ';', "\t"])],
            'template_sha256' => ['required_if:format,xlsx', 'regex:/^[a-f0-9]{64}$/'], 'sheet' => ['required_if:format,xlsx', 'string', 'max:31'],
            'header_row' => ['required_if:format,xlsx', 'integer', 'between:1,100'],
            'columns' => ['required', 'array', 'min:1', 'max:40'], 'columns.*' => ['array:header,field,value'],
            'columns.*.header' => ['required', 'string', 'max:191', 'distinct'], 'columns.*.field' => ['required', Rule::in(self::FIELDS)],
            'columns.*.value' => ['required_if:columns.*.field,literal', 'nullable', 'string', 'max:191'],
            'learner_refs' => ['required', 'array', 'min:1', 'max:10000'], 'learner_refs.*' => ['required', 'string', 'max:100', 'distinct'],
            'attendance_codes' => ['sometimes', 'array'], 'attendance_codes.*' => ['required', 'string', 'max:30'],
            'mark_codes' => ['sometimes', 'array'], 'mark_codes.*' => ['required', 'string', 'max:30'],
            'value_maps' => ['sometimes', 'array:gender,grade,homeroom'], 'value_maps.*' => ['array', 'max:1000'], 'value_maps.*.*' => ['required', 'string', 'max:191'],
        ])->validate();
        $required = match ($p['kind']) {
            'learners' => ['learner_ref'], 'attendance' => ['learner_ref', 'on_date', 'attendance_code'], 'marks' => ['learner_ref', 'score'],
        };
        if (array_diff($required, array_column($p['columns'], 'field')) !== []) {
            $this->fail('Include the dataset identity and value columns in the target layout.');
        }

        return $p;
    }

    public function render(array $p, array $rows, ?string $template): string
    {
        $values = [];
        foreach ($rows as $row) {
            $cells = [];
            foreach ($p['columns'] as $column) {
                $value = $column['field'] === 'literal' ? ($column['value'] ?? '') : ($row[$column['field']] ?? null);
                if ($value === null) {
                    $this->fail('A selected export field is unavailable. Correct the source or mapping; no blank value was substituted.');
                }
                $value = (string) $value;
                if (isset($p['value_maps'][$column['field']])) {
                    $value = $p['value_maps'][$column['field']][$value] ?? null;
                    if ($value === null) {
                        $this->fail('Map every selected demographic code explicitly for the target layout.');
                    }
                }
                // Refuse active CSV values instead of modifying target identities/codes.
                if (preg_match('/^[\s\x00-\x1f]*[=+@]/u', $value) || (str_starts_with(ltrim($value), '-') && ! preg_match('/^-\d+(?:\.\d+)?$/D', $value)) || str_contains($value, "\0")) {
                    $this->fail('The export contains an unsafe spreadsheet value. Correct the source or mapping.');
                }
                $cells[] = $value;
            }
            $values[] = $cells;
        }
        $headers = array_column($p['columns'], 'header');
        foreach ($headers as $header) {
            if (preg_match('/^[\s\x00-\x1f]*[=+@-]/u', $header)) {
                $this->fail('Use plain target column headers without spreadsheet expressions.');
            }
        }
        if ($p['format'] === 'csv') {
            $stream = fopen('php://temp', 'w+b');
            try {
                foreach ([$headers, ...$values] as $row) {
                    fputcsv($stream, $row, $p['delimiter'], '"', '', "\r\n");
                }
                rewind($stream);

                return stream_get_contents($stream);
            } finally {
                fclose($stream);
            }
        }

        return $this->xlsx($p, $headers, $values, $template);
    }

    private function xlsx(array $p, array $headers, array $rows, ?string $template): string
    {
        if ($template === null || ! is_file($template) || filesize($template) > 2 * 1024 * 1024 || ! hash_equals($p['template_sha256'], hash_file('sha256', $template))) {
            $this->fail('Choose the exact XLSX template matching the layout hash, at most 2 MiB.');
        }
        $zip = new ZipArchive;
        if ($zip->open($template) !== true) {
            $this->fail('The template must be an XLSX workbook.');
        }
        try {
            $expanded = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $expanded += $stat['size'];
                if ($expanded > 8 * 1024 * 1024 || $zip->numFiles > 200 || preg_match('/vba|externalLinks|embeddings|connections|activeX/i', $stat['name'])) {
                    $this->fail('Use a bounded template without macros, external links or embedded objects.');
                }
                if (str_ends_with($stat['name'], '.xml') || str_ends_with($stat['name'], '.rels')) {
                    $xml = $zip->getFromIndex($i);
                    if (preg_match('/<!DOCTYPE|<!ENTITY|<(?:[\w]+:)?f\b|TargetMode\s*=\s*["\']External/i', $xml)) {
                        $this->fail('Use a template without formulas or external references.');
                    }
                    if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $stat['name'])) {
                        if ($stat['size'] > 512 * 1024) {
                            $this->fail('Use a small blank template without learner rows.');
                        }
                        preg_match_all('/<c\b[^>]*\br=["\']([A-Z]+)(\d+)["\']/', $xml, $cells, PREG_SET_ORDER);
                        foreach ($cells as $cell) {
                            if ((int) $cell[2] > $p['header_row'] || Coordinate::columnIndexFromString($cell[1]) > 40) {
                                $this->fail('The blank template must stay within its header rows and 40 columns.');
                            }
                        }
                    }
                }
            }
        } finally {
            $zip->close();
        }
        $reader = new XlsxReader;
        $book = $reader->load($template);
        $output = tempnam(sys_get_temp_dir(), 'sasams-render-');
        try {
            $sheet = $book->getSheetByName($p['sheet']);
            if ($book->getSheetCount() !== 1 || $sheet === null || $sheet->getHighestDataRow() > $p['header_row'] || Coordinate::columnIndexFromString($sheet->getHighestColumn()) > 40) {
                $this->fail('Choose a blank target sheet with no learner rows below its header.');
            }
            foreach ($headers as $i => $header) {
                if ((string) $sheet->getCell([$i + 1, $p['header_row']])->getValue() !== $header) {
                    $this->fail('Template headers differ from the versioned layout.');
                }
            }
            foreach ($rows as $i => $row) {
                foreach ($row as $column => $value) {
                    $sheet->setCellValueExplicit([$column + 1, $p['header_row'] + 1 + $i], $value, DataType::TYPE_STRING);
                }
            }
            (new XlsxWriter($book))->save($output);

            return file_get_contents($output);
        } finally {
            $book->disconnectWorksheets();
            if ($output !== false && is_file($output)) {
                unlink($output);
            }
        }
    }

    public function fail(string $message): never
    {
        throw ValidationException::withMessages(['layout' => __($message)]);
    }
}
