<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletOperation;
use App\Models\Money\WalletTransfer;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\User;
use App\Services\Money\WalletDeskTopups;
use App\Services\Money\WalletFundingService;
use App\Services\Money\WalletOperations;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletTransferReconciliation;
use App\Services\Money\WalletTransfers;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class WalletTransfersTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private User $child;

    private WalletAccount $own;

    private WalletAccount $childWallet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->child = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'can_approve' => true, 'can_see_fees' => false, 'relationship' => 'guardian']);
        LearnerApproval::firstOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletFundingService::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        app(WalletFundingService::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'method' => 'cash', 'source_reference' => 'SYNTHETIC-TRANSFER-BACKING', 'amount' => '100.00', 'received_on' => now()->toDateString(), 'school_owned' => true, 'evidence' => 'Synthetic school-owned transfer test backing']);
        app(WalletDeskTopups::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'threshold_amount' => '100.00']);
        app(WalletDeskTopups::class)->grant($this->admin, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $this->parent->id, 'amount' => '50.00', 'reason' => 'Synthetic school-funded adult test wallet', 'confirmed' => true]);
        $this->own = WalletAccount::where('user_id', $this->parent->id)->sole();
        $this->childWallet = app(Wallets::class)->open($this->parent, ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $this->child->id]);
    }

    private function input(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'from_wallet_id' => $this->own->id, 'to_wallet_id' => $this->childWallet->id, 'amount' => '10.25', 'confirmed' => true], $change);
    }

    private function allowReturns(): void
    {
        app(WalletTransfers::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'child_returns' => 'current_approving_guardian', 'confirmed' => true]);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected wallet transfer refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_verified_parent_transfers_once_without_fee_access_and_preserves_exact_balances(): void
    {
        $input = $this->input();
        $row = app(WalletTransfers::class)->transfer($this->parent, $input);
        $this->assertSame($row->id, app(WalletTransfers::class)->transfer($this->parent, $input)->id);
        $this->assertSame(1, WalletTransfer::count());
        $this->assertSame(3975, app(LedgerService::class)->balance($this->own->ledger_account_id)->cents);
        $this->assertSame(1025, app(LedgerService::class)->balance($this->childWallet->ledger_account_id)->cents);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(WalletTransferReconciliation::healthy(app(WalletTransferReconciliation::class)->inspect()));
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, array_replace($input, ['amount' => '11.00'])), 409);
    }

    public function test_child_returns_require_explicit_school_policy_and_return_to_the_acting_parent_only(): void
    {
        app(WalletTransfers::class)->transfer($this->parent, $this->input());
        $input = $this->input(['from_wallet_id' => $this->childWallet->id, 'to_wallet_id' => $this->own->id, 'amount' => '4.15']);
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $input), 422);
        $this->allowReturns();
        $row = app(WalletTransfers::class)->transfer($this->parent, $input);
        $this->assertSame('to_parent', $row->kind);
        $this->assertSame(1, $row->policy_revision);
        $this->assertSame(4390, app(LedgerService::class)->balance($this->own->ledger_account_id)->cents);
        $this->assertSame(610, app(LedgerService::class)->balance($this->childWallet->ledger_account_id)->cents);
    }

    public function test_missing_parent_verification_learner_teacher_and_third_party_are_refused(): void
    {
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->child, $this->input()), 403);
        $this->refuses(fn () => app(WalletTransfers::class)->transfer(User::findOrFail($this->schoolA->ids['tara_user']), $this->input()), 403);
        $this->parent->forceFill(['email_verified_at' => null])->save();
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $this->input()), 403);
        $this->assertSame(0, WalletTransfer::count());
    }

    public function test_fresh_guardian_revocation_prevents_even_permanent_and_http_replay(): void
    {
        $input = $this->input();
        Sanctum::actingAs($this->parent);
        $headers = ['X-School-Id' => '1', 'Idempotency-Key' => $input['operation_id']];
        $response = $this->postJson('/api/v2/wallets/transfers', $input, $headers)->assertOk()->assertJsonPath('data.amount_cents', 1025);
        $response = $this->getJson('/api/v2/wallets/transfers', ['X-School-Id' => '1'])->assertOk();
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['can_approve' => false]);
        $this->postJson('/api/v2/wallets/transfers', $input, $headers)->assertNotFound();
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $input), 404);
        $this->assertSame(1, WalletTransfer::count());
        $this->assertSame(1, app(WalletTransfers::class)->index($this->parent)['total']);
    }

    public function test_overdraft_fractional_cents_wrong_wallet_and_changed_policy_roll_back(): void
    {
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $this->input(['amount' => '50.01'])), 422);
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $this->input(['amount' => '1.001'])), 422);
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $this->input(['to_wallet_id' => $this->own->id])), 422);
        $this->assertSame(0, WalletTransfer::count());
        $this->assertSame(5000, app(LedgerService::class)->balance($this->own->ledger_account_id)->cents);
    }

    public function test_interrupted_uncommitted_operation_is_permanently_closed_before_late_retry(): void
    {
        $input = $this->input();
        $before = DB::table('ledger_entries')->count();
        $result = app(WalletOperations::class)->resolve($this->parent, $input['operation_id']);
        $this->assertSame('abandoned', $result['status']);
        $this->assertSame($result, app(WalletOperations::class)->resolve($this->parent, $input['operation_id']));
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $input), 409);
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertSame(0, WalletTransfer::count());
    }

    public function test_resolving_committed_outcome_does_not_repost_or_disclose_private_receipt_to_another_actor(): void
    {
        $input = $this->input();
        app(WalletTransfers::class)->transfer($this->parent, $input);
        $this->assertSame('committed', app(WalletOperations::class)->resolve($this->parent, $input['operation_id'])['status']);
        $this->refuses(fn () => app(WalletOperations::class)->resolve($this->child, $input['operation_id']), 404);
        $this->assertSame(1, WalletTransfer::count());
        $this->assertSame(1, WalletOperation::where('operation_id', $input['operation_id'])->count());
    }

    public function test_disabled_wallet_history_and_recovery_remain_readable_but_cannot_transfer(): void
    {
        $input = $this->input();
        app(WalletTransfers::class)->transfer($this->parent, $input);
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        $this->assertFalse(app(WalletTransfers::class)->index($this->parent)['can_transfer']);
        Sanctum::actingAs($this->parent);
        $this->postJson('/api/v2/wallets/operations/'.$input['operation_id'].'/resolve', [], ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.status', 'committed');
        $this->postJson('/api/v2/wallets/transfers', $this->input(), ['X-School-Id' => '1', 'Idempotency-Key' => (string) Str::uuid()])->assertForbidden();
    }

    public function test_wrong_school_cannot_read_move_or_resolve_another_schools_money(): void
    {
        $input = $this->input();
        app(WalletTransfers::class)->transfer($this->parent, $input);
        app(CurrentSchool::class)->set(2);
        $this->refuses(fn () => app(WalletTransfers::class)->index($this->parent), 403);
        $this->refuses(fn () => app(WalletOperations::class)->resolve($this->parent, $input['operation_id']), 403);
        $this->refuses(fn () => app(WalletTransfers::class)->transfer($this->parent, $input), 403);
        app(CurrentSchool::class)->set(1);
        $this->assertSame(1, WalletTransfer::count());
    }

    public function test_sql_retains_transfer_history_and_reconciliation_detects_unattributed_movements(): void
    {
        $row = app(WalletTransfers::class)->transfer($this->parent, $this->input());
        try {
            DB::table('wallet_transfers')->where('id', $row->id)->update(['amount_cents' => 1]);
            $this->fail('Transfer was changed');
        } catch (QueryException) {
            $this->assertSame(1025, $row->fresh()->amount_cents);
        }
        $source = 'wallet-transfer:unattributed';
        app(LedgerService::class)->post($source, $source, 'Synthetic unattributed transfer', [Posting::debit($this->own->ledger_account_id, Money::ofCents(1, 'ZAR')), Posting::credit($this->childWallet->ledger_account_id, Money::ofCents(1, 'ZAR'))]);
        $this->assertSame(1, app(WalletTransferReconciliation::class)->inspect()['unattributed_wallet_transfers']);
        $m = require database_path('migrations/2026_10_28_130000_add_parent_wallet_transfers.php');
        $this->expectException(\RuntimeException::class);
        $m->down();
    }
}
