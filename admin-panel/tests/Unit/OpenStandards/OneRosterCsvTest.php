<?php

declare(strict_types=1);

namespace Tests\Unit\OpenStandards;

use App\Services\OpenStandards\OneRoster\OneRosterCsv;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class OneRosterCsvTest extends TestCase
{
    #[DataProvider('activeCells')]
    public function test_formula_like_source_values_are_refused_without_changing_identities(string $value): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unsafe spreadsheet value');
        OneRosterCsv::render(['givenName'], [['givenName' => $value]]);
    }

    public static function activeCells(): array
    {
        return [['=1+1'], ['+1+1'], ['-1+1'], ['@SUM(1,1)'], [" \t=1+1"], ["\u{FEFF}=1+1"], ["Name\0"]];
    }

    public function test_plain_roster_values_keep_their_exact_csv_content(): void
    {
        $value = "Nandi, \"Anne-Marie\"\nMokoena";
        $csv = OneRosterCsv::render(['sourcedId', 'givenName'], [['sourcedId' => '0a2e1f77', 'givenName' => $value]]);
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, $csv);
        rewind($stream);
        $this->assertSame(['sourcedId', 'givenName'], fgetcsv($stream, escape: ''));
        $this->assertSame(['0a2e1f77', $value], fgetcsv($stream, escape: ''));
        fclose($stream);
    }
}
