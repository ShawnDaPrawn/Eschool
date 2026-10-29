<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletSetting;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Money\WalletFinanceOverview;
use App\Services\Money\Wallets;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Ledger\AccountType;
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
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class PersonalWalletsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private User $learner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->learner = User::findOrFail($this->schoolA->ids['asha_user']);
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'can_approve' => true, 'can_see_fees' => false, 'relationship' => 'guardian']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        LearnerApproval::firstOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
    }

    public function test_zero_personal_wallet_and_own_statement_work_without_fees_and_open_once(): void
    {
        $this->settings();
        $input = $this->openInput($this->parent);
        $wallet = app(Wallets::class)->open($this->parent, $input);
        $this->assertSame($wallet->id, app(Wallets::class)->open($this->parent, $input)->id);
        $this->assertSame($wallet->id, app(Wallets::class)->open($this->parent, $this->openInput($this->parent))->id);
        $statement = app(Wallets::class)->statement($this->parent, $wallet->id);
        $this->assertSame(0, $statement['balance_cents']);
        $this->assertSame([], $statement['entries']['items']);
        $this->assertFalse($statement['read_only']);
        $this->assertFalse(app(ModuleGate::class)->writable('fees'));
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(1, WalletAccount::count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_verified_guardian_controls_child_independently_of_fee_visibility_and_learner_cannot_raise_limits(): void
    {
        $this->settings();
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->learner));
        $input = $this->limits(['all_categories' => false, 'categories' => ['food', 'books']]);
        $limit = app(Wallets::class)->controls($this->parent, $wallet->id, $input);
        $this->assertSame(['books', 'food'], $limit->categories);
        $this->assertSame($limit->id, app(Wallets::class)->controls($this->parent, $wallet->id, $input)->id);
        $this->refuses(fn () => app(Wallets::class)->controls($this->learner, $wallet->id, $this->limits(['revision' => 1])), 403);
        $this->assertSame(1000, app(Wallets::class)->statement($this->learner, $wallet->id)['limits']['effective_daily_spend_cents']);
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['can_approve' => false]);
        $this->refuses(fn () => app(Wallets::class)->controls($this->parent, $wallet->id, $input), 404);
        $this->refuses(fn () => app(Wallets::class)->statement($this->parent, $wallet->id), 404);
    }

    public function test_other_family_and_teacher_cannot_view_or_edit_child_wallet(): void
    {
        $this->settings();
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->learner));
        foreach (['gina', 'tara'] as $name) {
            $actor = User::findOrFail($this->schoolA->ids[$name.'_user']);
            $actor->forceFill(['email_verified_at' => now()])->save();
            $this->refuses(fn () => app(Wallets::class)->statement($actor, $wallet->id), $name === 'gina' ? 404 : 403);
            $this->refuses(fn () => app(Wallets::class)->controls($actor, $wallet->id, $this->limits()), $name === 'gina' ? 404 : 403);
        }
    }

    public function test_school_maximum_revisions_operation_mismatch_and_currency_are_explicit(): void
    {
        $this->refuses(fn () => app(Wallets::class)->open($this->parent, $this->openInput($this->parent)), 422);
        $this->settings();
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->parent));
        $input = $this->limits();
        app(Wallets::class)->controls($this->parent, $wallet->id, $input);
        $this->refuses(fn () => app(Wallets::class)->controls($this->parent, $wallet->id, $this->limits()), 409);
        $this->refuses(fn () => app(Wallets::class)->controls($this->parent, $wallet->id, [...$input, 'daily_spend_cents' => 1001]), 409);
        $this->refuses(fn () => app(Wallets::class)->controls($this->parent, $wallet->id, $this->limits(['revision' => 1, 'daily_spend_cents' => 5001])), 422);
        $this->refuses(fn () => $this->settings(['revision' => 1, 'currency' => 'USD']), 409);
        $this->assertSame('ZAR', WalletSetting::sole()->currency);
    }

    public function test_unapproved_learner_and_unverified_guardian_cannot_open_or_manage_child_wallet(): void
    {
        $this->settings();
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->refuses(fn () => app(Wallets::class)->open($this->parent, $this->openInput($this->learner)), 403);
        $this->refuses(fn () => app(Wallets::class)->open($this->learner, $this->openInput($this->learner)), 403);
        $this->parent->forceFill(['email_verified_at' => null])->save();
        $this->refuses(fn () => app(Wallets::class)->open($this->parent, $this->openInput($this->learner)), 403);
        $this->assertSame(0, WalletAccount::count());
    }

    public function test_inactive_child_history_remains_guardian_readable_without_allowing_new_controls(): void
    {
        $this->settings();
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->learner));
        $this->learner->forceFill(['status' => 0])->save();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->learner->id)->update(['status' => 'ended', 'ends_on' => '2026-06-14']);
        $this->assertSame($wallet->id, app(Wallets::class)->statement($this->parent, $wallet->id)['id']);
        $this->refuses(fn () => app(Wallets::class)->controls($this->parent, $wallet->id, $this->limits()), 403);
    }

    public function test_disabled_wallet_is_read_only_including_replays_and_other_school_cannot_read_it(): void
    {
        $this->settings();
        $input = $this->openInput($this->parent);
        $wallet = app(Wallets::class)->open($this->parent, $input);
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        $this->assertTrue(app(Wallets::class)->statement($this->parent, $wallet->id)['read_only']);
        $this->refuses(fn () => app(Wallets::class)->open($this->parent, $input), 403);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($wallet): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            app(ModuleGate::class)->change('wallet', true, $actor);
            $this->refuses(fn () => app(Wallets::class)->statement($actor, $wallet->id), 404);
            $this->api($actor)->withHeader('X-School-Id', '2')->getJson('/api/v2/wallets/'.$wallet->id.'/statement')->assertNotFound();
        });
    }

    public function test_finance_permission_and_wallet_sql_identity_are_preserved(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(WalletSettings::class)->save($teacher, $this->settingsInput()), 403);
        $this->settings();
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->parent));
        foreach ([fn () => DB::table('wallet_accounts')->update(['user_id' => $teacher->id]), fn () => DB::table('wallet_accounts')->delete(), fn () => DB::table('wallet_operations')->update(['actor_id' => $teacher->id]), fn () => DB::table('wallet_settings')->update(['revision' => 3])] as $write) {
            try {
                $write();
                $this->fail('Expected retained wallet identity');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_api_issues_zero_wallet_with_permanent_and_http_replay_and_own_scope_contract(): void
    {
        $this->settings();
        $this->api($this->parent)->getJson('/api/v2/wallets')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.actor_user_id', (int) $this->parent->id)->assertJsonPath('data.currency', 'ZAR');
        $input = $this->openInput($this->parent);
        $key = (string) Str::uuid();
        $this->api($this->parent)->withHeader('Idempotency-Key', $key)->postJson('/api/v2/wallets', $input)->assertOk()->assertJsonPath('data.balance_cents', 0)->assertJsonPath('data.owner_user_id', (int) $this->parent->id)->assertJsonPath('data.can_manage_limits', true);
        $wallet = WalletAccount::sole();
        $this->api($this->parent)->withHeader('Idempotency-Key', $key)->postJson('/api/v2/wallets', $input)->assertOk()->assertJsonPath('data.id', (int) $wallet->id);
        $this->assertSame(1, WalletAccount::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_api_guardian_revocation_and_disabled_module_apply_before_cached_control_replay(): void
    {
        $this->settings();
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->learner));
        $url = '/api/v2/wallets/'.$wallet->id.'/limits';
        $input = $this->limits();
        $key = (string) Str::uuid();
        $this->api($this->parent)->withHeader('Idempotency-Key', $key)->putJson($url, $input)->assertOk()->assertJsonPath('data.limits.daily_spend_cents', 1000);
        $this->api($this->learner)->withHeader('Idempotency-Key', (string) Str::uuid())->putJson($url, $this->limits(['revision' => 1]))->assertForbidden();
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['can_approve' => false]);
        $this->api($this->parent)->withHeader('Idempotency-Key', $key)->putJson($url, $input)->assertNotFound();
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['can_approve' => true]);
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        $this->api($this->parent)->getJson('/api/v2/wallets/'.$wallet->id.'/statement')->assertOk()->assertJsonPath('data.read_only', true)->assertJsonPath('data.can_manage_limits', false);
        $this->api($this->parent)->withHeader('Idempotency-Key', $key)->putJson($url, $input)->assertForbidden();
    }

    public function test_statement_pages_keep_running_cents_and_hide_counterparty_evidence(): void
    {
        $this->settings();
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->parent));
        $ledger = app(LedgerService::class);
        $asset = $ledger->openAccount(AccountType::CashDesk, 'wallet:synthetic-cash', 'ZAR', null, 'wallet');
        $ledger->post('synthetic-wallet-fund', 'synthetic-wallet-fund', 'CONFIDENTIAL counterpart bank evidence', [Posting::debit($asset, Money::ofCents(5000, 'ZAR')), Posting::credit($wallet->ledger_account_id, Money::ofCents(5000, 'ZAR'))]);
        $ledger->post('synthetic-wallet-use', 'synthetic-wallet-use', 'CONFIDENTIAL counterparty name', [Posting::debit($wallet->ledger_account_id, Money::ofCents(100, 'ZAR')), Posting::credit($asset, Money::ofCents(100, 'ZAR'))]);
        $url = '/api/v2/wallets/'.$wallet->id.'/statement?per_page=1';
        $response = $this->api($this->parent)->getJson($url)->assertOk()->assertJsonPath('data.balance_cents', 4900)->assertJsonPath('data.entries.total', 2)->assertJsonPath('data.entries.items.0.balance_after_cents', 4900)->assertJsonPath('data.entries.items.0.change_cents', -100);
        $this->assertStringNotContainsString('CONFIDENTIAL', $response->getContent());
        $this->assertStringNotContainsString('synthetic-cash', $response->getContent());
        $this->api($this->parent)->getJson($url.'&page=2')->assertOk()->assertJsonPath('data.entries.items.0.balance_after_cents', 5000)->assertJsonPath('data.entries.items.0.change_cents', 5000);
    }

    public function test_finance_panel_requires_its_own_permission_and_binds_actor_school_and_current_rules(): void
    {
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->get('/wallet-settings')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $input = $this->settingsInput() + ['daily_spend_amount' => '50.00'];
        $this->post('/wallet-settings', $input + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/wallet-settings', $input + $bind)->assertStatus(303);
        $wallet = app(Wallets::class)->open($this->parent, $this->openInput($this->parent));
        $this->get('/wallet-settings')->assertOk()->assertSee($this->parent->first_name);
        $this->get('/wallet-settings/'.$wallet->id.'/statement')->assertOk()->assertSee('Wallet balance');
        $this->post('/wallet-settings', [...$this->settingsInput(['revision' => 1]), 'daily_spend_amount' => 'bad'] + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->actingAs($teacher)->get('/wallet-settings')->assertForbidden();
        $this->get('/wallet-settings/'.$wallet->id.'/statement')->assertForbidden();
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('synthetic-wallet')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    public function test_finance_page_projection_preserves_child_caps_and_checks_raw_balances(): void
    {
        $this->settings();
        $service = app(Wallets::class);
        $parent = $service->open($this->parent, $this->openInput($this->parent));
        $child = $service->open($this->parent, $this->openInput($this->learner));
        $wallets = WalletAccount::whereIn('id', [$parent->id, $child->id])->get();
        $projection = app(WalletFinanceOverview::class);
        $rows = $projection->read($this->admin, $wallets);
        foreach ($wallets as $wallet) {
            $detail = $service->summary($wallet);
            $this->assertSame($detail['owner_name'], $rows[$wallet->id]['owner_name']);
            $this->assertSame($detail['balance_cents'], $rows[$wallet->id]['balance_cents']);
            $this->assertSame($detail['limits']['effective_daily_spend_cents'], $rows[$wallet->id]['limits']['effective_daily_spend_cents']);
        }
        $this->assertSame(0, $rows[$child->id]['limits']['effective_daily_spend_cents']);
        $this->refuses(fn () => $projection->read($this->parent, $wallets), 403);
        app(CurrentSchool::class)->run(2, function () use ($projection, $wallets): void {
            $this->refuses(fn () => $projection->read($this->admin, $wallets), 403);
        });
        DB::table('ledger_accounts')->where('id', $parent->ledger_account_id)->update(['balance_cents' => 1]);
        $this->refuses(fn () => $projection->read($this->admin, $wallets), 409);
    }

    private function settings(array $changes = []): void
    {
        app(WalletSettings::class)->save($this->admin, $this->settingsInput($changes));
    }

    private function settingsInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true], $changes);
    }

    private function openInput(User $owner): array
    {
        return ['operation_id' => (string) Str::uuid(), 'owner_user_id' => $owner->id];
    }

    private function limits(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'daily_spend_cents' => 1000, 'all_categories' => true, 'categories' => []], $changes);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected wallet refusal');
        } catch (ValidationException $e) {
            $this->assertSame(422, $status, json_encode($e->errors()));
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }
}
