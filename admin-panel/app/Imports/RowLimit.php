<?php

declare(strict_types=1);

namespace App\Imports;

use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * The imports' read filter. PhpSpreadsheet asks it about each cell while it
 * reads the uploaded file, so a file with too many rows is refused there,
 * before the rest is loaded into memory and before any row is saved. One
 * spreadsheet with hundreds of thousands of rows used to tie up a worker.
 */
class RowLimit implements IReadFilter
{
    /** Data rows one upload may hold, across all its sheets. */
    public const MAX_ROWS = 5000;

    /** @var array<string, int> highest row read so far in each sheet */
    private array $lastRows = [];

    private int $rows = 0;

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        $last = $this->lastRows[$worksheetName] ?? 0;
        if ($row > $last) {
            $this->rows += $row - $last;
            $this->lastRows[$worksheetName] = $row;

            // Every sheet is imported, and each one's first row is its headings.
            if ($this->rows - count($this->lastRows) > self::MAX_ROWS) {
                throw ValidationException::withMessages([
                    'file' => trans('The file has more than :max rows. Split it into smaller files and import them one at a time.', ['max' => self::MAX_ROWS]),
                ]);
            }
        }

        return true;
    }
}
