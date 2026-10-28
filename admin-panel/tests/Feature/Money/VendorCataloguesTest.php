<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorCategory;
use App\Models\Commerce\VendorOperation;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\VendorStockMovement;
use App\Models\Money\WalletSetting;
use App\Models\User;
use App\Services\Commerce\VendorCatalogueReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class VendorCataloguesTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private VendorCatalogues $catalogues;

    private int $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->catalogues = app(VendorCatalogues::class);
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        app(ModuleGate::class)->change('vendors', true, $this->admin);
        $this->category = $this->catalogues->category($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'key' => 'food', 'name' => 'Food', 'status' => 'active'])->id;
    }

    private function vendor(array $extra = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $this->category, 'name' => 'School tuckshop', 'kind' => 'school', 'status' => 'active', 'legal_name' => 'School synthetic trading', 'vat_registered' => false, 'refund_bearer' => 'school', 'chargeback_bearer' => 'school', 'settlement_method' => 'school_internal', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Retain proceeds in the school ledger until a reviewed settlement.'], $extra);
    }

    private function product(int $vendor, array $extra = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $vendor, 'category_id' => $this->category, 'sku' => 'SANDWICH-01', 'name' => 'Sandwich', 'unit_label' => 'each', 'price' => '12.50', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => true, 'status' => 'active'], $extra);
    }

    private function stock(int $product, array $extra = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'product_id' => $product, 'source_reference' => 'SYNTHETIC-DELIVERY-001', 'kind' => 'receipt', 'units_delta' => 10, 'private_reason' => 'Synthetic counted delivery invoice with private supplier details'], $extra);
    }

    private function refuses(callable $work, int $status): void
    {
        try {
            $work();
            $this->fail('Expected catalogue refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_catalogue_preparation_and_stock_work_with_wallet_paused_and_create_no_money(): void
    {
        $input = $this->vendor();
        $v = $this->catalogues->vendor($this->admin, $input);
        $this->assertSame($v->id, $this->catalogues->vendor($this->admin, $input)->id);
        $this->assertSame(0, app(LedgerService::class)->balance($v->ledger_account_id)->cents);
        $p = $this->catalogues->product($this->admin, $this->product($v->id));
        $this->assertSame(1250, $p->price_cents);
        $this->assertSame(0, VendorStockBalance::sole()->on_hand);
        $input = $this->stock($p->id);
        $movement = $this->catalogues->stock($this->admin, $input);
        $this->assertSame($movement->id, $this->catalogues->stock($this->admin, $input)->id);
        $this->assertSame($movement->id, $this->catalogues->stock($this->admin, array_replace($input, ['source_reference' => ' synthetic-delivery-001 ']))->id);
        $this->assertSame(10, VendorStockBalance::sole()->on_hand);
        $this->assertSame(1, VendorStockMovement::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame('food', $this->catalogues->storefront($this->admin, $v->id)['items'][0]['category_key']);
    }

    public function test_activation_requires_explicit_liability_settlement_and_vat_choices(): void
    {
        $this->refuses(fn () => $this->catalogues->vendor($this->admin, $this->vendor(['refund_bearer' => null])), 422);
        $this->refuses(fn () => $this->catalogues->vendor($this->admin, $this->vendor(['vat_registered' => null])), 422);
        $this->refuses(fn () => $this->catalogues->vendor($this->admin, $this->vendor(['kind' => 'independent', 'settlement_method' => 'eft'])), 422);
        $draft = $this->catalogues->vendor($this->admin, $this->vendor(['status' => 'draft', 'refund_bearer' => null, 'vat_registered' => null, 'settlement_method' => null]));
        $this->assertSame('draft', $draft->status);
        $this->refuses(fn () => $this->catalogues->storefront($this->admin, $draft->id), 404);
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_independent_bank_instructions_and_stock_reasons_are_encrypted_and_absent_from_storefront(): void
    {
        $bank = 'Private synthetic original merchant bank instructions';
        $v = $this->catalogues->vendor($this->admin, $this->vendor(['kind' => 'independent', 'settlement_method' => 'eft', 'private_bank_instructions' => $bank, 'refund_bearer' => 'vendor']));
        $p = $this->catalogues->product($this->admin, $this->product($v->id));
        $m = $this->catalogues->stock($this->admin, $this->stock($p->id));
        $this->assertSame($bank, $v->private_bank_instructions);
        $this->assertNotSame($bank, DB::table('vendors')->value('private_bank_instructions'));
        $this->assertNotSame($m->private_reason, DB::table('vendor_stock_movements')->value('private_reason'));
        $this->assertStringNotContainsString('private_bank_instructions', $v->toJson());
        $public = json_encode($this->catalogues->storefront($this->admin, $v->id), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($bank, $public);
        $this->assertStringNotContainsString('legal_name', $public);
        $this->assertStringNotContainsString('private_reason', $public);
        $this->assertStringNotContainsString($bank, VendorOperation::get()->toJson());
    }

    public function test_stock_cannot_be_duplicated_overdrawn_or_rewritten(): void
    {
        $v = $this->catalogues->vendor($this->admin, $this->vendor());
        $p = $this->catalogues->product($this->admin, $this->product($v->id));
        $this->catalogues->stock($this->admin, $this->stock($p->id));
        $this->refuses(fn () => $this->catalogues->stock($this->admin, $this->stock($p->id)), 409);
        $this->refuses(fn () => $this->catalogues->stock($this->admin, $this->stock($p->id, ['source_reference' => 'OVERDRAW', 'kind' => 'waste', 'units_delta' => -11])), 409);
        $this->refuses(fn () => $this->catalogues->stock($this->admin, $this->stock($p->id, ['source_reference' => 'INVALID-SIGN', 'kind' => 'receipt', 'units_delta' => -1])), 422);
        $this->catalogues->stock($this->admin, $this->stock($p->id, ['source_reference' => 'COUNTED-WASTE', 'kind' => 'waste', 'units_delta' => -3]));
        $this->assertSame(7, VendorStockBalance::sole()->on_hand);
        foreach ([fn () => DB::table('vendor_stock_balances')->update(['on_hand' => 99, 'revision' => 3]), fn () => DB::table('vendor_stock_movements')->update(['units_delta' => 99]), fn () => DB::table('vendor_stock_movements')->delete()] as $work) {
            try {
                $work();
                $this->fail('History must be immutable');
            } catch (QueryException $e) {
                $this->assertSame('45000', $e->errorInfo[0]);
            }
        }
    }

    public function test_authority_is_rechecked_before_replay_and_other_schools_cannot_resolve_ids(): void
    {
        $input = $this->vendor();
        $v = $this->catalogues->vendor($this->admin, $input);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => $this->catalogues->vendor($teacher, $input), 403);
        $teacher->givePermissionTo('vendors-manage');
        $this->refuses(fn () => $this->catalogues->vendor($teacher, $input), 409);
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $learner->givePermissionTo('vendors-manage');
        $this->refuses(fn () => $this->catalogues->vendor($learner, $input), 403);
        $this->refuses(fn () => $this->catalogues->vendor($this->admin, array_replace($input, ['name' => 'Changed retry'])), 409);
        DB::table('model_has_roles')->where('school_id', 1)->where('model_id', $this->admin->id)->delete();
        $this->refuses(fn () => $this->catalogues->vendor($this->admin, $input), 403);
        $this->assertSame(1, Vendor::count());
        app(CurrentSchool::class)->run(2, fn () => $this->assertNull(Vendor::find($v->id)));
    }

    public function test_archival_retains_history_and_module_disable_becomes_read_only(): void
    {
        $v = $this->catalogues->vendor($this->admin, $this->vendor());
        $p = $this->catalogues->product($this->admin, $this->product($v->id));
        $this->catalogues->stock($this->admin, $this->stock($p->id));
        $this->catalogues->vendor($this->admin, $this->vendor(['id' => $v->id, 'revision' => 1, 'status' => 'archived']));
        $this->refuses(fn () => $this->catalogues->storefront($this->admin, $v->id), 404);
        $this->assertSame(10, VendorStockBalance::sole()->on_hand);
        $state = app(ModuleGate::class)->change('vendors', false, $this->admin);
        $this->assertTrue($state['enabled']);
        $this->assertTrue($state['read_only']);
        $this->refuses(fn () => $this->catalogues->stock($this->admin, $this->stock($p->id, ['source_reference' => 'PAUSED'])), 403);
    }

    public function test_product_tax_revision_and_ownership_are_explicit(): void
    {
        $v = $this->catalogues->vendor($this->admin, $this->vendor());
        $this->refuses(fn () => $this->catalogues->product($this->admin, $this->product($v->id, ['vat_kind' => 'standard', 'vat_rate_bps' => 1500])), 422);
        $p = $this->catalogues->product($this->admin, $this->product($v->id));
        $this->refuses(fn () => $this->catalogues->product($this->admin, $this->product($v->id, ['id' => $p->id, 'revision' => 0])), 409);
        $this->refuses(fn () => $this->catalogues->product($this->admin, $this->product($v->id, ['id' => $p->id, 'revision' => 1, 'tracks_stock' => false])), 409);
        $this->refuses(fn () => $this->catalogues->vendor($this->admin, $this->vendor(['id' => $v->id, 'revision' => 1, 'vat_registered' => true, 'vat_number' => 'SYNTHETIC-VAT'])), 409);
        $this->assertSame(1, $p->fresh()->revision);
    }

    public function test_finance_forms_bind_actor_school_and_never_restore_bank_data(): void
    {
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin, 'web')->get('/vendor-catalogues')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $input = $this->vendor(['kind' => 'independent', 'settlement_method' => 'eft', 'private_bank_instructions' => 'Private synthetic vendor original bank account']);
        $this->post('/vendor-catalogues/vendor', $input + $bind)->assertRedirect();
        $v = Vendor::sole();
        $this->get('/vendor-catalogues?vendor_id='.$v->id)->assertOk()->assertSee('School tuckshop')->assertDontSee($input['private_bank_instructions']);
        $this->post('/vendor-catalogues/product', $this->product($v->id) + $bind)->assertRedirect();
        $p = VendorProduct::sole();
        $this->get('/vendor-catalogues?product_id='.$p->id)->assertOk()->assertSee('Units on hand')->assertSee('Sandwich');
        $this->post('/vendor-catalogues/stock', $this->stock($p->id) + $bind)->assertRedirect();
        $this->get('/vendor-catalogues?product_id='.$p->id)->assertOk()->assertSee('SYNTHETIC-DELIVERY-001')->assertDontSee('private supplier details');
        $this->post('/vendor-catalogues/vendor', $input + ['expected_account_id' => $this->admin->id, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/vendor-catalogues/vendor?unexpected=1', $input + $bind)->assertUnprocessable();
        $this->post('/vendor-catalogues/vendor', array_replace($input, ['name' => '']) + $bind)->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']), 'web')->get('/vendor-catalogues')->assertForbidden();
    }

    public function test_foreign_category_and_vendor_ids_are_refused_in_services_and_panel(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, fn () => VendorCategory::create(['key' => 'baobab', 'name' => 'Baobab private category', 'status' => 'active', 'revision' => 1, 'changed_by' => $this->schoolB->ids['tara_user']]));
        $this->refuses(fn () => $this->catalogues->vendor($this->admin, $this->vendor(['category_id' => $foreign->id])), 404);
        $this->actingAs($this->admin, 'web')->get('/vendor-catalogues?category_id='.$foreign->id)->assertNotFound()->assertDontSee('Baobab private category');
        $v = $this->catalogues->vendor($this->admin, $this->vendor());
        $p = $this->catalogues->product($this->admin, $this->product($v->id));
        $this->refuses(fn () => $this->catalogues->product($this->admin, $this->product($v->id, ['id' => $p->id, 'revision' => 1, 'category_id' => $foreign->id])), 404);
        app(CurrentSchool::class)->run(2, fn () => $this->refuses(fn () => $this->catalogues->storefront(User::findOrFail($this->schoolB->ids['tara_user']), $v->id), 403));
    }

    public function test_retained_stock_and_vendor_identity_reconcile_without_creating_entries(): void
    {
        $v = $this->catalogues->vendor($this->admin, $this->vendor());
        $p = $this->catalogues->product($this->admin, $this->product($v->id));
        $this->catalogues->stock($this->admin, $this->stock($p->id));
        $report = app(VendorCatalogueReconciliation::class)->inspect();
        $this->assertTrue(VendorCatalogueReconciliation::healthy($report));
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_zero_vendor_identity_also_freezes_the_explicit_school_currency(): void
    {
        $this->catalogues->vendor($this->admin, $this->vendor());
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        $this->refuses(fn () => app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'currency' => 'USD', 'daily_spend_cents' => 5000, 'child_limits_required' => true]), 409);
        $this->assertSame('ZAR', WalletSetting::sole()->currency);
    }
}
