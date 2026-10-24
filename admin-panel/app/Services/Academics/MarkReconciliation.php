<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\MarkSnapshot;
use App\Models\Students;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Validation\ValidationException;

/** A weekly comparison, never an import or an overwrite of academic evidence. */
class MarkReconciliation
{
    public function compare(int $termId, string $file): array
    {
        $term = Term::findOrFail($termId);
        if (! is_file($file) || ! is_readable($file) || filesize($file) > 8 * 1024 * 1024) {
            throw ValidationException::withMessages(['sheet' => 'Choose a readable CSV smaller than 8 MiB.']);
        }
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['sheet' => 'The CSV could not be read.']);
        }
        try {
            $header = fgetcsv($handle, 16384, ',', '"', '');
            if ($header !== ['student_number', 'subject_code', 'percentage']) {
                throw ValidationException::withMessages(['sheet' => 'CSV headers must be student_number,subject_code,percentage. Use percentage as a decimal with up to two places.']);
            }
            $learners = Students::whereNotNull('student_number')->get(['id', 'student_number'])->keyBy('student_number');
            $subjects = Subject::get(['id', 'code'])->groupBy('code');
            $latest = MarkSnapshot::selectRaw('MAX(id)')->where('term_id', $termId)->where('session_year_id', $term->session_year_id)->groupBy('learner_id', 'subject_id', 'grade_level_id');
            $snapshots = MarkSnapshot::whereIn('id', $latest)->get(['id', 'learner_id', 'subject_id', 'grade_level_id', 'percentage_bp', 'complete'])
                ->groupBy(fn ($m) => $m->learner_id.':'.$m->subject_id)->map(fn ($rows) => $rows->unique('grade_level_id')->values());
            $seen = [];
            $rows = [];
            $counts = ['matched' => 0, 'different' => 0, 'incomplete' => 0, 'not_found' => 0, 'ambiguous' => 0];
            while (($cells = fgetcsv($handle, 16384, ',', '"', '')) !== false) {
                if (count($rows) >= 25000 || count($cells) !== 3 || max(array_map(fn ($c) => strlen((string) $c), $cells)) > 191) {
                    throw ValidationException::withMessages(['sheet' => 'The CSV must contain at most 25,000 bounded three-column rows.']);
                }
                [$number, $code, $value] = array_map(fn ($c) => trim((string) $c), $cells);
                $identity = $number.'|'.$code;
                if (isset($seen[$identity])) {
                    throw ValidationException::withMessages(['sheet' => 'Submit each learner and subject once.']);
                }
                $seen[$identity] = true;
                $expected = AcademicNumbers::units($value, 'percentage', 10000);
                $learner = $learners->get($number);
                $subject = $subjects->get($code, collect());
                $status = $learner === null || $subject->isEmpty() ? 'not_found' : ($subject->count() !== 1 ? 'ambiguous' : null);
                $snapshot = null;
                if ($status === null) {
                    $matches = $snapshots->get($learner->id.':'.$subject->sole()->id, collect());
                    $status = $matches->isEmpty() ? 'not_found' : ($matches->count() !== 1 ? 'ambiguous' : null);
                    if ($status === null) {
                        $snapshot = $matches->sole();
                        $status = ! $snapshot->complete ? 'incomplete' : ($snapshot->percentage_bp === $expected ? 'matched' : 'different');
                    }
                }
                $counts[$status]++;
                $rows[] = ['student_number' => $number, 'subject_code' => $code, 'expected_bp' => $expected, 'actual_bp' => $snapshot?->percentage_bp,
                    'snapshot_id' => $snapshot?->id, 'status' => $status];
            }
            if ($rows === []) {
                throw ValidationException::withMessages(['sheet' => 'The CSV contains no learner marks.']);
            }

            return ['term_id' => $termId, 'session_year_id' => (int) $term->session_year_id, 'counts' => $counts, 'rows' => $rows];
        } finally {
            fclose($handle);
        }
    }
}
