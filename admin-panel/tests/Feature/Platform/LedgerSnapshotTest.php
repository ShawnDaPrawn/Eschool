<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Real committed fixtures: an outer fixture transaction would hide writer data. */
class LedgerSnapshotTest extends TestCase
{
    private const WRITER = 'ledger_snapshot_writer';

    private string $primary;

    private LedgerService $ledger;

    private int $cash;

    private int $family;

    protected function setUp(): void
    {
        parent::setUp();
        $this->primary = DB::getDefaultConnection();
        // Immutable history deliberately prevents migrate:rollback. Each test
        // recreates the isolated *_test schema instead of deleting sealed rows.
        Artisan::call('migrate:fresh', ['--force' => true]);
        DB::table('schools')->updateOrInsert(['id' => 1], ['name' => 'Snapshot School', 'slug' => 'snapshot-school',
            'status' => 'active', 'timezone' => 'UTC', 'package_key' => 'snapshot-ledger']);
        config(['modules.fees' => ['name' => 'Fees', 'money' => true, 'default' => true, 'dependencies' => []],
            'school_packages.snapshot-ledger' => ['fees'], 'school_packages.snapshot-without-fees' => []]);
        DB::table('school_modules')->updateOrInsert(['school_id' => 1, 'module' => 'fees'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
        app(CurrentSchool::class)->set(1);
        app(ModuleGate::class)->forget();
        $this->assertTrue(app(ModuleGate::class)->writable('fees'));
        $this->ledger = app(LedgerService::class);
        $this->cash = $this->ledger->openAccount(AccountType::CashDesk, 'snapshot:cash', 'ZAR');
        $this->family = $this->ledger->openAccount(AccountType::Family, 'snapshot:family', 'ZAR');
        $this->assertSame(0, DB::connection()->transactionLevel());

        $writer = config('database.connections.'.$this->primary);
        $writer['options'][\PDO::ATTR_PERSISTENT] = false;
        config(['database.connections.'.self::WRITER => $writer]);
        DB::purge(self::WRITER);
        $this->assertNotSame(
            (int) DB::connection($this->primary)->selectOne('SELECT CONNECTION_ID() AS id')->id,
            (int) DB::connection(self::WRITER)->selectOne('SELECT CONNECTION_ID() AS id')->id,
        );
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->primary);
        while (DB::connection($this->primary)->transactionLevel() > 0) {
            DB::connection($this->primary)->rollBack();
        }
        DB::purge(self::WRITER);
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    private function receipt(string $key, int $cents): int
    {
        return $this->ledger->post($key, $key, 'Synthetic recorded receipt', [
            Posting::debit($this->cash, Money::ofCents($cents, 'ZAR')),
            Posting::credit($this->family, Money::ofCents($cents, 'ZAR')),
        ]);
    }

    private function staleSnapshot(callable $otherCommit, callable $afterCommit): void
    {
        $reader = DB::connection($this->primary);
        $reader->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $reader->beginTransaction();
        try {
            // Only consistent READS occur on the reader before the other
            // connection commits. No school/account write lock is held here.
            $this->assertSame(0, $reader->table('ledger_entries')->count());
            $this->assertSame(0, (int) $reader->table('ledger_accounts')->where('id', $this->family)->value('balance_cents'));
            $this->assertSame('snapshot-ledger', $reader->table('schools')->where('id', 1)->value('package_key'));
            DB::setDefaultConnection(self::WRITER);
            try {
                $committed = $otherCommit();
                $this->assertSame(0, DB::connection(self::WRITER)->transactionLevel());
            } finally {
                DB::setDefaultConnection($this->primary);
            }
            // The original snapshot is still old, despite the completed writer.
            $this->assertSame(0, $reader->table('ledger_entries')->count());
            $this->assertSame(0, (int) $reader->table('ledger_accounts')->where('id', $this->family)->value('balance_cents'));
            $afterCommit($committed);
            $reader->commit();
        } finally {
            DB::setDefaultConnection($this->primary);
            while ($reader->transactionLevel() > 0) {
                $reader->rollBack();
            }
        }
    }

    private function refusesReceipt(): void
    {
        try {
            $this->receipt('snapshot:new-after-revocation', 50);
            $this->fail('A revoked module/package accepted a new monetary effect.');
        } catch (DomainException $error) {
            $this->assertNotSame('', $error->getMessage());
        }
    }

    public function test_exact_replay_finds_the_committed_receipt_outside_the_old_snapshot(): void
    {
        $this->staleSnapshot(fn () => $this->receipt('snapshot:receipt:one', 100), function (int $id): void {
            $this->assertSame($id, $this->receipt('snapshot:receipt:one', 100));
        });
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(2, DB::table('ledger_postings')->count());
        $this->assertSame(100, $this->ledger->balance($this->family)->cents);
    }

    public function test_new_legitimate_post_uses_current_history_after_another_committed_receipt(): void
    {
        $this->staleSnapshot(fn () => $this->receipt('snapshot:receipt:one', 100), function (int $first): void {
            $this->assertNotSame($first, $this->receipt('snapshot:receipt:two', 75));
        });
        $this->assertSame(2, DB::table('ledger_entries')->count());
        $this->assertSame(4, DB::table('ledger_postings')->count());
        $this->assertSame(175, $this->ledger->balance($this->family)->cents);
        $this->assertSame([], $this->ledger->projectionDifferences());
    }

    public function test_module_readonly_change_is_current_even_with_cached_and_snapshot_enabled_state(): void
    {
        $this->staleSnapshot(function (): void {
            DB::transaction(function (): void {
                DB::table('schools')->where('id', 1)->lockForUpdate()->first();
                DB::table('school_modules')->where('school_id', 1)->where('module', 'fees')->update(['enabled' => true, 'read_only' => true]);
            });
        }, function (): void {
            $this->assertFalse((bool) DB::table('school_modules')->where('school_id', 1)->where('module', 'fees')->value('read_only'));
            $this->refusesReceipt();
        });
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, $this->ledger->balance($this->family)->cents);
    }

    public function test_package_removal_is_current_even_when_the_school_object_and_snapshot_are_old(): void
    {
        $this->staleSnapshot(function (): void {
            DB::transaction(function (): void {
                DB::table('schools')->where('id', 1)->lockForUpdate()->first();
                DB::table('schools')->where('id', 1)->update(['package_key' => 'snapshot-without-fees']);
            });
        }, function (): void {
            $this->assertSame('snapshot-ledger', DB::table('schools')->where('id', 1)->value('package_key'));
            $this->refusesReceipt();
        });
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, $this->ledger->balance($this->family)->cents);
    }
}
