<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class BillingPreview
{
    public function build(User $actor, int $year, string $date, string $due, string $tax): array
    {
        return DB::transaction(function () use ($actor, $year, $date, $due, $tax): array {
            $rates = app(FeeCalculator::class)->forBilling($actor, $year, $date);
            $school = app(CurrentSchool::class)->id();
            $links = FeeAccountLearner::where('session_year_id', $year)->orderBy('learner_id')->lockForUpdate()->get();
            $byLearner = $links->keyBy('learner_id');
            $accounts = FeeAccount::whereIn('id', $links->pluck('fee_account_id')->unique())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $holderRows = FeeAccountHolder::where('session_year_id', $year)->whereIn('fee_account_id', $accounts->keys())->orderBy('user_id')->lockForUpdate()->get()->groupBy('fee_account_id');
            $sets = [];
            foreach ($links->groupBy('fee_account_id') as $id => $family) {
                $sets[(int) $id] = ['learner_ids' => $family->pluck('learner_id')->map(fn ($v) => (int) $v)->all(),
                    'holders' => $holderRows->get($id, collect())->map(fn ($h) => ['user_id' => (int) $h->user_id, 'share_bp' => (int) $h->share_bp])->all()];
            }
            $holders = app(FamilyHolders::class)->snapshots($sets);
            $billed = DB::table('invoice_lines as l')->join('invoices as i', function ($join) {
                $join->on('i.id', '=', 'l.invoice_id')->on('i.school_id', '=', 'l.school_id');
            })->where('l.school_id', $school)->where('l.session_year_id', $year)->lockForUpdate()->get(['l.learner_id', 'l.invoice_id', 'i.sealed_at'])->keyBy('learner_id');
            $rows = [];
            $totals = [];
            $exceptions = [];
            $groups = [];
            $skipped = 0;
            foreach ($rates['rows'] as $rate) {
                $row = $rate + ['state' => 'exception', 'account_id' => null, 'account_name' => null, 'account_reference' => null, 'holders' => [], 'existing_invoice_id' => null];
                $prior = $billed->get($rate['learner_id']);
                if ($prior !== null && $prior->sealed_at !== null) {
                    $row['state'] = 'already_billed';
                    $row['existing_invoice_id'] = (int) $prior->invoice_id;
                    $row['issues'] = [];
                    $skipped++;
                    $rows[] = $row;

                    continue;
                }
                if ($prior !== null) {
                    $row['issues'][] = 'Incomplete invoice history needs reconciliation';
                }
                if ($row['issues'] === [] && $row['amount_cents'] === 0) {
                    $row['state'] = 'no_charge';
                    $skipped++;
                    $rows[] = $row;

                    continue;
                }
                $link = $byLearner->get($rate['learner_id']);
                $account = $link === null ? null : $accounts->get($link->fee_account_id);
                if ($account === null || ! $account->active) {
                    $row['issues'][] = 'No active payer account';
                } else {
                    $row['account_id'] = (int) $account->id;
                    $row['account_name'] = $account->name;
                    $row['account_reference'] = $account->reference;
                    $row['holders'] = $holders[(int) $account->id]['holders'];
                    $row['issues'] = [...$row['issues'], ...$holders[(int) $account->id]['issues']];
                    if ($row['currency'] !== null && $row['currency'] !== $account->currency) {
                        $row['issues'][] = 'Plan and family account currencies differ';
                    }
                }
                if ($row['issues'] === []) {
                    $id = (int) $row['account_id'];
                    $amount = (int) $row['amount_cents'];
                    $groups[$id] = ($groups[$id] ?? 0) + $amount;
                    if ($groups[$id] > intdiv(LedgerService::MAX_CENTS, 2)) {
                        $row['issues'][] = 'Family invoice exceeds the supported cents limit';
                    } else {
                        $row['state'] = 'ready';
                        $totals[$row['currency']] = ($totals[$row['currency']] ?? 0) + $amount;
                    }
                }
                $row['issues'] = array_values(array_unique($row['issues']));
                foreach ($row['issues'] as $issue) {
                    $exceptions[$issue] = ($exceptions[$issue] ?? 0) + 1;
                }
                $rows[] = $row;
            }
            $ready = count(array_filter($rows, fn ($r) => $r['state'] === 'ready'));
            if ($ready === 0) {
                $exceptions['No new positive annual charges'] = 1;
            }
            ksort($totals);
            ksort($exceptions);
            app(MoneyPolicy::class)->authorize($actor, BillRuns::PERMISSION, locked: true);

            return ['year_id' => $year, 'year_start' => $rates['year_start'], 'year_end' => $rates['year_end'], 'as_of' => $date, 'due_on' => $due, 'tax_treatment' => $tax,
                'rows' => $rows, 'totals' => $totals, 'exceptions' => $exceptions, 'learners' => count($rows), 'billable' => $ready, 'skipped' => $skipped,
                'account_count' => count(array_unique(array_column(array_filter($rows, fn ($r) => $r['state'] === 'ready'), 'account_id')))];
        });
    }

    /** Exact weighted shares, largest remainder first, stable user-ID ties. */
    public static function shares(int $cents, array $holders): array
    {
        if ($cents < 0 || $cents > FeePlans::MAX_CENTS || $holders === [] || count($holders) > 20) {
            throw new InvalidArgumentException('Use a supported annual amount and explicit holder shares.');
        }
        $ids = [];
        $totalBp = 0;
        foreach ($holders as $holder) {
            if (! is_int($holder['user_id'] ?? null) || $holder['user_id'] < 1 || ! is_int($holder['share_bp'] ?? null) || $holder['share_bp'] < 1 || $holder['share_bp'] > 10000) {
                throw new InvalidArgumentException('Use positive integer holder identities and basis points.');
            }
            $ids[] = $holder['user_id'];
            $totalBp += $holder['share_bp'];
        }
        if ($totalBp !== 10000 || count(array_unique($ids)) !== count($ids)) {
            throw new InvalidArgumentException('Unique holder shares must total 100%.');
        }
        $result = [];
        foreach ($holders as $h) {
            $weighted = $cents * $h['share_bp'];
            $result[] = $h + ['amount_cents' => intdiv($weighted, 10000), 'remainder' => $weighted % 10000];
        }
        $left = $cents - array_sum(array_column($result, 'amount_cents'));
        usort($result, fn ($a, $b) => ($b['remainder'] <=> $a['remainder']) ?: ($a['user_id'] <=> $b['user_id']));
        for ($i = 0; $i < $left; $i++) {
            $result[$i]['amount_cents']++;
        }
        usort($result, fn ($a, $b) => $a['user_id'] <=> $b['user_id']);

        return array_map(function ($h) {
            unset($h['remainder']);

            return $h;
        }, $result);
    }
}
