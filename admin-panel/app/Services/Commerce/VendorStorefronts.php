<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorCategory;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\Wallets;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class VendorStorefronts
{
    private function actor(User $actor): void
    {
        $fresh = app(VendorPolicy::class)->actor($actor);
        app(WalletPolicy::class)->activePerson($fresh);
    }

    public function index(User $actor, int $page = 1): array
    {
        return DB::transaction(function () use ($actor, $page): array {
            $this->actor($actor);
            $rows = Vendor::where('status', 'active')->whereIn('category_id', VendorCategory::where('status', 'active')->select('id'))->orderBy('name')->paginate(30, ['id', 'name', 'description', 'kind', 'currency'], 'page', $page);

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'can_purchase' => app(ModuleGate::class)->writable('vendors') && app(ModuleGate::class)->writable('wallet'), 'items' => $rows->map(fn ($v) => ['id' => (int) $v->id, 'name' => $v->name, 'description' => $v->description, 'kind' => $v->kind, 'currency' => $v->currency])->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
        });
    }

    public function catalogue(User $actor, int $vendor, int $page = 1): array
    {
        return DB::transaction(function () use ($actor, $vendor, $page): array {
            $this->actor($actor);

            return app(VendorCatalogues::class)->storefront($actor, $vendor, $page) + ['actor_user_id' => (int) $actor->id, 'can_purchase' => app(ModuleGate::class)->writable('vendors') && app(ModuleGate::class)->writable('wallet')];
        });
    }

    public function orders(User $actor, int $page = 1): array
    {
        return DB::transaction(function () use ($actor, $page): array {
            $this->actor($actor);
            $accessible = app(Wallets::class)->accessible($actor);
            $ids = [];
            foreach ($accessible['owners'] as $owner) {
                foreach ($owner['wallets'] as $wallet) {
                    $ids[] = $wallet['id'];
                }
            }
            $rows = VendorOrder::whereIn('wallet_account_id', $ids)->whereNotNull('sealed_at')->orderByDesc('id')->paginate(30, ['*'], 'page', $page);

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'read_only' => ! app(ModuleGate::class)->writable('vendors') || ! app(ModuleGate::class)->writable('wallet'), 'items' => $rows->map(fn ($o) => $this->receipt($actor, (int) $o->id))->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
        });
    }

    public function receipt(User $actor, int $id): array
    {
        return DB::transaction(function () use ($actor, $id): array {
            $order = app(VendorOrders::class)->authorizeOrder($actor, $id);
            $action = VendorOrderAction::where('order_id', $order->id)->first();
            $state = app(VendorOrders::class)->state($order);
            $canCancel = $state === 'ready' && app(ModuleGate::class)->writable('vendors') && app(ModuleGate::class)->writable('wallet');
            if ($canCancel) {
                try {
                    app(VendorOrders::class)->authorizeOrder($actor, $id, true);
                } catch (HttpExceptionInterface) {
                    $canCancel = false;
                }
            }
            $lines = VendorOrderLine::where('order_id', $order->id)->orderBy('id')->get()->map(fn ($l) => ['product_id' => $l->product_id, 'name' => $l->product_name, 'sku' => $l->sku, 'unit_label' => $l->unit_label, 'category_key' => $l->category_key, 'quantity' => $l->quantity, 'unit_price_cents' => $l->unit_price_cents, 'gross_cents' => $l->gross_cents, 'net_cents' => $l->net_cents, 'vat_cents' => $l->vat_cents, 'vat_kind' => $l->vat_kind, 'vat_rate_bps' => $l->vat_rate_bps])->all();

            return ['id' => (int) $order->id, 'school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'operation_id' => $order->operation_id, 'order_number' => $order->order_number, 'wallet_id' => $order->wallet_account_id, 'wallet_owner_id' => $order->wallet_owner_id, 'learner_id' => $order->learner_id, 'vendor_id' => $order->vendor_id, 'currency' => $order->currency, 'amount_cents' => $order->amount_cents, 'net_cents' => $order->net_cents, 'vat_cents' => $order->vat_cents, 'status' => $state, 'can_cancel' => $canCancel, 'qr' => $state === 'ready' ? 'navigateed:order:'.$order->private_qr_token : null, 'created_at' => $order->created_at->toISOString(), 'processed_at' => $action?->created_at->toISOString(), 'seller' => array_intersect_key($order->seller_snapshot, array_flip(['id', 'name', 'legal_name', 'legal_address', 'kind', 'vat_registered', 'vat_number'])), 'lines' => $lines];
        });
    }
}
