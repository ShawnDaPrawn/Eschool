<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorCategory;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\VendorStockMovement;
use App\Models\Money\WalletSetting;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Catalogue preparation has no dependency on wallet writes and never posts money. */
final class VendorCatalogues
{
    public static function rules(string $kind): array
    {
        $base = ['operation_id' => ['required', 'uuid']];

        return $base + match ($kind) {
            'category' => [
                'id' => ['nullable', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'],
                'key' => ['required', 'regex:/\A[a-z][a-z0-9_-]{0,39}\z/D'], 'name' => ['required', 'string', 'max:100'],
                'status' => ['required', Rule::in(['active', 'archived'])],
            ],
            'vendor' => [
                'id' => ['nullable', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'],
                'category_id' => ['required', 'integer', 'min:1'], 'name' => ['required', 'string', 'max:160'],
                'legal_name' => ['nullable', 'string', 'max:160'], 'legal_address' => ['nullable', 'string', 'max:1000'], 'description' => ['nullable', 'string', 'max:2000'],
                'kind' => ['required', Rule::in(['school', 'independent'])], 'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
                'vat_registered' => ['nullable', 'boolean'], 'vat_number' => ['nullable', 'string', 'max:40'],
                'refund_bearer' => ['nullable', Rule::in(['school', 'vendor'])], 'chargeback_bearer' => ['nullable', Rule::in(['school', 'vendor'])],
                'settlement_method' => ['nullable', Rule::in(['school_internal', 'cash', 'eft'])],
                'settlement_cycle' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'manual'])],
                'settlement_delay_days' => ['nullable', 'integer', 'min:0', 'max:90'], 'settlement_terms' => ['nullable', 'string', 'min:10', 'max:4000'],
                'private_bank_instructions' => ['nullable', 'string', 'min:10', 'max:4000'],
            ],
            'product' => [
                'id' => ['nullable', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'],
                'vendor_id' => ['required', 'integer', 'min:1'], 'category_id' => ['required', 'integer', 'min:1'],
                'sku' => ['required', 'regex:/\A[A-Z0-9][A-Z0-9._\/-]{0,63}\z/D'], 'name' => ['required', 'string', 'max:160'],
                'description' => ['nullable', 'string', 'max:2000'], 'unit_label' => ['required', 'string', 'max:40'],
                'price' => ['required', 'string', 'regex:/\A\d{1,9}(?:\.\d{1,2})?\z/D'],
                'vat_kind' => ['required', Rule::in(['none', 'zero', 'exempt', 'standard'])], 'vat_rate_bps' => ['required', 'integer', 'min:0', 'max:10000'],
                'tracks_stock' => ['required', 'boolean'], 'status' => ['required', Rule::in(['draft', 'active', 'archived'])],
            ],
            'stock' => [
                'product_id' => ['required', 'integer', 'min:1'], 'source_reference' => ['required', 'regex:/\A[A-Z0-9][A-Z0-9 ._:\/-]{0,95}\z/D'],
                'kind' => ['required', Rule::in(['receipt', 'correction', 'waste'])],
                'units_delta' => ['required', 'integer', 'between:-1000000000,1000000000', 'not_in:0'],
                'private_reason' => ['required', 'string', 'min:3', 'max:2000'],
            ],
            default => throw new \InvalidArgumentException('Unknown catalogue change.'),
        };
    }

