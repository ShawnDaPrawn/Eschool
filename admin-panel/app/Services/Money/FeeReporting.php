<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountingSetting;
use App\Models\Platform\School;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/** Current reconciled fee subledger; never mixes currencies or offsets pinned credit. */
final class FeeReporting
{
    public function ageing(User $actor, string $currency): array
    {
        app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION);
        Validator::make(['currency' => $currency], ['currency' => ['required', 'regex:/\A[A-Z]{3}\z/D']])->validate();

        return $this->read($actor, fn () => $this->snapshot($currency));
    }

    public function export(User $actor, array $input): array
    {
        app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION);
        $data = Validator::make($input, self::exportRules())->validate();

        return $this->read($actor, function () use ($data): array {
            $school = app(CurrentSchool::class)->id();
            $timezone = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
            $today = now($timezone)->toDateString();
            $rows = [];
            $mappingRevision = null;
            if (in_array($data['layout'], ['ageing', 'charges'], true)) {
                $snapshot = $this->snapshot($data['currency']);
                if ($data['layout'] === 'ageing') {
                    $header = ['AsOf', 'FamilyReference', 'FamilyName', 'Currency', 'Current', 'Days1To29', 'Days30To59', 'Days60To89', 'Days90Plus', 'Outstanding', 'HeldCredit'];
                    foreach ($snapshot['accounts'] as $account) {
                        $rows[] = [$today, self::text($account['reference']), self::text($account['name']), $data['currency'], ...array_map(fn ($v) => self::decimal($v, $data['currency']), array_values($account['buckets'])), self::decimal($account['outstanding_cents'], $data['currency']), self::decimal($account['credit_cents'], $data['currency'])];
                    }
                } else {
                    $header = ['AsOf', 'FamilyReference', 'Invoice', 'Learner', 'Holder', 'Description', 'IssuedOn', 'DueOn', 'Currency', 'GrossCharge', 'CreditNotes', 'Allocated', 'Outstanding', 'DaysOverdue', 'AgeBucket', 'OriginalVAT', 'VATCredits', 'CurrentBeforeVAT'];
                    foreach ($snapshot['charges'] as $charge) {
                        $rows[] = [$today, self::text($charge['account_reference']), self::text($charge['invoice_number']), self::text($charge['learner_name']), self::text($charge['holder_name']), self::text($charge['description']), $charge['issued_on'], $charge['due_on'], $data['currency'],
                            self::decimal($charge['gross_charged_cents'], $data['currency']), self::decimal($charge['credited_cents'], $data['currency']), self::decimal($charge['allocated_cents'], $data['currency']), self::decimal($charge['outstanding_cents'], $data['currency']), $charge['days_overdue'], $charge['age_bucket'], self::decimal($charge['original_vat_cents'], $data['currency']), self::decimal($charge['vat_credited_cents'], $data['currency']), self::decimal($charge['net_charged_cents'], $data['currency'])];
                    }
                }
            } else {
                abort_unless($data['from_on'] <= $data['through_on'] && $data['through_on'] <= $today && CarbonImmutable::parse($data['from_on'])->diffInDays(CarbonImmutable::parse($data['through_on'])) <= 366, 422, 'Choose a completed journal period of at most one year.');
                app(FeeStatements::class)->completeAccounts(FeeAccount::where('currency', $data['currency'])->pluck('id')->map(fn ($id) => (int) $id)->all());
                $from = CarbonImmutable::parse($data['from_on'], $timezone)->startOfDay()->utc();
                $until = CarbonImmutable::parse($data['through_on'], $timezone)->startOfDay()->addDay()->utc();
                $entries = DB::table('ledger_entries as e')->where('e.school_id', $school)->where('e.currency', $data['currency'])->whereNotNull('e.posted_at')->where('e.posted_at', '>=', $from)->where('e.posted_at', '<', $until)
                    ->whereExists(function ($q) use ($school): void {
                        $q->selectRaw('1')->from('ledger_postings as fp')->join('ledger_accounts as fa', function ($j) {
                            $j->on('fa.school_id', '=', 'fp.school_id')->on('fa.id', '=', 'fp.account_id');
                        })
                            ->where('fp.school_id', $school)->whereColumn('fp.entry_id', 'e.id')->where('fa.module', 'fees');
                    })->select('e.id');
                abort_if(DB::table('ledger_entries')->where('school_id', $school)->where('currency', $data['currency'])->whereNull('posted_at')->exists(), 409, 'An incomplete ledger entry needs reconciliation before journal export.');
                $postings = DB::table('ledger_postings as p')->join('ledger_entries as e', function ($j) {
                    $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id');
                })
                    ->join('ledger_accounts as a', function ($j) {
                        $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id');
                    })
                    ->where('p.school_id', $school)->whereIn('p.entry_id', $entries)->orderBy('e.id')->orderBy('p.id')->limit(50001)
                    ->get(['p.id', 'p.entry_id', 'p.direction', 'p.cents', 'e.posted_at', 'e.description', 'a.type', 'a.module', 'a.reference']);
                abort_unless($postings->count() <= 50000, 422, 'Choose a shorter journal period, up to 50,000 posting rows.');
                abort_if($postings->contains(fn ($p) => $p->module !== 'fees'), 409, 'Cross-module entries need a consolidated accounting export.');
                $net = [];
                foreach ($postings as $posting) {
                    $net[$posting->entry_id] = ($net[$posting->entry_id] ?? 0) + ($posting->direction === 'debit' ? (int) $posting->cents : -(int) $posting->cents);
                }
                abort_if(array_filter($net, fn ($v) => $v !== 0) !== [], 409, 'Unbalanced journal history needs reconciliation.');
                $settings = FeeAccountingSetting::first();
                $mappingRevision = $settings?->revision;
                if ($data['layout'] !== 'journal') {
                    abort_unless($settings !== null, 422, 'Configure the accounting package mapping before export.');
                }
                abort_if($data['layout'] === 'xero' && trim($settings->xero_tax_label) === '', 422, 'Configure the exact zero-tax label for the Xero organisation before export.');
                if (in_array($data['layout'], ['xero', 'quickbooks'], true)) {
                    abort_unless($settings->getAttribute($data['layout'].'_currency') === $data['currency'], 422, 'Export only the configured base currency of the accounting organisation.');
                }
                $header = match ($data['layout']) {
                    'xero' => ['Narration', 'Date', 'Description', 'AccountCode', 'TaxRate', 'Amount', 'TrackingName1', 'TrackingOption1', 'TrackingName2', 'TrackingOption2'],
                    'quickbooks' => ['JournalNo', 'JournalDate', 'AccountName', 'Debits', 'Credits', 'Description', 'Name', 'Currency'],
                    default => ['Journal', 'PostedAtUTC', 'LocalDate', 'Currency', 'AccountType', 'AccountReference', 'Debit', 'Credit', 'Description'],
                };
                foreach ($postings as $posting) {
                    $date = CarbonImmutable::parse($posting->posted_at, 'UTC')->setTimezone($timezone);
                    $amount = self::decimal((int) $posting->cents, $data['currency']);
                    $debit = $posting->direction === 'debit' ? $amount : '';
                    $credit = $posting->direction === 'credit' ? $amount : '';
                    $journal = 'LE-'.$posting->entry_id;
                    $description = self::text($posting->description);
                    if ($data['layout'] === 'journal') {
                        $rows[] = [$journal, $posting->posted_at, $date->toDateString(), $data['currency'], $posting->type, self::text($posting->reference), $debit, $credit, $description];
                    } else {
                        $target = $settings->mappings[$posting->type][$data['layout']] ?? '';
                        abort_unless($target !== '', 422, 'Map every exported ledger account type to the target chart of accounts.');
                        $format = $data['layout'] === 'xero' ? $settings->xero_date_format : $settings->quickbooks_date_format;
                        $rows[] = $data['layout'] === 'xero'
                            ? [$journal, $date->format($format), $description, $target, self::text($settings->xero_tax_label), self::decimal(($posting->direction === 'debit' ? 1 : -1) * (int) $posting->cents, $data['currency']), '', '', '', '']
                            : [$journal, $date->format($format), $target, $debit, $credit, $description, '', $data['currency']];
                    }
                }
            }
            $csv = $this->csv($header, $rows);
            Audit::record('money.fee_accounting_exported', null, [], ['layout' => $data['layout'], 'currency' => $data['currency'], 'rows' => count($rows), 'fingerprint' => hash('sha256', $csv), 'mapping_revision' => $mappingRevision, 'from_on' => $data['from_on'] ?? null, 'through_on' => $data['through_on'] ?? null], $school);

            return ['csv' => $csv, 'row_count' => count($rows), 'filename' => 'fees-'.$school.'-'.$data['currency'].'-'.$data['layout'].'-'.$today.'.csv'];
        });
    }

    private function snapshot(string $currency): array
    {
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        $accounts = FeeAccount::where('currency', $currency)->orderBy('id')->limit(20001)->get();
        abort_unless($accounts->count() <= 20000, 422, 'This report supports up to 20,000 family accounts per currency.');
        $ids = $accounts->pluck('id')->map(fn ($v) => (int) $v)->all();
        $shareCount = DB::table('invoice_lines as l')->join('invoices as i', function ($j) {
            $j->on('i.school_id', '=', 'l.school_id')->on('i.id', '=', 'l.invoice_id');
        })
            ->where('l.school_id', app(CurrentSchool::class)->id())->whereIn('i.fee_account_id', $ids)->selectRaw('COALESCE(SUM(JSON_LENGTH(l.holder_shares)),0) AS shares')->first();
        abort_unless($shareCount !== null && (int) $shareCount->shares <= 50000, 422, 'This report supports up to 50,000 invoice-holder shares per currency.');
        app(FeeStatements::class)->completeAccounts($ids);
        $byAccount = [];
        foreach ($accounts as $account) {
            $byAccount[(int) $account->id] = ['id' => (int) $account->id, 'name' => $account->name, 'reference' => $account->reference, 'currency' => $currency, 'buckets' => FeeAgeing::empty(), 'outstanding_cents' => 0, 'credit_cents' => 0];
        }
        $charges = app(FeeStatements::class)->chargesFor($ids);
        $buckets = FeeAgeing::empty();
        $outstanding = 0;
        $credit = 0;
        foreach ($charges as &$charge) {
            $charge['days_overdue'] = FeeAgeing::days($charge['due_on'], $today);
            $charge['age_bucket'] = FeeAgeing::bucket($charge['days_overdue']);
            $charge['account_reference'] = $byAccount[$charge['fee_account_id']]['reference'];
            $byAccount[$charge['fee_account_id']]['buckets'][$charge['age_bucket']] = self::add($byAccount[$charge['fee_account_id']]['buckets'][$charge['age_bucket']], $charge['outstanding_cents']);
            $byAccount[$charge['fee_account_id']]['outstanding_cents'] = self::add($byAccount[$charge['fee_account_id']]['outstanding_cents'], $charge['outstanding_cents']);
            $buckets[$charge['age_bucket']] = self::add($buckets[$charge['age_bucket']], $charge['outstanding_cents']);
            $outstanding = self::add($outstanding, $charge['outstanding_cents']);
        }
        unset($charge);
        $school = app(CurrentSchool::class)->id();
        $creditAccounts = DB::table('payments')->where('school_id', $school)->whereIn('fee_account_id', $ids)->distinct()->select('school_id', 'fee_account_id', 'credit_account_id');
        $held = DB::table('ledger_postings as p')->join('ledger_entries as e', function ($j) {
            $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id');
        })
            ->joinSub($creditAccounts, 'credit', function ($j) {
                $j->on('credit.school_id', '=', 'p.school_id')->on('credit.credit_account_id', '=', 'p.account_id');
            })
            ->where('p.school_id', $school)->whereNotNull('e.posted_at')->selectRaw("credit.fee_account_id, SUM(CASE WHEN p.direction = 'credit' THEN p.cents ELSE -p.cents END) AS cents")->groupBy('credit.fee_account_id')->get();
        foreach ($held as $amount) {
            $cents = filter_var($amount->cents, FILTER_VALIDATE_INT);
            abort_unless($cents !== false && $cents >= 0, 409, 'Held credit history needs reconciliation.');
            $byAccount[(int) $amount->fee_account_id]['credit_cents'] = $cents;
            $credit = self::add($credit, $cents);
        }

        return ['as_of' => $today, 'generated_at' => now('UTC')->toIso8601String(), 'currency' => $currency, 'buckets' => $buckets, 'outstanding_cents' => $outstanding,
            'credit_cents' => $credit, 'accounts' => array_values($byAccount), 'charges' => $charges];
    }

    private function read(User $actor, callable $read): array
    {
        return DB::transaction(function () use ($actor, $read): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION, locked: true);
            app(CurrentSchool::class)->school()->refresh();

            return $read();
        }, 3);
    }

    private function csv(array $header, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        abort_unless($stream !== false, 500);
        try {
            fputcsv($stream, $header, escape: '', eol: "\r\n");
            foreach ($rows as $row) {
                fputcsv($stream, $row, escape: '', eol: "\r\n");
            }
            rewind($stream);

            return (string) stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    private static function text(string $value): string
    {
        return preg_match('/\A[\s\x{FEFF}]*[=+@\-]/u', $value) === 1 ? "'".$value : $value;
    }

    private static function add(int $total, int $cents): int
    {
        abort_unless($cents >= 0 && $total <= PHP_INT_MAX - $cents, 422, 'This report total exceeds the supported integer range.');

        return $total + $cents;
    }

    private static function decimal(int $cents, string $currency): string
    {
        return Money::ofCents($cents, $currency)->toDecimalString();
    }

    public static function exportRules(): array
    {
        return ['layout' => ['required', 'in:ageing,charges,journal,xero,quickbooks'], 'currency' => ['required', 'regex:/\A[A-Z]{3}\z/D'],
            'from_on' => ['nullable', 'required_if:layout,journal,xero,quickbooks', 'date_format:Y-m-d'], 'through_on' => ['nullable', 'required_if:layout,journal,xero,quickbooks', 'date_format:Y-m-d']];
    }
}
