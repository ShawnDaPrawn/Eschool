<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Ledger\Money;
use Illuminate\Support\Facades\Validator;

/** Explicit bank-neutral interchange format; never guesses dates, signs or columns. */
final class BankCsv
{
    public const HEADER = ['received_on', 'transaction_reference', 'family_reference', 'description', 'credit', 'debit', 'currency'];

    public function parse(string $csv): array
    {
        abort_unless(strlen($csv) <= 262144 && mb_check_encoding($csv, 'UTF-8') && ! str_contains($csv, "\0"), 422, 'Use a UTF-8 CSV file of at most 256 KiB.');
        $csv = preg_replace('/\A\xEF\xBB\xBF/', '', $csv) ?? '';
        $this->syntax($csv);
        $stream = fopen('php://temp', 'r+');
        abort_unless($stream !== false, 500);
        try {
            fwrite($stream, $csv);
            rewind($stream);
            abort_unless(fgetcsv($stream, escape: '') === self::HEADER, 422, 'Use the supplied bank import column headings in their original order.');
            $rows = [];
            $seen = [];
            while (($cells = fgetcsv($stream, escape: '')) !== false) {
                if ($cells === [null]) {
                    continue;
                }
                abort_unless(count($cells) === count(self::HEADER) && count($rows) < 500, 422, 'Use at most 500 complete bank rows per import.');
                $data = array_combine(self::HEADER, array_map(fn ($v) => trim((string) $v), $cells));
                $data['transaction_reference'] = FeePayments::reference($data['transaction_reference']);
                $data['family_reference'] = strtoupper($data['family_reference']);
                Validator::make($data, [
                    'received_on' => ['required', 'date_format:Y-m-d'],
                    'transaction_reference' => FeePayments::rules()['source_reference'],
                    'family_reference' => ['nullable', 'string', 'max:80', 'regex:/\A[A-Z0-9 ._:\/\-]*\z/D'],
                    'description' => ['nullable', 'string', 'max:240', 'not_regex:/[\x00-\x1F\x7F]/u'],
                    'credit' => ['nullable', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'],
                    'debit' => ['nullable', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'],
                    'currency' => ['required', 'regex:/\A[A-Z]{3}\z/D'],
                ])->validate();
                $credit = $data['credit'] === '' ? 0 : Money::fromDecimalString($data['credit'])->cents;
                $debit = $data['debit'] === '' ? 0 : Money::fromDecimalString($data['debit'])->cents;
                abort_unless(($credit > 0 xor $debit > 0) && max($credit, $debit) <= FeePlans::MAX_CENTS, 422, 'Each row needs exactly one positive credit or debit in supported cents.');
                abort_if(isset($seen[$data['transaction_reference']]), 422, 'A transaction reference occurs more than once in this file.');
                $seen[$data['transaction_reference']] = true;
                $rows[] = ['received_on' => $data['received_on'], 'transaction_reference' => $data['transaction_reference'],
                    'family_reference' => $data['family_reference'], 'description' => $data['description'],
                    'direction' => $credit > 0 ? 'credit' : 'debit', 'amount_cents' => max($credit, $debit), 'currency' => $data['currency']];
            }
            abort_unless($rows !== [], 422, 'Include at least one bank transaction.');
            usort($rows, fn ($a, $b) => strcmp($a['transaction_reference'], $b['transaction_reference']));

            return $rows;
        } finally {
            fclose($stream);
        }
    }

    private function syntax(string $csv): void
    {
        // Reject truncated quotes and ambiguous characters after a quoted field.
        $state = 'start';
        for ($i = 0, $length = strlen($csv); $i < $length; $i++) {
            $c = $csv[$i];
            if ($state === 'quoted') {
                if ($c === '"') {
                    if ($i + 1 < $length && $csv[$i + 1] === '"') {
                        $i++;
                    } else {
                        $state = 'closed';
                    }
                }

                continue;
            }
            if ($c === ',' || $c === "\r" || $c === "\n") {
                $state = 'start';

                continue;
            }
            abort_if($state === 'closed' || ($c === '"' && $state !== 'start'), 422, 'Malformed CSV quoting.');
            $state = $c === '"' ? 'quoted' : 'plain';
        }
        abort_if($state === 'quoted', 422, 'Malformed CSV quoting.');
    }
}
