<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\CashHandover;
use App\Models\Money\CashHandoverOutcome;
use App\Models\Money\WalletParentDeposit;
use App\Models\User;
use App\Services\Money\CashControlReconciliation;
use App\Services\Money\CashCustody;
use App\Services\Money\Cashups;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class CashCustodyTest extends TestCase
{
    use UsesTwoSchools;

    private User $finance;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->finance = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        app(ModuleGate::class)->change('wallet', true, $this->finance);
        app(ModuleGate::class)->change('fees', false, $this->finance);
        app(WalletSettings::class)->save($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletDeposits::class)->policy($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        $this->deposit('cash', '20.00');
        $this->deposit('eft', '60.00');
    }

    private function deposit(string $method, string $amount, ?string $date = null): void
    {
        $date ??= now()->toDateString();
        $claim = app(WalletDeposits::class)->claim($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'method' => $method, 'source_reference' => 'SYNTHETIC-CLAIM-'.Str::uuid(), 'paid_on' => $date, 'evidence' => 'Synthetic original actual parent deposit evidence', 'confirmed' => true]);
        app(WalletDeposits::class)->verify($this->finance, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'method' => $method, 'source_reference' => 'SYNTHETIC-VERIFIED-'.Str::uuid(), 'received_on' => $date, 'evidence' => 'Independent actual synthetic receipt held by this Finance operator', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
    }

    private function report(): array
    {
        return app(Cashups::class)->report($this->finance, (int) $this->finance->id, 'ZAR', now()->toDateString());
    }

    private function input(array $extra = []): array
    {
        $r = $this->report();

        return array_replace(['operation_id' => (string) Str::uuid(), 'currency' => 'ZAR', 'counted_on' => now()->toDateString(), 'revision' => $r['latest']?->revision ?? 0, 'source_hash' => $r['source_hash'], 'counted_amount' => '20.00', 'private_evidence' => 'Actual counted synthetic notes and coins at this desk', 'confirmed_actual_count' => true], $extra);
    }

    private function refuses(callable $work, int $status): void
    {
        try {
            $work();
            $this->fail('Expected private cash control refusal');
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (HttpExceptionInterface$e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    private function cashier(string $name = 'receiver'): User
    {
        $user = User::create(['first_name' => 'Synthetic', 'last_name' => $name, 'email' => $name.'@example.test', 'password' => Hash::make(Str::random(40)), 'status' => 1, 'password_change_required' => false]);
        $user->assignRole('Super Admin');
        app(MembershipService::class)->grant($user, 1, 'Super Admin');

        return $user;
    }

    private function proposal(User $recipient, string $amount = '12.00'): array
    {
        return ['operation_id' => (string) Str::uuid(), 'recipient_id' => $recipient->id, 'currency' => 'ZAR', 'amount' => $amount, 'private_evidence' => 'Original actual synthetic cash notes prepared for handover', 'confirmed' => true];
    }

    private function outcome(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'private_evidence' => 'Independently counted actual synthetic cash received at this desk', 'confirmed' => true];
    }

    public function test_proposal_reserves_actual_cash_and_acceptance_moves_custody_once_without_posting(): void
    {
        $other = $this->cashier();
        $s = app(CashCustody::class);
        $entries = DB::table('ledger_entries')->count();
        $input = $this->proposal($other);
        $row = $s->propose($this->finance, $input);
        $this->assertSame($row->id, $s->propose($this->finance, $input)->id);
        $this->assertSame(800, $s->available($this->finance->id, 'ZAR')['available_cents']);
        $this->assertSame(0, $s->available($other->id, 'ZAR')['available_cents']);
        $this->refuses(fn () => $s->propose($this->finance, $this->proposal($other)), 422);
        $this->refuses(fn () => $s->requirePayout($this->finance, 'ZAR', 801), 422);
        $op = $this->outcome();
        $result = $s->finish($other, $row->id, 'accepted', $op);
        $this->assertSame($result->id, $s->finish($other, $row->id, 'accepted', $op)->id);
        $this->refuses(fn () => $s->finish($other, $row->id, 'accepted', $this->outcome()), 409);
        $this->assertSame(800, $s->available($this->finance->id, 'ZAR')['available_cents']);
        $this->assertSame(1200, $s->available($other->id, 'ZAR')['available_cents']);
        $this->assertSame(0, $s->available($this->finance->id, 'ZAR')['reserved_cents']);
        $this->assertSame(800, $this->report()['closing_cents']);
        $this->assertSame(1200, app(Cashups::class)->report($other, $other->id, 'ZAR', now()->toDateString())['closing_cents']);
        $this->assertSame(2000, $this->report()['cash_asset_cents']);
        $this->assertSame($entries, DB::table('ledger_entries')->count());
        $this->assertSame(1, CashHandover::count());
        $this->assertSame(1, CashHandoverOutcome::count());
        $s->requirePayout($other, 'ZAR', 1200);
        $cash = app(CashControlReconciliation::class)->inspect();
        $this->assertTrue($cash['cash_control_healthy']);
        $this->assertSame(0, $cash['cash_currencies'][0]['custody_difference_cents']);
        $this->artisan('cash:reconcile', ['--school' => 1, '--json' => true])->assertExitCode(0);
    }

    public function test_only_original_sender_can_cancel_and_recipient_can_accept_or_reject(): void
    {
        $other = $this->cashier();
        $stranger = $this->cashier('unrelated');
        $s = app(CashCustody::class);
        $row = $s->propose($this->finance, $this->proposal($other));
        $this->refuses(fn () => $s->finish($this->finance, $row->id, 'accepted', $this->outcome()), 404);
        $this->refuses(fn () => $s->finish($other, $row->id, 'cancelled', $this->outcome()), 404);
        $this->refuses(fn () => $s->finish($stranger, $row->id, 'rejected', $this->outcome()), 404);
        $s->finish($other, $row->id, 'rejected', $this->outcome());
        $this->assertSame(2000, $s->available($this->finance->id, 'ZAR')['available_cents']);
        $next = $s->propose($this->finance, $this->proposal($other));
        $s->finish($this->finance, $next->id, 'cancelled', $this->outcome());
        $this->refuses(fn () => $s->finish($other, $next->id, 'accepted', $this->outcome()), 409);
        $this->assertSame(2000, $this->report()['closing_cents']);
        $this->assertSame(0, $s->available($other->id, 'ZAR')['available_cents']);
    }

    public function test_original_keys_are_bound_to_body_actor_and_operation_kind(): void
    {
        $other = $this->cashier();
        $s = app(CashCustody::class);
        $op = $this->proposal($other);
        $row = $s->propose($this->finance, $op);
        $this->refuses(fn () => $s->propose($this->finance, array_replace($op, ['amount' => '1.00'])), 409);
        $this->refuses(fn () => $s->propose($other, $op), 409);
        $this->refuses(fn () => $s->finish($other, $row->id, 'accepted', array_replace($this->outcome(), ['operation_id' => $op['operation_id']])), 409);
        $out = $this->outcome();
        $s->finish($other, $row->id, 'accepted', $out);
        $this->refuses(fn () => $s->propose($other, array_replace($this->proposal($this->finance, '1.00'), ['operation_id' => $out['operation_id']])), 409);
    }

    public function test_wrong_role_school_and_revoked_recipient_are_refused_before_replay(): void
    {
        $other = $this->cashier();
        $s = app(CashCustody::class);
        $this->refuses(fn () => $s->propose($this->parent, $this->proposal($other)), 403);
        $this->refuses(fn () => $s->propose($this->finance, $this->proposal($this->parent)), 403);
        $this->refuses(fn () => $s->propose($this->finance, $this->proposal($this->finance)), 422);
        $row = $s->propose($this->finance, $this->proposal($other));
        app(CurrentSchool::class)->run(2, function () use ($s, $row, $other): void {
            $this->refuses(fn () => $s->finish($other, $row->id, 'accepted', $this->outcome()), 403);
        });
        app(CurrentSchool::class)->run(2, function () use ($s, $row, $other): void {
            $other->assignRole('Super Admin');
            app(MembershipService::class)->grant($other, 2, 'Super Admin');
            $this->refuses(fn () => $s->finish($other, $row->id, 'accepted', $this->outcome()), 404);
        });
        $out = $this->outcome();
        $s->finish($other, $row->id, 'accepted', $out);
        app(MembershipService::class)->end($other, 1, 'Super Admin');
        $this->refuses(fn () => $s->finish($other, $row->id, 'accepted', $out), 403);
        $this->assertSame(1, CashHandoverOutcome::count());
    }

    public function test_recorded_shortage_caps_spend_and_pending_acceptance_until_reviewed(): void
    {
        $other = $this->cashier();
        $s = app(CashCustody::class);
        $row = $s->propose($this->finance, $this->proposal($other, '20.00'));
        app(Cashups::class)->record($this->finance, $this->input(['counted_amount' => '19.00', 'private_reason' => 'One rand physically missing and being investigated']));
        $this->refuses(fn () => $s->finish($other, $row->id, 'accepted', $this->outcome()), 409);
        $s->finish($this->finance, $row->id, 'cancelled', $this->outcome());
        $this->assertSame(1900, $s->available($this->finance->id, 'ZAR')['available_cents']);
        $this->refuses(fn () => $s->requirePayout($this->finance, 'ZAR', 1901), 422);
        $s->requirePayout($this->finance, 'ZAR', 1900);
        app(Cashups::class)->record($this->finance, $this->input(['private_reason' => 'Missing original note located and actual cash independently recounted']));
        $this->assertSame(2000, $s->available($this->finance->id, 'ZAR')['available_cents']);
        app(Cashups::class)->record($this->finance, $this->input(['counted_amount' => '21.00', 'private_reason' => 'Unidentified extra note held pending original source verification']));
        $this->assertSame(2000, $s->available($this->finance->id, 'ZAR')['available_cents']);
        $this->refuses(fn () => $s->requirePayout($this->finance, 'ZAR', 2001), 422);
    }

    public function test_handover_marks_counts_stale_and_backdating_cannot_borrow_later_receipts(): void
    {
        $other = $this->cashier();
        $s = app(CashCustody::class);
        app(Cashups::class)->record($this->finance, $this->input());
        $this->refuses(fn () => $s->requirePayout($this->finance, 'ZAR', 1, now()->subDay()->toDateString()), 422);
        $row = $s->propose($this->finance, $this->proposal($other));
        $s->finish($other, $row->id, 'accepted', $this->outcome());
        $this->assertTrue($this->report()['needs_review']);
        $this->refuses(fn () => $s->requirePayout($other, 'ZAR', 1, now()->subDay()->toDateString()), 422);
        app(ModuleGate::class)->change('wallet', false, $this->finance);
        $back = $s->propose($other, $this->proposal($this->finance, '2.00'));
        $s->finish($this->finance, $back->id, 'accepted', $this->outcome());
        $this->assertSame(1000, $s->available($other->id, 'ZAR')['available_cents']);
    }

    public function test_unknown_cash_movement_refuses_new_handover_and_acceptance_but_keeps_cancellation_available(): void
    {
        $other = $this->cashier();
        $s = app(CashCustody::class);
        $row = $s->propose($this->finance, $this->proposal($other));
        $cash = WalletParentDeposit::where('method', 'cash')->sole()->asset_account_id;
        DB::table('ledger_accounts')->where('id', $cash)->update(['balance_cents' => 2001]);
        $this->refuses(fn () => $s->propose($this->finance, $this->proposal($other, '1.00')), 409);
        $this->artisan('cash:reconcile', ['--school' => 1, '--json' => true])->assertExitCode(1);
        $this->refuses(fn () => $s->finish($other, $row->id, 'accepted', $this->outcome()), 409);
        $s->finish($this->finance, $row->id, 'cancelled', $this->outcome());
        $this->assertSame(0, $s->available($this->finance->id, 'ZAR')['reserved_cents']);
    }

    public function test_sql_history_and_actor_guards_and_retained_rollback(): void
    {
        $other = $this->cashier();
        $s = app(CashCustody::class);
        $row = $s->propose($this->finance, $this->proposal($other));
        try {
            CashHandoverOutcome::create(['handover_id' => $row->id, 'actor_id' => $this->finance->id, 'status' => 'accepted', 'effective_on' => now()->toDateString(), 'operation_id' => (string) Str::uuid(), 'request_hash' => str_repeat('a', 64), 'private_evidence' => 'Synthetic illegal sender attempting to confirm recipient cash', 'created_at' => now('UTC')]);
            $this->fail('SQL allowed sender to impersonate cash recipient');
        } catch (QueryException) {
            $this->assertSame(0, CashHandoverOutcome::count());
        }
        $out = $s->finish($other, $row->id, 'accepted', $this->outcome());
        foreach (['cash_handovers' => $row->id, 'cash_handover_outcomes' => $out->id] as $table => $id) {
            foreach (['UPDATE', 'DELETE'] as $action) {
                try {
                    DB::statement($action === 'UPDATE' ? "UPDATE $table SET private_evidence='changed' WHERE id=?" : "DELETE FROM $table WHERE id=?", [$id]);
                    $this->fail('Cash custody history changed');
                } catch (QueryException) {
                    $this->assertSame(1, DB::table($table)->count());
                }
            }
        }
        $this->assertStringNotContainsString('synthetic', DB::table('cash_handovers')->value('private_evidence'));
        $m = require database_path('migrations/2026_10_28_220000_add_confirmed_cash_custody_handovers.php');
        try {
            $m->down();
            $this->fail('Retained handover history dropped');
        } catch (\RuntimeException) {
            $this->assertSame(1, CashHandover::count());
        }
    }

    public function test_private_panel_binds_actor_and_school_and_hides_unrelated_handovers(): void
    {
        $other = $this->cashier();
        $stranger = $this->cashier('unrelated');
        $input = $this->proposal($other);
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->finance->id];
        $this->actingAs($this->finance)->get('/cashups/handovers')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/cashups/handovers', array_replace($input + $bind, ['expected_school_id' => 2]))->assertForbidden();
        $this->post('/cashups/handovers', $input + $bind)->assertRedirect();
        $row = CashHandover::sole();
        $this->actingAs($stranger)->get('/cashups/handovers')->assertOk()->assertDontSee('Original actual synthetic cash notes');
        $url = '/cashups/handovers/'.$row->id.'/accepted';
        $this->post($url, $this->outcome() + ['expected_school_id' => 1, 'expected_account_id' => $stranger->id])->assertNotFound();
        $this->actingAs($other)->post($url, array_replace($this->outcome(), ['confirmed' => false]) + ['expected_school_id' => 1, 'expected_account_id' => $other->id])->assertStatus(422)->assertSessionMissing('_old_input');
        $out = $this->outcome() + ['expected_school_id' => 1, 'expected_account_id' => $other->id];
        $this->post($url, $out)->assertRedirect();
        $this->post($url, $out)->assertRedirect();
        $this->assertSame(1, CashHandoverOutcome::count());
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/cashups/handovers')->assertForbidden();
    }

    public function test_backdated_payout_cannot_unfund_an_intervening_confirmed_handover(): void
    {
        $s = app(CashCustody::class);
        $other = $this->cashier();
        $paidOn = now()->toDateString();
        $this->travelTo(now()->addDay());
        $row = $s->propose($this->finance, $this->proposal($other, '20.00'));
        $s->finish($other, $row->id, 'accepted', $this->outcome());
        $this->travelTo(now()->addDay());
        $this->deposit('cash', '20.00');
        $this->assertSame(2000, $s->available($this->finance->id, 'ZAR')['available_cents']);
        $this->assertSame(2000, $s->available($this->finance->id, 'ZAR', $paidOn)['available_cents']);
        $this->refuses(fn () => $s->requirePayout($this->finance, 'ZAR', 1, $paidOn), 422);
        $s->requirePayout($this->finance, 'ZAR', 2000);
        $this->assertSame(2000, $s->available($other->id, 'ZAR')['available_cents']);
    }

    public function test_backdated_payout_uses_minimum_custody_without_reserving_future_proposals_on_past_days(): void
    {
        $s = app(CashCustody::class);
        $other = $this->cashier();
        $paidOn = now()->toDateString();
        $this->travelTo(now()->addDay());
        $row = $s->propose($this->finance, $this->proposal($other, '12.00'));
        $s->finish($other, $row->id, 'accepted', $this->outcome());
        $this->travelTo(now()->addDay());
        $this->deposit('cash', '20.00');
        $s->propose($this->finance, $this->proposal($other, '20.00'));
        $this->assertSame(800, $s->available($this->finance->id, 'ZAR')['available_cents']);
        $s->requirePayout($this->finance, 'ZAR', 800, $paidOn);
        $this->refuses(fn () => $s->requirePayout($this->finance, 'ZAR', 801, $paidOn), 422);
        $this->assertSame(2000, $s->available($this->finance->id, 'ZAR')['reserved_cents']);
    }

    public function test_historical_receipts_rebase_shortage_without_delivering_new_cash_after_a_count(): void
    {
        $count = app(Cashups::class)->record($this->finance, $this->input(['counted_amount' => '19.00', 'private_reason' => 'One original rand physically missing at the count']));
        $countedOn = now()->toDateString();
        $this->travel(2)->days();
        $this->deposit('cash', '5.00', $countedOn);
        $service = app(CashCustody::class);
        $available = $service->available($this->finance->id, 'ZAR');
        $this->assertSame(2500, $available['book_cents']);
        $this->assertSame(-600, $available['shortage_cents']);
        $this->assertSame(1900, $available['available_cents']);
        $this->assertSame(-100, $count->fresh()->variance_cents);
        $this->refuses(fn () => $service->requirePayout($this->finance, 'ZAR', 1901), 422);
        $this->deposit('cash', '3.00');
        $this->assertSame(2200, $service->available($this->finance->id, 'ZAR')['available_cents']);
        $this->refuses(fn () => $service->requirePayout($this->finance, 'ZAR', 1901, $countedOn), 422);
        $service->requirePayout($this->finance, 'ZAR', 2200);
        $this->assertTrue(app(CashControlReconciliation::class)->inspect()['cash_control_healthy']);
    }

    public function test_historical_receipt_cannot_restore_a_pending_handover_beyond_actual_counted_cash(): void
    {
        $recipient = $this->cashier();
        $service = app(CashCustody::class);
        $handover = $service->propose($this->finance, $this->proposal($recipient, '19.50'));
        app(Cashups::class)->record($this->finance, $this->input(['counted_amount' => '19.00', 'private_reason' => 'Original actual shortage detected before independent handover acceptance']));
        $countedOn = now()->toDateString();
        $this->travel(2)->days();
        $this->deposit('cash', '5.00', $countedOn);
        $this->refuses(fn () => $service->finish($recipient, $handover->id, 'accepted', $this->outcome()), 409);
        $this->assertSame(0, CashHandoverOutcome::count());
        $service->finish($recipient, $handover->id, 'rejected', $this->outcome());
        $this->assertSame(1900, $service->available($this->finance->id, 'ZAR')['available_cents']);
    }
}
