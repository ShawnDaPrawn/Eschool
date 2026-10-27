<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Money\BankCsv;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class BankCsvTest extends TestCase
{
    public function test_explicit_columns_preserve_exact_cents_bom_quoted_commas_and_reference_normalization(): void
    {
        $rows = app(BankCsv::class)->parse("\xEF\xBB\xBF".implode(',', BankCsv::HEADER)."\r\n2026-10-27, bank-02 , nef123 ,\"Quoted, \"\"description\"\"\",0,0.01,ZAR\r\n2026-10-26,BANK-01,,,1000000000.00,,ZAR\r\n");
        $this->assertSame(['BANK-01', 'BANK-02'], array_column($rows, 'transaction_reference'));
        $this->assertSame([100000000000, 1], array_column($rows, 'amount_cents'));
        $this->assertSame('NEF123', $rows[1]['family_reference']);
        $this->assertSame('Quoted, "description"', $rows[1]['description']);
        $this->assertSame('debit', $rows[1]['direction']);
    }

    #[DataProvider('invalidRows')]
    public function test_invalid_csv_is_refused_without_ambiguous_date_or_money_conversion(string $csv): void
    {
        try {
            app(BankCsv::class)->parse($csv);
            $this->fail('Expected invalid CSV refusal');
        } catch (ValidationException $e) {
            $this->assertNotEmpty($e->errors());
        } catch (HttpExceptionInterface $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }

    public static function invalidRows(): array
    {
        $header = implode(',', BankCsv::HEADER)."\n";

        return array_map(fn ($csv) => [$csv], [
            '', $header, "date,reference,credit\n2026-10-27,TX,1.00\n",
            $header."2026-10-27,TX,,,1.00,,ZAR,extra\n",
            $header."2026-10-27,TX,,,1.00\n",
            $header."27/10/2026,TX,,,1.00,,ZAR\n",
            $header."2026-02-30,TX,,,1.00,,ZAR\n",
            $header."2026-10-27,TX,,,1.00,2.00,ZAR\n",
            $header."2026-10-27,TX,,,0.00,0,ZAR\n",
            $header."2026-10-27,TX,,,-1.00,,ZAR\n",
            $header."2026-10-27,TX,,,1.001,,ZAR\n",
            $header."2026-10-27,TX,,,1e3,,ZAR\n",
            $header."2026-10-27,TX,,,1000000000.01,,ZAR\n",
            $header."2026-10-27,TX,,,1.00,,zar\n",
            $header."2026-10-27,,,,1.00,,ZAR\n",
            $header."2026-10-27,TX,,\"Unclosed,1.00,,ZAR\n",
            $header."2026-10-27,TX,,\"Closed\"trailing,1.00,,ZAR\n",
            $header."2026-10-27,TX,,Un\"quoted,1.00,,ZAR\n",
            $header."2026-10-27,TX,,,1.00,,ZAR\n2026-10-27, tx ,,,1.00,,ZAR\n",
            $header."2026-10-27,TX,,Bad\0description,1.00,,ZAR\n",
            $header."2026-10-27,TX,,Bad\xFFdescription,1.00,,ZAR\n",
            str_repeat('a', 262145),
            $header.implode('', array_map(fn ($i) => "2026-10-27,TX-$i,,,1.00,,ZAR\n", range(1, 501))),
        ]);
    }
}