    private function data(string $kind, array $input): array
    {
        foreach (['source_reference', 'sku'] as $reference) {
            if (is_string($input[$reference] ?? null)) {
                $input[$reference] = strtoupper(preg_replace('/ +/', ' ', trim($input[$reference])) ?? '');
            }
        }
        $data = Validator::make($input, self::rules($kind))->validate();
        foreach (['id', 'revision', 'category_id', 'vendor_id', 'product_id', 'vat_rate_bps', 'settlement_delay_days', 'units_delta'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }
        foreach (['vat_registered', 'tracks_stock'] as $key) {
            if (isset($data[$key])) {
                $data[$key] = (bool) $data[$key];
            }
        }

        return $data;
    }

    public function category(User $actor, array $input): VendorCategory
    {
        $data = $this->data('category', $input);
        $receipt = app(VendorOperations::class)->once($actor, 'category', $data, function () use ($actor, $data): array {
            $row = isset($data['id']) ? VendorCategory::lockForUpdate()->findOrFail($data['id']) : new VendorCategory;
            $this->revision($row->revision ?? 0, $data['revision']);
            abort_if($row->exists && $row->key !== $data['key'], 409, 'Category keys are permanent.');
            abort_if(VendorCategory::where('key', $data['key'])->when($row->exists, fn ($q) => $q->where('id', '<>', $row->id))->exists(), 409, 'This category key is already in use.');
            $row->fill(['key' => $data['key'], 'name' => $data['name'], 'status' => $data['status'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            Audit::record('vendors.category_saved', $row, [], ['revision' => $row->revision]);

            return ['id' => (int) $row->id];
        });

        return VendorCategory::findOrFail($receipt['id']);
    }

    public function vendor(User $actor, array $input): Vendor
    {
        $data = $this->data('vendor', $input);
        $receipt = app(VendorOperations::class)->once($actor, 'vendor', $data, function () use ($actor, $data): array {
            $row = isset($data['id']) ? Vendor::lockForUpdate()->findOrFail($data['id']) : new Vendor;
            $this->revision($row->revision ?? 0, $data['revision']);
            $category = VendorCategory::lockForUpdate()->findOrFail($data['category_id']);
            $settings = WalletSetting::lockForUpdate()->first();
            abort_unless($settings !== null, 409, 'Finance must explicitly configure the school currency first.');
            abort_if($row->exists && ($row->kind !== $data['kind'] || $row->currency !== $settings->currency), 409, 'Vendor ownership kind and currency are retained.');
            $fields = array_diff_key($data, array_flip(['id', 'operation_id', 'revision']));
            // Blank bank fields retain the encrypted instructions; they are never echoed into forms.
            if ($row->exists && empty($fields['private_bank_instructions'])) {
                unset($fields['private_bank_instructions']);
            }
            if ($row->exists && $row->vat_registered !== ($data['vat_registered'] ?? null)) {
                abort_if(VendorProduct::where('vendor_id', $row->id)->where('status', 'active')->exists(), 409, 'Archive and review active products before changing VAT registration.');
            }
            $row->fill($fields);
            if ($data['status'] === 'active') {
                abort_unless($category->status === 'active', 409, 'Activate the vendor category first.');
                $this->activation($row);
            }
            if (! $row->exists) {
                $row->operation_id = $data['operation_id'];
                $row->currency = $settings->currency;
                $row->ledger_account_id = app(LedgerService::class)->openAccount(AccountType::Vendor, 'vendors:merchant:'.$data['operation_id'].':'.$settings->currency, $settings->currency);
            }
            $row->revision = $data['revision'] + 1;
            $row->changed_by = $actor->id;
            $row->save();
            Audit::record('vendors.vendor_saved', $row, [], ['revision' => $row->revision, 'status' => $row->status]);

            return ['id' => (int) $row->id];
        });

        return Vendor::findOrFail($receipt['id']);
    }

    private function activation(Vendor $row): void
    {
        abort_unless(trim((string) $row->legal_name) !== '' && $row->vat_registered !== null && (! $row->vat_registered || trim((string) $row->vat_number) !== ''), 422, 'Activation requires legal identity and an explicit VAT decision.');
        abort_unless($row->refund_bearer !== null && $row->chargeback_bearer !== null && $row->settlement_cycle !== null && $row->settlement_delay_days !== null && mb_strlen(trim((string) $row->settlement_terms)) >= 10, 422, 'Activation requires explicit settlement terms and refund/chargeback responsibility.');
        abort_unless($row->kind === 'school' ? $row->settlement_method === 'school_internal' : in_array($row->settlement_method, ['cash', 'eft'], true), 422, 'Choose a settlement method for this ownership kind.');
        abort_if($row->settlement_method === 'eft' && empty($row->private_bank_instructions), 422, 'EFT settlement requires private bank instructions.');
    }

    public function product(User $actor, array $input): VendorProduct
    {
        $data = $this->data('product', $input);
        $receipt = app(VendorOperations::class)->once($actor, 'product', $data, function () use ($actor, $data): array {
            $vendor = Vendor::lockForUpdate()->findOrFail($data['vendor_id']);
            $category = VendorCategory::lockForUpdate()->findOrFail($data['category_id']);
            $row = isset($data['id']) ? VendorProduct::lockForUpdate()->findOrFail($data['id']) : new VendorProduct;
            $this->revision($row->revision ?? 0, $data['revision']);
            abort_if($row->exists && ($row->vendor_id !== $data['vendor_id'] || $row->sku !== $data['sku'] || $row->tracks_stock !== $data['tracks_stock']), 409, 'Product vendor, SKU and stock mode are permanent.');
            abort_if(VendorProduct::where('vendor_id', $vendor->id)->where('sku', $data['sku'])->when($row->exists, fn ($q) => $q->where('id', '<>', $row->id))->exists(), 409, 'This vendor already has that SKU.');
            $taxValid = $data['vat_kind'] === 'standard' ? $data['vat_rate_bps'] > 0 : $data['vat_rate_bps'] === 0;
            abort_unless($taxValid, 422, 'Only standard-rated products have a positive VAT rate.');
            if ($data['status'] === 'active') {
                abort_unless($vendor->status === 'active' && $category->status === 'active', 409, 'Activate the vendor and product category first.');
                $this->activation($vendor);
                abort_unless($vendor->vat_registered ? $data['vat_kind'] !== 'none' : $data['vat_kind'] === 'none', 422, 'The product tax treatment must match the vendor VAT registration.');
            }
            $row->fill(array_diff_key($data, array_flip(['id', 'operation_id', 'revision', 'price'])));
            $row->currency = $vendor->currency;
            $row->price_cents = Money::fromDecimalString($data['price'], $vendor->currency)->cents;
            $row->revision = $data['revision'] + 1;
            $row->changed_by = $actor->id;
            $creating = ! $row->exists;
            $row->save();
            if ($creating && $row->tracks_stock) {
                VendorStockBalance::create(['product_id' => $row->id, 'on_hand' => 0, 'revision' => 0]);
            }
            Audit::record('vendors.product_saved', $row, [], ['revision' => $row->revision, 'status' => $row->status]);

            return ['id' => (int) $row->id];
        });

        return VendorProduct::findOrFail($receipt['id']);
    }

    public function stock(User $actor, array $input): VendorStockMovement
    {
        $data = $this->data('stock', $input);
        $receipt = app(VendorOperations::class)->once($actor, 'stock', $data, function () use ($actor, $data): array {
            $row = VendorProduct::lockForUpdate()->findOrFail($data['product_id']);
            abort_unless($row->tracks_stock, 409, 'This product does not track stock.');
            abort_unless($data['source_reference'] === trim($data['source_reference']) && ! str_contains($data['source_reference'], '  '), 422, 'Use a canonical source reference.');
            abort_if(VendorStockMovement::where('product_id', $row->id)->where('source_reference', $data['source_reference'])->exists(), 409, 'This physical stock reference was already recorded.');
            $balance = VendorStockBalance::where('product_id', $row->id)->lockForUpdate()->sole();
            $actual = app(VendorInventory::class)->actual((int) $row->id);
            abort_unless($actual === $balance->on_hand, 409, 'Reconcile stock before changing it.');
            $after = $actual + $data['units_delta'];
            abort_unless($after >= 0 && $after <= 1000000000, 409, 'Stock cannot become negative or exceed the supported quantity.');
            abort_unless(($data['kind'] !== 'receipt' || $data['units_delta'] > 0) && ($data['kind'] !== 'waste' || $data['units_delta'] < 0), 422, 'Receipt adds stock and waste removes it.');
            $movement = VendorStockMovement::create($data + ['actor_id' => $actor->id, 'before_units' => $actual, 'after_units' => $after, 'created_at' => now('UTC')]);
            Audit::record('vendors.stock_recorded', $movement, [], ['product_id' => $row->id, 'units_delta' => $data['units_delta']]);

            return ['id' => (int) $movement->id];
        });

        return VendorStockMovement::findOrFail($receipt['id']);
    }

    /** Active school storefront details only; private legal, bank and stock reasons stay server-side. */
    public function storefront(User $actor, int $vendorId, int $page = 1): array
    {
        return DB::transaction(function () use ($actor, $vendorId, $page): array {
            $fresh = app(VendorPolicy::class)->actor($actor);
            app(WalletPolicy::class)->activePerson($fresh);
            $vendor = Vendor::where('status', 'active')->whereIn('category_id', VendorCategory::where('status', 'active')->select('id'))->findOrFail($vendorId);
            $products = VendorProduct::where('vendor_id', $vendor->id)->where('status', 'active')->whereIn('category_id', VendorCategory::where('status', 'active')->select('id'))->orderBy('id')->paginate(30, ['*'], 'page', $page);
            $items = $products->map(function (VendorProduct $p): array {
                return ['id' => (int) $p->id, 'name' => $p->name, 'description' => $p->description, 'sku' => $p->sku, 'unit_label' => $p->unit_label, 'category_key' => VendorCategory::findOrFail($p->category_id)->key, 'currency' => $p->currency, 'price_cents' => $p->price_cents, 'vat_kind' => $p->vat_kind, 'vat_rate_bps' => $p->vat_rate_bps, 'in_stock' => ! $p->tracks_stock || VendorStockBalance::where('product_id', $p->id)->where('on_hand', '>', 0)->exists()];
            })->all();

            return ['school_id' => app(CurrentSchool::class)->id(), 'vendor' => ['id' => (int) $vendor->id, 'name' => $vendor->name, 'description' => $vendor->description, 'kind' => $vendor->kind], 'items' => $items, 'page' => $products->currentPage(), 'last_page' => $products->lastPage()];
        });
    }

    private function revision(int $actual, int $expected): void
    {
        abort_unless($actual === $expected, 409, 'This catalogue record changed. Review its current revision.');
    }
}
