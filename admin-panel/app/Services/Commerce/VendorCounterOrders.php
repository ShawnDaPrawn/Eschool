<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\VendorOperation;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Platform\School;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class VendorCounterOrders
{
    public static function lookupRules(): array
    {
        return ['order_number' => ['nullable', 'string', 'regex:/\AVO-\d{6,20}\z/D', 'required_without:qr_token'], 'qr_token' => ['nullable', 'string', 'regex:/\A[a-f0-9]{64}\z/D', 'required_without:order_number']];
    }

    public function listing(User $actor, int $vendor, array $input): array
    {
        app(VendorOperatorAccess::class)->counter($actor, $vendor);
        $state = $input['status'] ?? 'ready';
        $rows = VendorOrder::where('vendor_id', $vendor)->whereNotNull('sealed_at')
            ->when($state === 'ready', fn ($q) => $q->whereNotExists(fn ($a) => $a->selectRaw('1')->from('vendor_order_actions')->whereColumn('vendor_order_actions.school_id', 'vendor_orders.school_id')->whereColumn('vendor_order_actions.order_id', 'vendor_orders.id')),
                fn ($q) => $q->whereExists(fn ($a) => $a->selectRaw('1')->from('vendor_order_actions')->whereColumn('vendor_order_actions.school_id', 'vendor_orders.school_id')->whereColumn('vendor_order_actions.order_id', 'vendor_orders.id')->where('action', $state)))
            ->orderByDesc('id')->paginate(20, ['*'], 'page', (int) ($input['page'] ?? 1));

        return $this->scope($actor, $vendor) + ['status' => $state, 'items' => $rows->map(fn ($o) => $this->project($actor, $vendor, $o))->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function lookup(User $actor, int $vendor, array $input): array
    {
        app(VendorOperatorAccess::class)->counter($actor, $vendor);
        $data = Validator::make($input, self::lookupRules())->validate();
        abort_if(! empty($data['order_number']) && ! empty($data['qr_token']), 422);
        $order = VendorOrder::where('vendor_id', $vendor)->whereNotNull('sealed_at')
            ->when(! empty($data['order_number']), fn ($q) => $q->where('order_number', $data['order_number']), fn ($q) => $q->where('qr_hash', hash('sha256', $data['qr_token'])))->firstOrFail();

        return $this->project($actor, $vendor, $order);
    }

    public function receipt(User $actor, int $vendor, int $order): array
    {
        app(VendorOperatorAccess::class)->counter($actor, $vendor);

        return $this->project($actor, $vendor, VendorOrder::where('vendor_id', $vendor)->whereNotNull('sealed_at')->findOrFail($order));
    }

    private function scope(User $actor, int $vendor): array
    {
        return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'vendor_id' => $vendor];
    }

    private function project(User $actor, int $vendor, VendorOrder $order): array
    {
        $counter = app(VendorOperatorAccess::class)->counter($actor, $vendor);
        $action = VendorOrderAction::where('order_id', $order->id)->first();
        $state = $action === null ? 'ready' : $action->action;

        return $this->scope($actor, $vendor) + ['id' => (int) $order->id, 'order_number' => $order->order_number,
            'status' => $state, 'currency' => $order->currency, 'amount_cents' => $order->amount_cents, 'net_cents' => $order->net_cents, 'vat_cents' => $order->vat_cents,
            'created_at' => $order->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'processed_at' => $action?->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'can_collect' => $state === 'ready' && $counter->status === 'active' && app(ModuleGate::class)->writable('vendors'),
            'lines' => VendorOrderLine::where('order_id', $order->id)->orderBy('id')->get()->map(fn ($l) => ['product_name' => $l->product_name, 'sku' => $l->sku, 'unit_label' => $l->unit_label, 'quantity' => $l->quantity, 'gross_cents' => $l->gross_cents, 'net_cents' => $l->net_cents, 'vat_cents' => $l->vat_cents])->all()];
    }

    public function collect(User $actor, int $vendor, array $input): array
    {
        return DB::transaction(function () use ($actor, $vendor, $input): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(VendorOperatorAccess::class)->counter($actor, $vendor, true);
            $existing = VendorOrderAction::where('operation_id', $input['operation_id'] ?? '')->first();
            $order = app(VendorOrders::class)->collect($actor, $vendor, $input, operator: true);

            return $this->receipt($actor, $vendor, $order->id) + ['newly_collected' => $existing === null];
        }, 3);
    }

    public function resolve(User $actor, int $vendor, string $operation): array
    {
        Validator::make(['operation' => $operation], ['operation' => ['required', 'uuid']])->validate();

        return DB::transaction(function () use ($actor, $vendor, $operation): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(VendorOperatorAccess::class)->counter($actor, $vendor);
            $row = VendorOperation::where('operation_id', $operation)->lockForUpdate()->first();
            if ($row === null) {
                VendorOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'request_hash' => hash_hmac('sha256', 'abandoned_vendor_collection:'.$vendor.':'.$operation, (string) config('app.key')), 'receipt' => ['abandoned' => true, 'vendor_id' => $vendor], 'created_at' => now('UTC')]);
                $state = 'abandoned';
                $order = null;
            } else {
                abort_unless($row->actor_id === (int) $actor->id, 404);
                if (($row->receipt['abandoned'] ?? false) === true) {
                    abort_unless(($row->receipt['vendor_id'] ?? null) === $vendor, 404);
                    $state = 'abandoned';
                    $order = null;
                } else {
                    $id = $row->receipt['id'] ?? 0;
                    $action = VendorOrderAction::where('operation_id', $operation)->where('actor_id', $actor->id)->where('order_id', $id)->where('action', 'collected')->firstOrFail();
                    $order = $this->receipt($actor, $vendor, $action->order_id);
                    $state = 'committed';
                }
            }

            return $this->scope($actor, $vendor) + ['operation_id' => $operation, 'status' => $state, 'order' => $order];
        }, 3);
    }
}
