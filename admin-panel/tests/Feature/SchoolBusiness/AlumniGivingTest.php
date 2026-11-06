<?php

declare(strict_types=1);

namespace Tests\Feature\SchoolBusiness;

use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SchoolBusiness\GivingDonation;
use App\Models\User;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\Giving;
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

class AlumniGivingTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        $this->admin = $this->superAdmin();
        SchoolModule::updateOrCreate(['school_id' => 1, 'module' => 'giving'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
    }

    private function user(string $name): User
    {
        return User::findOrFail($this->schoolA->ids[$name.'_user']);
    }

    private function campaign(array $changes = []): array
    {
        return app(Giving::class)->create($this->admin, array_replace(['operation_id' => (string) Str::uuid(), 'name' => 'New science lab', 'description' => 'Alumni help build a lab.',
            'currency' => 'ZAR', 'goal_cents' => 50000000, 'starts_on' => '2026-06-01', 'ends_on' => '2026-12-31', 'public_totals' => true], $changes));
    }

    private function donate(int $campaign, array $changes = []): array
    {
        return app(Giving::class)->donate($this->admin, $campaign, array_replace(['operation_id' => (string) Str::uuid(), 'donor_name' => 'Synthetic Alumna', 'donor_email' => 'alumna@example.com',
            'amount_cents' => 250075, 'received_on' => '2026-06-14', 'method' => 'eft', 'source_reference' => ' gift  ref 001 ', 'confirmed' => true], $changes));
    }

    /** @param int|list<int> $status */
    private function refuses(callable $call, int|array $status): void
    {
        try {
            $call();
            $this->fail('Expected HTTP '.json_encode($status));
        } catch (HttpExceptionInterface $error) {
            $this->assertContains($error->getStatusCode(), (array) $status, $error->getMessage());
        } catch (ModelNotFoundException) {
            // Rendered as 404 over HTTP.
            $this->assertContains(404, (array) $status);
        }
    }

    public function test_pledge_and_eft_donation_post_to_the_ledger_with_numbered_receipts(): void
    {
        $campaign = $this->campaign();
        $pledge = app(Giving::class)->pledge($this->admin, $campaign['id'], ['operation_id' => (string) Str::uuid(), 'donor_name' => 'Synthetic Alumna', 'donor_email' => null, 'amount_cents' => 500000, 'pledged_on' => '2026-06-10']);
        $this->assertSame('P-000001', $pledge['reference']);
        $donation = $this->donate($campaign['id'], ['pledge_id' => $pledge['id']]);
        $this->assertSame('D-000001', $donation['receipt']);
        $second = $this->donate($campaign['id'], ['source_reference' => 'GIFT REF 002', 'amount_cents' => 1]);
        $this->assertSame('D-000002', $second['receipt']);
        $row = GivingDonation::findOrFail($donation['id']);
        $this->assertSame(['GIFT REF 001', 250075, 'manual', 'school'], [$row->source_reference, $row->amount_cents, $row->provider, $row->collected_by]);
        $this->assertSame(250076, app(LedgerService::class)->balance($row->income_account_id)->cents);
        $this->assertSame(250076, app(LedgerService::class)->balance($row->asset_account_id)->cents);
        $this->assertSame(['giving', 250075], [(string) DB::table('financial_receipts')->where('ledger_entry_id', $row->ledger_entry_id)->value('domain'), (int) DB::table('financial_receipts')->where('ledger_entry_id', $row->ledger_entry_id)->value('amount_cents')]);
        $this->assertStringNotContainsString('Synthetic Alumna', (string) DB::table('giving_donations')->where('id', $row->id)->value('donor_name'), 'Donor names are encrypted');
        $donors = app(Giving::class)->donors($this->admin, $campaign['id']);
        $this->assertSame('Synthetic Alumna', $donors['donations'][0]['donor_name']);
        $this->assertSame(250075, $donors['pledges'][0]['received_cents']);
        $this->assertSame([250076, 500000, 2], [$donors['campaign']['raised_cents'], $donors['campaign']['pledged_cents'], $donors['campaign']['donations']]);
        $receipt = app(Giving::class)->receipt($this->admin, $donation['id']);
        $this->assertSame(['D-000001', 'New science lab', '2026-06-14'], [$receipt['receipt'], $receipt['campaign'], $receipt['received_on']]);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        try {
            $row->update(['amount_cents' => 1]);
            $this->fail('Donation receipts are immutable');
        } catch (QueryException) {
            $this->assertSame(250075, $row->fresh()->amount_cents);
        }
    }

    public function test_one_bank_line_is_never_counted_twice_and_retries_are_exact(): void
    {
        $campaign = $this->campaign();
        $operation = (string) Str::uuid();
        $first = $this->donate($campaign['id'], ['operation_id' => $operation]);
        $this->assertSame($first, $this->donate($campaign['id'], ['operation_id' => $operation]));
        $this->refuses(fn () => $this->donate($campaign['id'], ['operation_id' => $operation, 'amount_cents' => 9]), 409);
        $this->refuses(fn () => $this->donate($campaign['id'], ['source_reference' => 'GIFT REF 001']), 409);
        $this->assertSame(1, GivingDonation::count());
        $this->assertSame(1, DB::table('ledger_entries')->where('source', 'like', 'giving-receipt:%')->count());
    }

    public function test_amounts_are_integer_cents_and_the_gateway_is_not_a_method(): void
    {
        $campaign = $this->campaign();
        foreach ([['amount_cents' => '2500.75'], ['amount_cents' => 0], ['method' => 'gateway'], ['method' => 'cash'], ['received_on' => '2026-06-16']] as $bad) {
            try {
                $this->donate($campaign['id'], $bad);
                $this->fail('Refused');
            } catch (ValidationException) {
                $this->assertTrue(true);
            } catch (HttpExceptionInterface $error) {
                $this->assertSame(422, $error->getStatusCode());
            }
        }
        $this->assertSame(0, GivingDonation::count());
    }

    public function test_donor_names_are_for_finance_only_and_public_totals_are_optional(): void
    {
        $campaign = $this->campaign();
        $private = $this->campaign(['name' => 'Quiet fund', 'public_totals' => false]);
        $this->donate($campaign['id']);
        $tara = $this->user('tara');
        $this->refuses(fn () => app(Giving::class)->index($tara), 403);
        $tara->givePermissionTo(Giving::CAMPAIGNS);
        $this->assertSame(250075, collect(app(Giving::class)->index($tara)['items'])->firstWhere('id', $campaign['id'])['raised_cents']);
        $this->refuses(fn () => app(Giving::class)->donors($tara, $campaign['id']), 403);
        $this->refuses(fn () => app(Giving::class)->donate($tara, $campaign['id'], ['operation_id' => (string) Str::uuid()]), 403);
        $parent = $this->user('farid');
        $summary = app(Giving::class)->summary($parent, $campaign['id']);
        $this->assertSame([250075, 1], [$summary['raised_cents'], $summary['donations']]);
        $this->assertStringNotContainsString('Synthetic Alumna', json_encode($summary));
        $this->refuses(fn () => app(Giving::class)->summary($parent, $private['id']), 404);
        $this->refuses(fn () => app(Giving::class)->donors($parent, $campaign['id']), 403);
    }

    public function test_wrong_school_closed_campaign_and_disabled_module_are_refused_while_history_stays_readable(): void
    {
        $campaign = $this->campaign();
        $this->donate($campaign['id']);
        app(CurrentSchool::class)->run(2, function () use ($campaign) {
            SchoolModule::updateOrCreate(['school_id' => 2, 'module' => 'giving'], ['enabled' => true, 'read_only' => false, 'in_package' => true]);
            $this->refuses(fn () => app(Giving::class)->donors($this->admin, $campaign['id']), [403, 404]);
            $this->refuses(fn () => app(Giving::class)->summary(User::findOrFail($this->schoolB->ids['farid_user']), $campaign['id']), 404);
            $this->refuses(fn () => $this->donate($campaign['id'], ['source_reference' => 'BAOBAB 1']), [403, 404]);
            $this->assertSame(0, GivingDonation::count(), 'School B sees no School A donation');
        });
        app(Giving::class)->close($this->admin, $campaign['id'], ['operation_id' => (string) Str::uuid(), 'revision' => 1]);
        $this->refuses(fn () => $this->donate($campaign['id'], ['source_reference' => 'LATE GIFT']), 409);
        // Switching giving off keeps the retained history readable, never writable.
        app(ModuleGate::class)->change('giving', false, $this->admin);
        $this->assertTrue(SchoolModule::where('school_id', 1)->where('module', 'giving')->value('read_only'));
        $this->assertSame('Synthetic Alumna', app(Giving::class)->donors($this->admin, $campaign['id'])['donations'][0]['donor_name']);
        $this->refuses(fn () => $this->campaign(['name' => 'After closing']), 404);
    }

    public function test_api_campaign_and_donation_routes_match_the_contract(): void
    {
        Sanctum::actingAs($this->admin);
        $campaign = $this->postJson('/api/v2/giving/campaigns', ['operation_id' => (string) Str::uuid(), 'name' => 'Bursary fund', 'description' => null, 'currency' => 'ZAR',
            'goal_cents' => null, 'starts_on' => '2026-06-01', 'ends_on' => '2026-12-31', 'public_totals' => true], ['X-School-Id' => '1'])->assertOk()->json('data.id');
        $pledge = $this->postJson("/api/v2/giving/campaigns/$campaign/pledges", ['operation_id' => (string) Str::uuid(), 'donor_name' => 'Synthetic Alumnus', 'donor_email' => 'alumnus@example.com',
            'amount_cents' => 100000, 'pledged_on' => '2026-06-14'], ['X-School-Id' => '1'])->assertOk()->json('data.id');
        $donation = $this->postJson("/api/v2/giving/campaigns/$campaign/donations", ['operation_id' => (string) Str::uuid(), 'donor_name' => 'Synthetic Alumnus', 'donor_email' => null,
            'amount_cents' => 100000, 'received_on' => '2026-06-15', 'method' => 'eft', 'source_reference' => 'API GIFT 1', 'pledge_id' => $pledge, 'confirmed' => true], ['X-School-Id' => '1'])->assertOk()->json('data.id');
        $this->getJson('/api/v2/giving/campaigns', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.items.0.raised_cents', 100000);
        $this->getJson("/api/v2/giving/campaigns/$campaign/donors", ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.donations.0.donor_name', 'Synthetic Alumnus');
        $this->getJson("/api/v2/giving/donations/$donation/receipt", ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.receipt', 'D-000001');
        $this->postJson("/api/v2/giving/campaigns/$campaign/close", ['operation_id' => (string) Str::uuid(), 'revision' => 1], ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.state', 'closed');
        Sanctum::actingAs($this->user('farid'));
        $this->getJson("/api/v2/giving/campaigns/$campaign", ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.raised_cents', 100000)->assertJsonMissingPath('data.donor_name');
        $this->getJson("/api/v2/giving/campaigns/$campaign/donors", ['X-School-Id' => '1'])->assertStatus(403);
    }
}
