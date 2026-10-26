<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\NoCurrentSchool;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class LedgerServiceTest extends TestCase
{
    use UsesTwoSchools;

    private LedgerService $ledger;

    private int $cash;

    private int $family;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->ledger = app(LedgerService::class);
        $this->cash = $this->ledger->openAccount(AccountType::CashDesk, 'cash:desk', 'ZAR');
        $this->family = $this->ledger->openAccount(AccountType::Family, 'family:1', 'ZAR', $this->schoolA->ids['farid_user']);
        config(['modules.wallet' => ['name' => 'Wallet', 'money' => true, 'default' => true],
            'school_packages.legacy' => [...config('school_packages.legacy'), 'wallet']]);
        app(ModuleGate::class)->forget();
    }

    public function test_balanced_integer_posting_is_sealed_and_uses_utc(): void
    {
        config(['app.timezone' => 'Pacific/Honolulu']);
        $this->travelTo(now('UTC')->setDateTime(2026, 10, 26, 12, 34, 56));
        $id = $this->receipt('cash:receipt:1', 12345);
        $entry = DB::table('ledger_entries')->find($id);
        $this->assertSame('2026-10-26 12:34:56.000000', $entry->posted_at);
        $this->assertSame(2, DB::table('ledger_postings')->where('entry_id', $id)->count());
        $this->assertSame(12345, $this->ledger->balance($this->family)->cents);
        $this->assertSame(12345, (int) DB::table('ledger_accounts')->where('id', $this->cash)->value('balance_cents'));
        $this->assertSame([], $this->ledger->projectionDifferences());
    }

    public function test_exact_replay_is_permanent_and_accepts_reordered_postings_even_after_disable(): void
    {
        $id = $this->receipt('cash:receipt:1', 100);
        app(ModuleGate::class)->change('fees', false, $this->superAdmin());
        $this->travel(3)->days();
        $this->assertSame($id, $this->ledger->post('cash:receipt:1', 'cash:receipt:1', 'Recorded receipt', [
            Posting::credit($this->family, Money::ofCents(100, 'ZAR')), Posting::debit($this->cash, Money::ofCents(100, 'ZAR')),
        ]));
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(100, $this->ledger->balance($this->family)->cents);
    }

    public function test_a_key_cannot_change_amount_and_a_new_key_cannot_repost_the_same_source(): void
    {
        $this->receipt('cash:receipt:1', 100);
        foreach ([['cash:receipt:1', 'cash:receipt:1', 101], ['another-key', 'cash:receipt:1', 100]] as [$key, $source, $cents]) {
            $this->refuses(fn () => $this->ledger->post($key, $source, 'Recorded receipt', $this->lines($cents)));
        }
        $this->assertSame(1, DB::table('ledger_entries')->count());
    }

    public function test_wrong_school_accounts_owners_and_missing_context_are_refused(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, fn () => $this->ledger->openAccount(AccountType::Family, 'family:2', 'ZAR'));
        $this->refuses(fn () => $this->ledger->post('wrong-school', 'wrong-school', 'Receipt', [
            Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($foreign, Money::ofCents(100)),
        ]));
        $this->refuses(fn () => $this->ledger->openAccount(AccountType::Wallet, 'foreign-owner', 'ZAR', $this->schoolB->ids['asha_user']));
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $this->ledger->balance($this->family)));
        app(CurrentSchool::class)->forget();
        $this->expectException(NoCurrentSchool::class);
        $this->ledger->balance($this->family);
    }

    public function test_unbalanced_mixed_currency_and_overflow_requests_leave_no_partial_money(): void
    {
        foreach ([
            [Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($this->family, Money::ofCents(99))],
            [Posting::debit($this->cash, Money::ofCents(100, 'ZAR')), Posting::credit($this->family, Money::ofCents(100, 'USD'))],
            [Posting::debit($this->cash, Money::ofCents(PHP_INT_MAX)), Posting::credit($this->family, Money::ofCents(PHP_INT_MAX))],
        ] as $lines) {
            try {
                $this->ledger->post('invalid', 'invalid', 'Receipt', $lines);
                $this->fail('Invalid money was accepted');
            } catch (InvalidArgumentException) {
            }
        }
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, $this->ledger->balance($this->cash)->cents);
    }

    public function test_wallet_cannot_overdraw_and_projection_drift_cannot_authorize_spend(): void
    {
        $wallet = $this->ledger->openAccount(AccountType::Wallet, 'wallet:1', 'ZAR', $this->schoolA->ids['asha_user']);
        $this->ledger->post('topup', 'topup', 'Desk top-up', [Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]);
        $spend = fn () => $this->ledger->post('spend', 'spend', 'Wallet spend', [Posting::debit($wallet, Money::ofCents(101)), Posting::credit($this->cash, Money::ofCents(101))]);
        $this->refuses($spend);
        DB::table('ledger_accounts')->where('id', $wallet)->update(['balance_cents' => 10000]);
        $this->refuses($spend);
        $this->assertSame(100, $this->ledger->balance($wallet)->cents);
        $this->assertCount(1, $this->ledger->projectionDifferences());
        $this->ledger->projectionDifferences(true);
        $this->assertSame([], $this->ledger->projectionDifferences());
        $this->assertSame(1, DB::table('ledger_entries')->count());
    }

    public function test_zero_wallet_with_history_stays_readable_and_new_money_is_refused(): void
    {
        $wallet = $this->ledger->openAccount(AccountType::Wallet, 'wallet:1', 'ZAR');
        $this->ledger->post('topup', 'topup', 'Top-up', [Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]);
        $this->ledger->post('reverse', 'reverse', 'Reversal', [Posting::debit($wallet, Money::ofCents(100)), Posting::credit($this->cash, Money::ofCents(100))]);
        $state = app(ModuleGate::class)->change('wallet', false, $this->superAdmin());
        $this->assertTrue($state['enabled']);
        $this->assertTrue($state['read_only']);
        $this->assertSame(0, $this->ledger->balance($wallet)->cents);
        $this->refuses(fn () => $this->ledger->post('again', 'again', 'Top-up', [Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]));
        $actor = $this->superAdmin();
        app(CurrentSchool::class)->run(2, function () use ($actor) {
            $state = app(ModuleGate::class)->change('wallet', false, $actor);
            $this->assertFalse($state['enabled']);
        });
    }

    public function test_account_definitions_are_idempotent_but_not_reassignable(): void
    {
        $this->assertSame($this->family, $this->ledger->openAccount(AccountType::Family, 'family:1', 'ZAR', $this->schoolA->ids['farid_user']));
        $this->refuses(fn () => $this->ledger->openAccount(AccountType::Family, 'family:1', 'USD', $this->schoolA->ids['farid_user']));
        $this->refuses(fn () => $this->ledger->openAccount(AccountType::Family, 'family:1', 'ZAR', $this->schoolA->ids['mona_user']));
    }

    public function test_wallet_cash_accounts_do_not_require_fees_and_cannot_be_reassigned_to_bypass_a_switch(): void
    {
        app(ModuleGate::class)->change('fees', false, $this->superAdmin());
        $cash = $this->ledger->openAccount(AccountType::CashDesk, 'cash:wallet', 'ZAR', module: 'wallet');
        $wallet = $this->ledger->openAccount(AccountType::Wallet, 'wallet:1', 'ZAR');
        $this->ledger->post('wallet:fund', 'wallet:fund', 'Wallet cash deposit', [Posting::debit($cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]);
        $this->assertSame(100, $this->ledger->balance($wallet)->cents);
        $this->assertSame('wallet', DB::table('ledger_accounts')->where('id', $cash)->value('module'));
        $this->refuses(fn () => $this->ledger->openAccount(AccountType::CashDesk, 'cash:wallet', 'ZAR', module: 'fees'));
        $this->refuses(fn () => $this->ledger->post('fees:cash', 'fees:cash', 'Wrong cash module', [Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]));
        app(ModuleGate::class)->change('wallet', false, $this->superAdmin());
        $this->refuses(fn () => $this->ledger->post('wallet:again', 'wallet:again', 'Disabled wallet deposit', [Posting::debit($cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]));
        $this->assertSame(1, DB::table('ledger_entries')->count());
    }

    public function test_package_removal_preserves_wallet_history_and_refuses_new_money(): void
    {
        $wallet = $this->ledger->openAccount(AccountType::Wallet, 'wallet:1', 'ZAR');
        $lines = [Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))];
        $this->ledger->post('topup', 'topup', 'Top-up', $lines);
        $actor = $this->superAdmin();
        DB::table('schools')->where('id', 1)->update(['package_key' => 'core']);
        app(CurrentSchool::class)->set(1);
        app(ModuleGate::class)->keepRetainedMoneyReadable($actor);
        $state = app(ModuleGate::class)->state('wallet');
        $this->assertTrue($state['enabled']);
        $this->assertTrue($state['read_only']);
        $this->assertSame(100, $this->ledger->balance($wallet)->cents);
        $this->refuses(fn () => $this->ledger->post('again', 'again', 'Top-up', $lines));
    }

    public function test_suspension_blocks_new_money_and_durable_keys_are_scoped_to_the_school(): void
    {
        $this->receipt('same-key', 100);
        app(CurrentSchool::class)->run(2, function () {
            $cash = $this->ledger->openAccount(AccountType::CashDesk, 'cash:desk', 'ZAR');
            $family = $this->ledger->openAccount(AccountType::Family, 'family:1', 'ZAR');
            $this->ledger->post('same-key', 'same-key', 'Recorded receipt', [Posting::debit($cash, Money::ofCents(50)), Posting::credit($family, Money::ofCents(50))]);
            $this->assertSame(50, $this->ledger->balance($family)->cents);
        });
        $this->assertSame(100, $this->ledger->balance($this->family)->cents);
        DB::table('schools')->where('id', 1)->update(['status' => 'suspended']);
        $this->refuses(fn () => $this->receipt('new-key', 100));
        $this->assertSame(100, $this->ledger->balance($this->family)->cents);
    }

    public function test_collection_accounts_exist_at_zero_and_cannot_post_in_this_phase(): void
    {
        foreach ([AccountType::Clearing, AccountType::DueToSchool] as $type) {
            $id = $this->ledger->openAccount($type, $type->value, 'ZAR');
            $this->refuses(fn () => $this->ledger->post($type->value, $type->value, 'Collection', [Posting::debit($this->cash, Money::ofCents(100)), Posting::credit($id, Money::ofCents(100))]));
            $this->assertSame(0, $this->ledger->balance($id)->cents);
        }
    }

    public function test_database_rejects_unbalanced_finalization_even_outside_the_service(): void
    {
        $id = $this->draft();
        DB::table('ledger_postings')->insert(['school_id' => 1, 'entry_id' => $id, 'account_id' => $this->cash, 'cents' => 100, 'direction' => 'debit']);
        $this->expectException(QueryException::class);
        DB::table('ledger_entries')->where('id', $id)->update(['posted_at' => now('UTC')]);
    }

    public function test_database_rejects_cross_currency_postings_and_cross_school_pairs(): void
    {
        $usd = $this->ledger->openAccount(AccountType::Family, 'usd', 'USD');
        $foreign = app(CurrentSchool::class)->run(2, fn () => $this->ledger->openAccount(AccountType::Family, 'foreign', 'ZAR'));
        foreach ([$usd, $foreign] as $account) {
            try {
                DB::table('ledger_postings')->insert(['school_id' => 1, 'entry_id' => $this->draft(), 'account_id' => $account, 'cents' => 100, 'direction' => 'credit']);
                $this->fail('Wrong posting context was accepted');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_database_seals_postings_and_history_including_case_only_mutations(): void
    {
        $id = $this->receipt('receipt', 100);
        foreach ([
            fn () => DB::table('ledger_entries')->where('id', $id)->update(['description' => 'RECORDED RECEIPT']),
            fn () => DB::table('ledger_entries')->where('id', $id)->delete(),
            fn () => DB::table('ledger_postings')->where('entry_id', $id)->update(['cents' => 101]),
            fn () => DB::table('ledger_postings')->where('entry_id', $id)->delete(),
            fn () => DB::table('ledger_postings')->insert(['school_id' => 1, 'entry_id' => $id, 'account_id' => $this->cash, 'cents' => 100, 'direction' => 'debit']),
            fn () => DB::table('ledger_accounts')->where('id', $this->family)->update(['owner_user_id' => $this->schoolA->ids['mona_user']]),
        ] as $change) {
            try {
                $change();
                $this->fail('Immutable ledger history changed');
            } catch (QueryException) {
                $this->assertTrue(true);
            }
        }
        $this->assertSame(100, $this->ledger->balance($this->family)->cents);
    }

    public function test_reconciliation_is_read_only_by_default_and_repairs_only_projections_explicitly(): void
    {
        CentsColumns::backfill();
        $this->receipt('receipt', 100);
        DB::table('ledger_accounts')->where('id', $this->family)->update(['balance_cents' => 90]);
        $this->artisan('ledger:reconcile --school=1 --json')->assertExitCode(1);
        $this->assertSame(90, (int) DB::table('ledger_accounts')->where('id', $this->family)->value('balance_cents'));
        $this->artisan('ledger:reconcile --school=1 --rebuild --json')->assertExitCode(0);
        $this->assertSame(100, (int) DB::table('ledger_accounts')->where('id', $this->family)->value('balance_cents'));
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(2, DB::table('ledger_postings')->count());
        $this->assertSame(100, $this->ledger->balance($this->family)->cents);
    }

    /** @return list<Posting> */
    private function lines(int $cents): array
    {
        return [Posting::debit($this->cash, Money::ofCents($cents, 'ZAR')), Posting::credit($this->family, Money::ofCents($cents, 'ZAR'))];
    }

    private function receipt(string $key, int $cents): int
    {
        return $this->ledger->post($key, $key, 'Recorded receipt', $this->lines($cents));
    }

    private function refuses(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Ledger operation should have been refused');
        } catch (DomainException) {
            $this->assertTrue(true);
        }
    }

    private function draft(): int
    {
        $key = (string) Str::uuid();

        return DB::table('ledger_entries')->insertGetId(['school_id' => 1, 'idempotency_key' => $key, 'source' => $key,
            'request_hash' => hash('sha256', $key), 'description' => 'Draft fixture', 'currency' => 'ZAR', 'created_at' => now('UTC')]);
    }
}
