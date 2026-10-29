<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorCatalogueRequest;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorCategory;
use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\VendorStockMovement;
use App\Models\Money\WalletSetting;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VendorCataloguePanelController extends Controller
{
    public function index(VendorCatalogueRequest $request): Response
    {
        return DB::transaction(function () use ($request): Response {
            app(VendorPolicy::class)->finance($request->user());
            $vendor = $request->filled('vendor_id') ? Vendor::findOrFail((int) $request->input('vendor_id')) : null;
            $product = $request->filled('product_id') ? VendorProduct::findOrFail((int) $request->input('product_id')) : null;
            if ($product !== null) {
                abort_if($vendor !== null && $product->vendor_id !== (int) $vendor->id, 404);
                $vendor ??= Vendor::findOrFail($product->vendor_id);
            }
            $category = $request->filled('category_id') ? VendorCategory::findOrFail((int) $request->input('category_id')) : null;
            $categoryOptions = VendorCategory::orderBy('name')->get()->mapWithKeys(fn ($c) => [$c->id => $c->name.' · '.$c->key])->all();
            $vendors = Vendor::orderBy('name')->paginate(30);
            $products = $vendor ? VendorProduct::where('vendor_id', $vendor->id)->orderBy('name')->paginate(30, ['*'], 'products_page') : null;
            $stock = $product ? VendorStockBalance::where('product_id', $product->id)->first() : null;
            $movements = $product ? VendorStockMovement::where('product_id', $product->id)->orderByDesc('id')->paginate(30, ['*'], 'stock_page') : null;
            $canWrite = app(ModuleGate::class)->writable('vendors');
            $walletCurrency = WalletSetting::value('currency');
            $canConfigureWallets = $request->user()->can('wallet-finance-manage') && app(ModuleGate::class)->allows('wallet');

            return response()->view('money.vendors.catalogues', compact('vendor', 'product', 'category', 'categoryOptions', 'vendors', 'products', 'stock', 'movements', 'canWrite', 'walletCurrency', 'canConfigureWallets'));
        });
    }

    public function save(VendorCatalogueRequest $request): Response
    {
        $service = app(VendorCatalogues::class);
        $data = $request->safe()->except(['expected_account_id', 'expected_school_id']);
        $kind = $request->route('kind');
        $row = match ($kind) {
            'category' => $service->category($request->user(), $data),
            'vendor' => $service->vendor($request->user(), $data),
            'product' => $service->product($request->user(), $data),
            'stock' => $service->stock($request->user(), $data),
            default => abort(404),
        };
        $query = match ($kind) {
            'category' => ['category_id' => $row->id],
            'vendor' => ['vendor_id' => $row->id],
            'product' => ['vendor_id' => $row->vendor_id, 'product_id' => $row->id],
            'stock' => ['product_id' => $row->product_id],
        };

        return redirect()->route('vendor-catalogues.index', $query)->with('success', __('Vendor catalogue change saved.'));
    }
}
