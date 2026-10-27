<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountingSetting;
use App\Models\Money\FeePayment;
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
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeeReporting;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FeeReportingTest extends TestCase
{
    use UsesTwoSchools;

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

    public function test_all_age_buckets_sum_exact_split_holder_amounts_and_keep_held_credit_separate(): void
    {
        foreach (['2026-06-16', '2026-06-15', '2026-06-14', '2026-05-17', '2026-05-16', '2026-04-17', '2026-04-16', '2026-03-18', '2026-03-17'] as $due) {
            $this->charge($due);
        }
        $this->receipt('50.00', $this->schoolA->ids['asha']);
        $before = [DB::table('ledger_entries')->count(), DB::table('payment_allocations')->count()];
        $report = app(FeeReporting::class)->ageing($this->admin, 'ZAR');
        $this->assertSame(['current' => 2002, 'days_1_29' => 2002, 'days_30_59' => 2002, 'days_60_89' => 2002, 'days_90_plus' => 1001], $report['buckets']);
        $this->assertSame(9009, $report['outstanding_cents']);
        $this->assertSame(5000, $report['credit_cents']);
        $this->assertSame(9009, array_sum(array_column($report['accounts'], 'outstanding_cents')));
        $this->assertSame(36, count($report['charges']));
        $this->assertSame($before, [DB::table('ledger_entries')->count(), DB::table('payment_allocations')->count()]);
    }

    public function test_partial_allocations_and_credit_notes_reduce_only_the_correct_aged_debt(): void
    {
        $this->charge();
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
        $payment = $this->receipt();
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        app(FeeAllocations::class)->post($this->admin, $payment->id, ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $this->assertSame(601, app(FeeReporting::class)->ageing($this->admin, 'ZAR')['outstanding_cents']);
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->schoolA->ids['mona_user'], 'amount' => '1.00', 'reason_code' => 'billing_error', 'reason' => 'Synthetic report correction'];
        $credit = app(FeeCreditNotes::class)->preview($this->admin, $input);
        app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $credit['fingerprint'], 'confirmed' => true]);
        $report = app(FeeReporting::class)->ageing($this->admin, 'ZAR');
        $this->assertSame(501, $report['outstanding_cents']);
        $this->assertSame(501, $report['buckets']['days_90_plus']);
        $this->assertSame(0, $report['credit_cents']);
    }

    public function test_currency_filters_and_real_other_school_accounts_never_mix(): void
    {
        $this->charge();
        $foreign = app(CurrentSchool::class)->run(2, fn () => FeeAccount::create(['reference' => 'NEF-BAOBAB', 'name' => 'Baobab financial secret', 'currency' => 'ZAR', 'active' => true, 'revision' => 1]));
        $this->assertCount(1, app(FeeReporting::class)->ageing($this->admin, 'ZAR')['accounts']);
        $this->assertSame(0, app(FeeReporting::class)->ageing($this->admin, 'USD')['outstanding_cents']);
        $csv = app(FeeReporting::class)->export($this->admin, $this->exportInput('ageing'));
        $this->assertStringNotContainsString('Baobab', $csv['csv']);
        $this->assertStringNotContainsString($foreign->reference, $csv['csv']);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function (): void {
            $other = $this->admin->fresh();
            $other->assignRole('Super Admin');
            $report = app(FeeReporting::class)->ageing($other, 'ZAR');
            $this->assertSame(0, $report['outstanding_cents']);
            $this->assertSame('Baobab financial secret', $report['accounts'][0]['name']);
        });
    }

    public function test_all_csv_layouts_preserve_exact_cents_references_and_balanced_journals(): void
    {
        $this->charge();
        $this->receipt();
        $settings = app(FeeAccountingSettings::class)->save($this->admin, $this->mapping());
        $aged = $this->rows(app(FeeReporting::class)->export($this->admin, $this->exportInput('ageing')));
        $this->assertSame('10.01', $aged[1][9]);
        $this->assertSame('4.00', $aged[1][10]);
        $detail = $this->rows(app(FeeReporting::class)->export($this->admin, $this->exportInput('charges')));
        $this->assertCount(5, $detail);
        $this->assertSame('2.51', $detail[1][9]);
        $this->assertSame('2.50', $detail[2][9]);
        $journal = $this->rows(app(FeeReporting::class)->export($this->admin, $this->exportInput()));
        $this->assertCount(5, $journal);
        $this->assertSame('10.01', $journal[1][6]);
        $this->assertSame('10.01', $journal[2][7]);
        $xero = $this->rows(app(FeeReporting::class)->export($this->admin, $this->exportInput('xero')));
        $this->assertSame(['Narration', 'Date', 'Description', 'AccountCode', 'TaxRate', 'Amount', 'TrackingName1', 'TrackingOption1', 'TrackingName2', 'TrackingOption2'], $xero[0]);
        $this->assertSame('15/06/2026', $xero[1][1]);
        $this->assertSame('CODE-1', $xero[1][3]);
        $this->assertSame('No Tax', $xero[1][4]);
        $this->assertSame('10.01', $xero[1][5]);
        $this->assertSame('-10.01', $xero[2][5]);
        $qbo = $this->rows(app(FeeReporting::class)->export($this->admin, $this->exportInput('quickbooks')));
        $this->assertSame(['JournalNo', 'JournalDate', 'AccountName', 'Debits', 'Credits', 'Description', 'Name', 'Currency'], $qbo[0]);
        $this->assertSame('06/15/2026', $qbo[1][1]);
        $this->assertSame('Chart 1', $qbo[1][2]);
        $this->assertSame(2, DB::table('ledger_entries')->count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertSame(5, DB::table('audit_log')->where('action', 'money.fee_accounting_exported')->count());
    }

    public function test_csv_formula_strings_are_escaped_without_turning_money_into_text(): void
    {
        $this->charge();
        $this->account->update(['name' => ' =HYPERLINK("https://example.invalid","unsafe")']);
        $rows = $this->rows(app(FeeReporting::class)->export($this->admin, $this->exportInput('ageing')));
        $this->assertStringStartsWith("' =HYPERLINK", $rows[1][2]);
        $this->assertSame('10.01', $rows[1][9]);
        $input = $this->mapping();
        $input['mappings']['fee_income']['xero'] = '=BAD';
        $this->refuses(fn () => app(FeeAccountingSettings::class)->save($this->admin, $input), 422);
    }

    public function test_package_export_requires_explicit_mapping_and_date_periods_are_bounded(): void
    {
        $this->charge();
        $this->refuses(fn () => app(FeeReporting::class)->export($this->admin, $this->exportInput('xero')), 422);
        $input = $this->mapping();
        $input['mappings']['fee_income']['quickbooks'] = '';
        app(FeeAccountingSettings::class)->save($this->admin, $input);
        $this->refuses(fn () => app(FeeReporting::class)->export($this->admin, $this->exportInput('quickbooks')), 422);
        foreach ([['from_on' => '2026-06-16'], ['through_on' => '2026-06-16'], ['from_on' => '2024-01-01'], ['from_on' => 'bad'], ['through_on' => null]] as $change) {
            $this->refuses(fn () => app(FeeReporting::class)->export($this->admin, $this->exportInput('journal', $change)), 422);
        }
        $this->assertSame(1, DB::table('ledger_entries')->count());
    }

    public function test_mapping_save_has_revision_and_actor_bound_permanent_replay_without_money_changes(): void
    {
        $input = $this->mapping();
        $settings = app(FeeAccountingSettings::class)->save($this->admin, $input);
        $this->assertSame($settings->id, app(FeeAccountingSettings::class)->save($this->admin, $input)->id);
        $this->refuses(fn () => app(FeeAccountingSettings::class)->save($this->admin, $this->mapping()), 409);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(1, FeeAccountingSetting::count());
        $other = User::findOrFail($this->schoolA->ids['tara_user']);
        $other->givePermissionTo('fee-payments-manage');
        $this->refuses(fn () => app(FeeAccountingSettings::class)->save($other, $input), 409);
    }

    public function test_journal_periods_use_school_calendar_dates_across_utc_midnight(): void
    {
        $this->travelTo(now('UTC')->setDateTime(2026, 6, 15, 22, 30, 0));
        $this->charge();
        $today = app(FeeReporting::class)->ageing($this->admin, 'ZAR')['as_of'];
        $this->assertSame('2026-06-16', $today);
        $late = app(FeeReporting::class)->export($this->admin, $this->exportInput('journal', ['from_on' => '2026-06-16', 'through_on' => '2026-06-16']));
        $early = app(FeeReporting::class)->export($this->admin, $this->exportInput('journal', ['from_on' => '2026-06-15', 'through_on' => '2026-06-15']));
        $this->assertSame(2, $late['row_count']);
        $this->assertSame(0, $early['row_count']);
    }

    public function test_panel_is_private_actor_bound_and_retains_readonly_reports_without_allowing_mapping_writes(): void
    {
        $this->charge();
        $this->receipt();
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->get('/fee-reporting')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('ZAR 10.01')->assertSee('ZAR 4.00');
        $this->get('/fee-reporting/settings')->assertOk()->assertSee('Accounting package mapping');
        $this->post('/fee-reporting/settings', $this->mapping() + $bind)->assertStatus(303);
        $this->post('/fee-reporting/export', $this->exportInput('xero') + $bind)->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8')->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/fee-reporting/export', $this->exportInput('ageing') + ['expected_school_id' => 2, 'expected_account_id' => $this->admin->id])->assertForbidden();
        $this->post('/fee-reporting/export?unsafe=1', $this->exportInput('ageing') + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-reporting')->assertOk();
        $this->post('/fee-reporting/export', $this->exportInput('journal') + $bind)->assertOk();
        $this->post('/fee-reporting/settings', $this->mapping(['revision' => 1]) + $bind)->assertForbidden();
    }

    public function test_wrong_role_parent_and_revoked_permission_cannot_read_or_export_financial_reports(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(FeeReporting::class)->ageing($teacher, 'ZAR'), 403);
        $this->actingAs($teacher)->get('/fee-reporting')->assertForbidden();
        $this->get('/fee-reporting/settings')->assertForbidden();
        $this->post('/fee-reporting/export', $this->exportInput('ageing') + ['expected_school_id' => 1, 'expected_account_id' => $teacher->id])->assertForbidden();
        foreach (['farid_user', 'asha_user'] as $key) {
            $actor = User::findOrFail($this->schoolA->ids[$key]);
            $this->refuses(fn () => app(FeeReporting::class)->ageing($actor, 'ZAR'), 403);
            foreach (['/fee-reporting', '/fee-reporting/settings'] as $path) {
                $this->actingAs($actor)->get($path)->assertRedirect(route('login'));
                $this->assertGuest();
            }
            $this->actingAs($actor)->post('/fee-reporting/export', $this->exportInput('ageing') + ['expected_school_id' => 1, 'expected_account_id' => $actor->id])->assertRedirect(route('login'))->assertSessionMissing('_old_input');
        }
        $permission = DB::table('permissions')->where('name', 'fee-payments-manage')->value('id');
        DB::table('role_has_permissions')->where('permission_id', $permission)->delete();
        $this->refuses(fn () => app(FeeReporting::class)->export($this->admin, $this->exportInput('ageing')), 403);
    }

    public function test_paid_credit_releases_remain_held_without_silently_settling_another_holders_debt(): void
    {
        $this->charge();
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
        $payment = $this->receipt('20.00');
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        app(FeeAllocations::class)->post($this->admin, $payment->id, ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'amount' => '1.00', 'reason_code' => 'billing_error', 'reason' => 'Synthetic paid correction'];
        $review = app(FeeCreditNotes::class)->preview($this->admin, $input);
        app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $review['fingerprint'], 'confirmed' => true]);
        $report = app(FeeReporting::class)->ageing($this->admin, 'ZAR');
        $this->assertSame(500, $report['outstanding_cents']);
        $this->assertSame(1599, $report['credit_cents']);
    }

    public function test_accounting_organisation_currency_must_match_the_exported_currency(): void
    {
        $this->charge();
        app(FeeAccountingSettings::class)->save($this->admin, $this->mapping(['xero_currency' => 'USD', 'quickbooks_currency' => 'USD']));
        foreach (['xero', 'quickbooks'] as $layout) {
            $this->refuses(fn () => app(FeeReporting::class)->export($this->admin, $this->exportInput($layout)), 422);
        }
        $this->assertSame(2, app(FeeReporting::class)->export($this->admin, $this->exportInput('journal'))['row_count']);
    }

    public function test_an_incomplete_ledger_entry_cannot_be_omitted_from_a_complete_journal_export(): void
    {
        $this->charge();
        DB::table('ledger_entries')->insert(['school_id' => 1, 'idempotency_key' => 'report-draft', 'source' => 'report-draft', 'request_hash' => str_repeat('a', 64), 'description' => 'Synthetic incomplete history', 'currency' => 'ZAR', 'posted_at' => null, 'created_at' => now('UTC')]);
        $this->refuses(fn () => app(FeeReporting::class)->export($this->admin, $this->exportInput('journal')), 409);
    }

    private function family(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
    }

    private function charge(string $due = '2026-03-01', string $currency = 'ZAR', ?int $accountId = null): void
    {
        $item = app(FeeItems::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic report charge', 'scope' => 'family', 'amount' => '10.01', 'currency' => $currency, 'tax_treatment' => 'no_vat', 'state' => 'active']);
        $input = ['operation_id' => (string) Str::uuid(), 'fee_item_id' => $item->id, 'fee_account_id' => $accountId ?? $this->account->id, 'session_year_id' => $this->year,
            'learner_id' => null, 'charged_on' => '2026-01-01', 'due_on' => $due, 'source_reference' => 'REPORT-'.Str::uuid()];
        $preview = app(FeeCharges::class)->preview($this->admin, $input);
        app(FeeCharges::class)->post($this->admin, $input + ['fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
    }

    private function receipt(string $amount = '4.00', ?int $pin = null): FeePayment
    {
        return app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->account->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => $pin,
            'method' => 'eft', 'source_reference' => 'REPORT-'.Str::uuid(), 'amount' => $amount, 'received_on' => '2026-06-15', 'confirmed' => true]);
    }

    private function mapping(array $changes = []): array
    {
        $mappings = [];
        foreach (FeeAccountingSettings::TYPES as $i => $type) {
            $mappings[$type] = ['xero' => 'CODE-'.($i + 1), 'quickbooks' => 'Chart '.($i + 1)];
        }

        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'mappings' => $mappings, 'xero_currency' => 'ZAR', 'quickbooks_currency' => 'ZAR', 'xero_date_format' => 'd/m/Y', 'quickbooks_date_format' => 'm/d/Y', 'xero_tax_label' => 'No Tax'], $changes);
    }

    private function exportInput(string $layout = 'journal', array $changes = []): array
    {
        return array_replace(['layout' => $layout, 'currency' => 'ZAR', 'from_on' => '2026-06-01', 'through_on' => '2026-06-15'], $changes);
    }

    private function rows(array $export): array
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $export['csv']);
        rewind($stream);
        $rows = [];
        while (($row = fgetcsv($stream, escape: '')) !== false) {
            $rows[] = $row;
        }
        fclose($stream);

        return $rows;
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
