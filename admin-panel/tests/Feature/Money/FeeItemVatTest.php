<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeItem;
use App\Models\Money\FeeTaxSetting;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeAccountingSettings;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeeCharges;
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeeItems;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeeReporting;
use App\Services\Money\FeeTaxSettings;
use App\Services\Money\ParentFeeStatements;
use App\Services\ParentPolicyService;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
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

class FeeItemVatTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    private FeeAccount $account;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        Enrolment::where('session_year_id', $this->year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic receipt allocation']);
        }
        foreach (['asha', 'cara'] as $learner) {
            foreach (['farid', 'mona'] as $parent) {
                GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->schoolA->ids[$parent.'_user']],
                    ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
            }
        }
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['gina_user']],
            ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        $this->account = app(FeeAccounts::class)->save($this->admin, $this->family());
    }

    public function test_inclusive_family_vat_preserves_gross_net_tax_and_each_holders_original_vat(): void
    {
        $this->policy();
        $item = $this->item();
        $charge = $this->postItem($item);
        $this->assertSame(1001, $charge->amount_cents);
        $this->assertSame(131, $charge->vat_cents);
        $this->assertSame(870, $charge->net_cents);
        $this->assertSame(1001, (int) InvoiceLine::sum('amount_cents'));
        $this->assertSame(131, (int) InvoiceLine::sum('vat_cents'));
        $vat = [];
        foreach (InvoiceLine::all() as $line) {
            $lineVat = 0;
            foreach ($line->holder_shares as $share) {
                $vat[$share['user_id']] = ($vat[$share['user_id']] ?? 0) + $share['vat_cents'];
                $lineVat += $share['vat_cents'];
            }$this->assertSame($line->vat_cents, $lineVat);
        }
        $this->assertSame(66, $vat[$this->schoolA->ids['farid_user']]);
        $this->assertSame(65, $vat[$this->schoolA->ids['mona_user']]);
        $this->assertSame(870, $this->balance('fee_income'));
        $this->assertSame(131, $this->balance('fee_vat'));
        $this->assertSame(1001, $this->balance('fee_receivable'));
        $this->assertSame(0, $this->balance('clearing'));
        $this->assertSame(0, $this->balance('due_to_school'));
        $statement = app(ParentFeeStatements::class)->show($this->parent(), $this->account->id, perPage: 50);
        $this->assertSame(66, array_sum(array_column($statement['charges']['items'], 'original_vat_cents')));
        $this->assertSame(435, array_sum(array_column($statement['charges']['items'], 'net_charged_cents')));
        $this->healthy();
    }

    public function test_exclusive_vat_keeps_listed_net_and_exact_holder_tax_totals(): void
    {
        $this->policy();
        $charge = $this->postItem($this->item('vat_exclusive'));
        $this->assertSame(1151, $charge->amount_cents);
        $this->assertSame(150, $charge->vat_cents);
        $this->assertSame(1001, $charge->net_cents);
        $shares = [];
        foreach (InvoiceLine::all() as $line) {
            foreach ($line->holder_shares as $share) {
                $shares[$share['user_id']] = ($shares[$share['user_id']] ?? 0) + $share['vat_cents'];
            }
        }
        $this->assertSame([75, 75], array_values($shares));
        $this->healthy();
    }

    public function test_vat_catalogue_can_be_prepared_before_registration_but_charging_requires_explicit_details(): void
    {
        $item = $this->item();
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->input($item)), 422);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->postItem($this->item('vat_exempt', '0', '1.00'));
        $this->postItem($this->item('no_vat', '0', '1.00'));
        $zero = $this->item('vat_zero', '0', '1.00');
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->input($zero)), 422);
        $this->policy();
        $charge = $this->postItem($zero);
        $this->assertSame(0, $charge->vat_cents);
        $this->assertSame('VAT-SYNTHETIC-01', $charge->snapshot['tax_details']['tax_registration']);
        $this->healthy();
    }

    public function test_partial_credit_tax_is_cumulative_and_a_full_share_returns_exact_original_tax(): void
    {
        $this->policy();
        $this->postItem($this->item('vat_inclusive', '15.00', '115.00'));
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $tax = [];
        foreach (['5.01', '7.02', '16.72'] as $amount) {
            $tax[] = $this->credit($line, $amount)->vat_cents;
        }
        $this->assertSame([65, 92, 218], $tax);
        $this->assertSame(375, array_sum($tax));
        $this->assertSame(1125, $this->balance('fee_vat'));
        $this->assertSame(7500, $this->balance('fee_income'));
        $this->assertSame(8625, $this->balance('fee_receivable'));
        $this->healthy();
    }

    public function test_paid_credit_restores_gross_to_original_pinned_receipt_and_reverses_net_and_vat(): void
    {
        $this->policy();
        $this->postItem($this->item('vat_inclusive', '15.00', '115.00'));
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
        $payment = app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->parent()->id, 'pinned_learner_id' => $this->schoolA->ids['asha'], 'method' => 'eft', 'source_reference' => 'SYNTHETIC-VAT-PAID', 'amount' => '28.75', 'received_on' => '2026-06-15', 'confirmed' => true]);
        $review = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        app(FeeAllocations::class)->post($this->admin, $payment->id, ['operation_id' => (string) Str::uuid(), 'fingerprint' => $review['fingerprint'], 'confirmed' => true]);
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $note = $this->credit($line, '14.38');
        $this->assertSame(188, $note->vat_cents);
        $this->assertSame(1438, $note->released_cents);
        $this->assertSame(0, $note->unpaid_cents);
        $this->assertSame(1438, app(FeePayments::class)->available($payment->fresh()));
        $this->assertSame($this->schoolA->ids['asha'], $payment->fresh()->pinned_learner_id);
        $this->assertSame(1312, $this->balance('fee_vat'));
        $this->assertSame(8750, $this->balance('fee_income'));
        $this->healthy();
    }

    public function test_zero_net_one_cent_tax_invoice_can_be_fully_credited_without_zero_postings(): void
    {
        $this->policy();
        $this->postItem($this->item('vat_inclusive', '100.00', '0.01', 'learner'), $this->schoolA->ids['asha']);
        $this->assertSame(0, $this->balance('fee_income'));
        $this->assertSame(1, $this->balance('fee_vat'));
        $line = InvoiceLine::firstOrFail();
        $note = $this->credit($line, '0.01');
        $this->assertSame(1, $note->vat_cents);
        $this->assertSame(0, $this->balance('fee_vat'));
        $this->assertSame(0, $this->balance('fee_receivable'));
        $this->assertSame(0, DB::table('ledger_postings')->where('cents', 0)->count());
        $this->healthy();
    }

    public function test_registered_details_and_rate_changes_invalidate_preview_and_old_credit_uses_original_details(): void
    {
        $this->policy();
        $item = $this->item();
        $input = $this->input($item);
        $old = app(FeeCharges::class)->preview($this->admin, $input);
        $this->policy(['revision' => 1, 'tax_registration' => 'VAT-SYNTHETIC-02']);
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $old['fingerprint'], 'confirmed' => true]), 409);
        $charge = $this->postItem($item);
        $this->policy(['revision' => 2, 'enabled' => false]);
        $line = InvoiceLine::firstOrFail();
        $note = $this->credit($line, '1.00');
        $this->assertSame('VAT-SYNTHETIC-02', $note->snapshot['tax_details']['tax_registration']);
        $this->assertSame('VAT-SYNTHETIC-02', $charge->fresh()->snapshot['tax_details']['tax_registration']);
        $this->refuses(fn () => app(FeeCharges::class)->preview($this->admin, $this->input($item)), 422);
        $this->healthy();
    }

    public function test_settings_are_revisioned_permanently_replayable_and_wrong_role_school_never_reads_tax_data(): void
    {
        $input = $this->policyInput();
        $setting = app(FeeTaxSettings::class)->save($this->admin, $input);
        $this->assertSame($setting->id, app(FeeTaxSettings::class)->save($this->admin, $input)->id);
        $this->refuses(fn () => app(FeeTaxSettings::class)->save($this->admin, $this->policyInput()), 409);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(FeeTaxSettings::class)->save($teacher, $input), 403);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => app(FeeTaxSettings::class)->save($this->admin, $input)), 403);
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => FeeTaxSetting::count()));
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_sql_keeps_original_tax_records_and_populated_rollback_refuses(): void
    {
        $this->policy();
        $charge = $this->postItem($this->item());
        $line = InvoiceLine::firstOrFail();
        $note = $this->credit($line, '1.00');
        foreach ([fn () => DB::table('fee_charges')->where('id', $charge->id)->update(['vat_cents' => 0]), fn () => DB::table('fee_credit_notes')->where('id', $note->id)->update(['vat_cents' => 0]), fn () => DB::table('invoice_lines')->where('id', $line->id)->update(['vat_cents' => 0])] as $write) {
            try {
                $write();
                $this->fail('Original tax must be retained');
            } catch (QueryException $e) {
                $this->assertSame('45000', $e->errorInfo[0]);
            }
        }
        $migration = require database_path('migrations/2026_10_27_900000_add_fee_item_vat.php');
        try {
            $migration->down();
            $this->fail('Retain tax history');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retain VAT', $e->getMessage());
        }
    }

    public function test_rate_edit_invalidates_review_and_original_rate_still_controls_credits(): void
    {
        $this->policy();
        $item = $this->item();
        $input = $this->input($item);
        $preview = app(FeeCharges::class)->preview($this->admin, $input);
        $item = app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => $item->scope, 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'vat_inclusive', 'vat_rate' => '20.00', 'state' => 'active'], $item->id);
        $this->refuses(fn () => app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]), 409);
        $charge = $this->postItem($item);
        $this->assertSame(167, $charge->vat_cents);
        app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $item->revision, 'name' => $item->name, 'scope' => $item->scope, 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'vat_inclusive', 'vat_rate' => '10.00', 'state' => 'active'], $item->id);
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $note = $this->credit($line, '2.51');
        $this->assertSame(42, $note->vat_cents);
        $this->assertSame(2000, $charge->fresh()->snapshot['vat_rate_bp']);
        $this->healthy();
    }

    public function test_large_credit_sql_guard_uses_exact_decimal_products_beyond_native_integer_range(): void
    {
        $this->policy();
        $this->postItem($this->item('vat_inclusive', '15.00', '999999999.99'));
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $first = $this->credit($line, '99999999.99');
        $last = $this->credit($line, '150000000.01');
        $this->assertSame(1304347826, $first->vat_cents);
        $this->assertSame(1956521740, $last->vat_cents);
        $this->assertSame(3260869566, $first->vat_cents + $last->vat_cents);
        $this->healthy();
    }

    public function test_private_parent_http_contract_contains_only_own_tax_and_obeys_fresh_guardians(): void
    {
        $this->policy();
        $this->postItem($this->item());
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $this->credit($line, '1.00');
        $parent = $this->parent();
        ParentPolicyService::acceptCurrent($parent);
        $url = '/api/v2/parent/fees/accounts/'.$this->account->id.'/statement';
        $response = $this->api($parent)->getJson($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(66, array_sum(array_column($response->json('data.charges.items'), 'original_vat_cents')));
        $response->assertJsonPath('data.credit_notes.items.0.vat_cents', 13)->assertJsonPath('data.credit_notes.items.0.net_cents', 87);
        foreach (['VAT-SYNTHETIC', 'seller_address', 'tax_details', 'holder_shares', 'Private Finance'] as $secret) {
            $response->assertDontSee($secret);
        }
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        ParentPolicyService::acceptCurrent($other);
        $this->api($other)->getJson($url)->assertNotFound();
        $this->api(User::findOrFail($this->schoolA->ids['tara_user']))->getJson($url)->assertForbidden();
        $this->api($parent)->withHeader('X-School-Id', '2')->getJson($url)->assertForbidden();
        GuardianLink::where('guardian_user_id', $parent->id)->update(['can_see_fees' => false]);
        $this->api($parent)->getJson($url)->assertNotFound();
    }

    public function test_vat_panel_binding_read_only_and_foreign_school_details_remain_private(): void
    {
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin)->get('/fee-items/tax-settings')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $input = $this->policyInput();
        $this->post('/fee-items/tax-settings', $input + $bind)->assertStatus(303);
        $this->post('/fee-items/tax-settings', $input + $bind)->assertStatus(303);
        $this->post('/fee-items/tax-settings', $this->policyInput(['revision' => 1]) + array_replace($bind, ['expected_school_id' => 2]))->assertForbidden();
        $this->post('/fee-items/tax-settings', $this->policyInput(['revision' => 1]) + array_replace($bind, ['expected_account_id' => 999999]))->assertForbidden();
        app(CurrentSchool::class)->run(2, fn () => FeeTaxSetting::create(['enabled' => true, 'country_code' => 'ZA', 'tax_registration' => 'PRIVATE-BAOBAB-VAT', 'seller_name' => 'Baobab tax secret', 'seller_address' => 'Private foreign address', 'revision' => 1, 'changed_by' => $this->admin->id]));
        $this->get('/fee-items/tax-settings')->assertOk()->assertDontSee('PRIVATE-BAOBAB-VAT');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-items/tax-settings')->assertOk()->assertSee('read-only');
        $this->post('/fee-items/tax-settings', $this->policyInput(['revision' => 1]) + $bind)->assertForbidden();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']))->get('/fee-items/tax-settings')->assertForbidden()->assertDontSee('VAT-SYNTHETIC');
    }

    public function test_tax_csv_and_package_journals_preserve_vat_without_double_tax(): void
    {
        $this->policy();
        $this->postItem($this->item());
        $input = ['layout' => 'journal', 'currency' => 'ZAR', 'from_on' => '2026-06-15', 'through_on' => '2026-06-15'];
        $csv = app(FeeReporting::class)->export($this->admin, $input)['csv'];
        $this->assertStringContainsString('fee_vat', $csv);
        $this->assertStringContainsString('1.31', $csv);
        $mapping = ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'xero_currency' => 'ZAR', 'quickbooks_currency' => 'ZAR', 'xero_date_format' => 'd/m/Y', 'quickbooks_date_format' => 'm/d/Y', 'xero_tax_label' => 'No Tax', 'mappings' => []];
        foreach (['fee_receivable', 'fee_income', 'fee_bank', 'family', 'cash_desk'] as $type) {
            $mapping['mappings'][$type] = ['xero' => 'CODE-'.$type, 'quickbooks' => 'Chart '.$type];
        }
        app(FeeAccountingSettings::class)->save($this->admin, $mapping);
        $this->refuses(fn () => app(FeeReporting::class)->export($this->admin, array_replace($input, ['layout' => 'xero'])), 422);
        $mapping['operation_id'] = (string) Str::uuid();
        $mapping['revision'] = 1;
        $mapping['mappings']['fee_vat'] = ['xero' => 'VAT-OUTPUT', 'quickbooks' => 'VAT payable'];
        app(FeeAccountingSettings::class)->save($this->admin, $mapping);
        $export = app(FeeReporting::class)->export($this->admin, array_replace($input, ['layout' => 'xero']));
        $rows = array_map(fn ($line) => str_getcsv($line, escape: ''), explode("\r\n", trim($export['csv'])));
        $this->assertCount(4, $rows);
        $net = 0;
        foreach (array_slice($rows, 1) as $row) {
            $this->assertSame('No Tax', $row[4]);
            $net += Money::fromDecimalString($row[5])->cents;
        }$this->assertSame(0, $net);
        $this->assertStringContainsString('VAT-OUTPUT', $export['csv']);
        $detail = app(FeeReporting::class)->export($this->admin, ['layout' => 'charges', 'currency' => 'ZAR'])['csv'];
        $this->assertStringContainsString('OriginalVAT,VATCredits,CurrentBeforeVAT', $detail);
        $this->healthy();
    }

    public function test_invalid_price_tax_combination_never_flashes_private_finance_form_text(): void
    {
        $response = $this->actingAs($this->admin)->post('/fee-items/new', [
            'expected_school_id' => 1, 'expected_account_id' => $this->admin->id, 'operation_id' => (string) Str::uuid(), 'revision' => 0,
            'name' => 'PRIVATE-FINANCE-ITEM-TEXT', 'scope' => 'family', 'amount' => '10.01', 'currency' => 'ZAR', 'tax_treatment' => 'no_vat', 'vat_rate' => '15.00', 'state' => 'active',
        ]);
        $response->assertSessionMissing('_old_input')->assertStatus(422)->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, FeeItem::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    private function api(User $actor): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($actor->createToken('synthetic-vat-statement')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $actor->id]);
    }

    private function policyInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'country_code' => 'ZA', 'tax_registration' => 'VAT-SYNTHETIC-01', 'seller_name' => 'Synthetic registered seller', 'seller_address' => '1 Synthetic School Road'], $changes);
    }

    private function policy(array $changes = []): FeeTaxSetting
    {
        return app(FeeTaxSettings::class)->save($this->admin, $this->policyInput($changes));
    }

    private function item(string $mode = 'vat_inclusive', string $rate = '15.00', string $price = '10.01', string $scope = 'family'): FeeItem
    {
        return app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic taxed item', 'scope' => $scope, 'amount' => $price, 'currency' => 'ZAR', 'tax_treatment' => $mode, 'vat_rate' => $rate, 'state' => 'active']);
    }

    private function input(FeeItem $item, ?int $learner = null): array
    {
        return ['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $this->account->id, 'session_year_id' => $this->year, 'learner_id' => $learner, 'charged_on' => '2026-06-15', 'due_on' => '2026-07-01', 'source_reference' => 'VAT-'.Str::uuid()];
    }

    private function postItem(FeeItem $item, ?int $learner = null): FeeCharge
    {
        $input = $this->input($item, $learner);
        $preview = app(FeeCharges::class)->preview($this->admin, $input);

        return app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
    }

    private function credit(InvoiceLine $line, string $amount): FeeCreditNote
    {
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->parent()->id, 'amount' => $amount, 'reason_code' => 'billing_error', 'reason' => 'Synthetic VAT correction'];
        $review = app(FeeCreditNotes::class)->preview($this->admin, $input);

        return app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $review['fingerprint'], 'confirmed' => true]);
    }

    private function parent(): User
    {
        return User::findOrFail($this->schoolA->ids['farid_user']);
    }

    private function balance(string $type): int
    {
        return (int) DB::table('ledger_accounts')->where('school_id', 1)->where('type', $type)->sum('balance_cents');
    }

    private function healthy(): void
    {
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    private function family(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected financial operation refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }
}
