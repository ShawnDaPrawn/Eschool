<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorSettlement;
use App\Models\Commerce\VendorSettlementApproval;
use App\Models\Commerce\VendorSettlementLine;
use App\Models\Commerce\VendorSettlementOutcome;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Money\CashCustody;
use App\Services\Money\ExternalDisbursements;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class VendorSettlements
{
    public static function prepareRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'vendor_id' => ['required', 'integer', 'min:1'], 'vendor_revision' => ['required', 'integer', 'min:1'], 'period_from' => ['required', 'date_format:Y-m-d'], 'period_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_from'], 'order_ids' => ['required', 'array', 'min:1', 'max:500'], 'order_ids.*' => ['required', 'integer', 'min:1', 'distinct'], 'confirmed_original_terms' => ['required', 'accepted']];
    }

    public static function confirmRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'confirmed' => ['required', 'accepted']];
    }

    public static function finishRules(): array
    {
        return self::confirmRules() + ['paid_on' => ['required', 'date_format:Y-m-d'], 'payout_reference' => ['nullable', 'string', 'min:3', 'max:191'], 'private_evidence' => ['required', 'string', 'min:10', 'max:4000'], 'verified_original_vendor' => ['required', 'accepted'], 'verified_actual_payout' => ['sometimes', 'accepted']];
    }

    private function period(array $data): array
    {
        $tz = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
        abort_unless($data['period_to'] <= now($tz)->toDateString(), 422, 'Choose a completed or current reporting period.');
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $data['period_from'], $tz)->utc();
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $data['period_to'], $tz)->addDay()->utc();
        abort_if($start->diffInDays($end) > 366, 422, 'Use a reporting period no longer than one year.');

        return [$start, $end];
    }

    public function eligible(User $actor, int $vendor, array $period): array
    {
        $data = Validator::make($period, ['period_from' => ['required', 'date_format:Y-m-d'], 'period_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:period_from']])->validate();

        return DB::transaction(function () use ($actor, $vendor, $data): array {
            app(VendorPolicy::class)->finance($actor);
            $row = Vendor::lockForUpdate()->findOrFail($vendor);
            [$start,$end] = $this->period($data);
            $due = now('UTC')->subDays($row->settlement_delay_days ?? 0);

            return $this->orders($vendor, $start, $end, $due)->orderBy('id')->limit(501)->get()->all();
        });
    }

    /** @return Builder<VendorOrder> */
    private function orders(int $vendor, CarbonImmutable $start, CarbonImmutable $end, CarbonInterface $due): Builder
    {
        return VendorOrder::where('vendor_id', $vendor)->whereNotNull('sealed_at')->whereExists(fn ($q) => $q->selectRaw('1')->from('vendor_order_actions as a')->whereColumn('a.school_id', 'vendor_orders.school_id')->whereColumn('a.order_id', 'vendor_orders.id')->where('a.action', 'collected')->where('a.created_at', '>=', $start)->where('a.created_at', '<', $end)->where('a.created_at', '<=', $due))->whereNotExists(fn ($q) => $q->selectRaw('1')->from('vendor_settlement_lines as l')->whereColumn('l.school_id', 'vendor_orders.school_id')->whereColumn('l.order_id', 'vendor_orders.id'));
    }

    public function prepare(User $actor, array $input): VendorSettlement
    {
        $data = Validator::make($input, self::prepareRules())->validate();
        foreach (['vendor_id', 'vendor_revision'] as $key) {
            $data[$key] = (int) $data[$key];
        }$data['order_ids'] = array_map(intval(...), $data['order_ids']);
        sort($data['order_ids']);
        $data['confirmed_original_terms'] = true;
        $receipt = app(VendorOperations::class)->once($actor, 'settlement_prepare', $data, function () use ($actor, $data): array {
            $vendor = Vendor::lockForUpdate()->findOrFail($data['vendor_id']);
            abort_unless($vendor->status === 'active' && $vendor->revision === $data['vendor_revision'], 409, 'Review current vendor terms before preparing this settlement.');
            abort_unless($vendor->refund_bearer !== null && $vendor->chargeback_bearer !== null && $vendor->settlement_delay_days !== null && $vendor->settlement_terms !== null && in_array($vendor->settlement_method, ['school_internal', 'cash', 'eft'], true), 422, 'Review explicit vendor settlement and liability terms.');
            [$start,$end] = $this->period($data);
            $due = now('UTC')->subDays($vendor->settlement_delay_days);
            $orders = $this->orders($vendor->id, $start, $end, $due)->whereIn('id', $data['order_ids'])->orderBy('id')->lockForUpdate()->get();
            abort_unless($orders->count() === count($data['order_ids']), 409, 'One of these orders is not collected, due or available for settlement.');
            $gross = (int) $orders->sum('amount_cents');
            $net = (int) $orders->sum('net_cents');
            $vat = (int) $orders->sum('vat_cents');
            abort_if($gross > 100000000000, 422, 'Use a smaller settlement batch.');
            $ledger = app(LedgerService::class);
            foreach ([[AccountType::Clearing, 'clearing'], [AccountType::DueToSchool, 'due-school']] as [$type,$ref]) {
                $id = $ledger->openAccount($type, 'vendor:collection:'.$ref.':'.$vendor->currency, $vendor->currency, module: 'vendors');
                abort_unless($this->soundBalance($id) === 0, 409, 'Phase6 collection accounts must remain at zero.');
            }
            $row = VendorSettlement::create(['vendor_id' => $vendor->id, 'operation_id' => $data['operation_id'], 'vendor_revision' => $vendor->revision, 'method' => $vendor->settlement_method, 'currency' => $vendor->currency, 'period_from' => $data['period_from'], 'period_to' => $data['period_to'], 'period_start_utc' => $start, 'period_end_utc' => $end, 'eligible_through' => $due, 'order_count' => $orders->count(), 'gross_cents' => $gross, 'sales_net_cents' => $net, 'vat_cents' => $vat, 'collected_by' => 'school', 'collector_fee_cents' => 0, 'payout_cents' => $gross, 'private_vendor_snapshot' => ['name' => $vendor->name, 'legal_name' => $vendor->legal_name, 'kind' => $vendor->kind, 'refund_bearer' => $vendor->refund_bearer, 'chargeback_bearer' => $vendor->chargeback_bearer, 'settlement_terms' => $vendor->settlement_terms, 'private_bank_instructions' => $vendor->private_bank_instructions], 'prepared_by' => $actor->id, 'created_at' => now('UTC')]);
            foreach ($orders as $order) {
                VendorSettlementLine::create(['settlement_id' => $row->id, 'order_id' => $order->id, 'gross_cents' => $order->amount_cents, 'sales_net_cents' => $order->net_cents, 'vat_cents' => $order->vat_cents]);
            }
            $row->forceFill(['sealed_at' => now('UTC')])->save();

            return ['id' => (int) $row->id];
        });

        return VendorSettlement::findOrFail($receipt['id']);
    }

    public function approve(User $actor, int $id, array $input): VendorSettlement
    {
        $data = Validator::make($input, self::confirmRules())->validate();
        $data['settlement_id'] = $id;
        $data['confirmed'] = true;
        app(VendorOperations::class)->once($actor, 'settlement_approve', $data, function () use ($actor, $id, $data): array {
            $row = VendorSettlement::lockForUpdate()->findOrFail($id);
            abort_unless($row->sealed_at !== null, 409);
            abort_if($row->prepared_by === (int) $actor->id, 403, 'A different Finance operator must approve this settlement.');
            abort_if(VendorSettlementApproval::where('settlement_id', $id)->exists() || VendorSettlementOutcome::where('settlement_id', $id)->exists(), 409, 'This settlement already has an approval or final outcome.');
            VendorSettlementApproval::create(['settlement_id' => $id, 'actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'created_at' => now('UTC')]);

            return ['id' => $id];
        });

        return VendorSettlement::findOrFail($id);
    }

    public function finish(User $actor, int $id, array $input): VendorSettlement
    {
        $data = Validator::make($input, self::finishRules())->validate();
        $data['settlement_id'] = $id;
        $data['confirmed'] = true;
        $data['verified_original_vendor'] = true;
        app(VendorOperations::class)->once($actor, 'settlement_finish', $data, function () use ($actor, $id, $data): array {
            $row = VendorSettlement::lockForUpdate()->findOrFail($id);
            $approval = VendorSettlementApproval::where('settlement_id', $id)->lockForUpdate()->firstOrFail();
            app(VendorPolicy::class)->finance(User::findOrFail($approval->actor_id), true);
            abort_if(VendorSettlementOutcome::where('settlement_id', $id)->exists(), 409, 'This settlement already has a final outcome.');
            $tz = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
            abort_unless($data['paid_on'] <= now($tz)->toDateString() && $data['paid_on'] >= $row->created_at->setTimezone($tz)->toDateString(), 422, 'Record an actual settlement date on or after preparation and no later than today.');
            abort_unless(VendorSettlementReconciliation::healthy(app(VendorSettlementReconciliation::class)->inspect()), 409, 'Reconcile original settlement history before posting.');
            $vendor = Vendor::lockForUpdate()->findOrFail($row->vendor_id);
            $ledger = app(LedgerService::class);
            abort_unless($this->soundBalance($vendor->ledger_account_id) >= $row->gross_cents, 409, 'Reconcile the original merchant payable.');
            $entry = $counter = $tax = $hash = $custodian = null;
            if ($row->gross_cents > 0) {
                $postings = [Posting::debit($vendor->ledger_account_id, Money::ofCents($row->gross_cents, $row->currency))];
                if ($row->method === 'school_internal') {
                    $counter = $ledger->openAccount(AccountType::FeeIncome, 'vendor:sales:'.$row->currency, $row->currency, module: 'vendors');
                    $postings[] = Posting::credit($counter, Money::ofCents($row->sales_net_cents, $row->currency));
                    if ($row->vat_cents > 0) {
                        $tax = $ledger->openAccount(AccountType::FeeVat, 'vendor:vat:'.$row->currency, $row->currency, module: 'vendors');
                        $postings[] = Posting::credit($tax, Money::ofCents($row->vat_cents, $row->currency));
                    }
                } else {
                    Validator::make($data, ['payout_reference' => ['required', 'string', 'min:3', 'max:191'], 'verified_actual_payout' => ['required', 'accepted']])->validate();
                    abort_unless(app(ModuleGate::class)->writable('wallet', locked: true), 403, 'Wallet backing is read-only.');
                    $hash = app(ExternalDisbursements::class)->available($row->method, $row->currency, $data['payout_reference']);
                    $counter = $ledger->openAccount($row->method === 'cash' ? AccountType::CashDesk : AccountType::WalletBank, 'wallet:'.$row->method.':'.$row->currency, $row->currency, module: 'wallet');
                    abort_unless($this->soundBalance($counter) >= $row->payout_cents, 422, 'Reconcile and fund the actual payout asset before recording payment.');
                    if ($row->method === 'cash') {
                        app(CashCustody::class)->requirePayout($actor, $row->currency, $row->payout_cents, $data['paid_on']);
                    }
                    $custodian = $row->method === 'cash' ? (int) $actor->id : null;
                    $postings[] = Posting::credit($counter, Money::ofCents($row->payout_cents, $row->currency));
                }
                $source = 'vendor:settlement:'.$data['operation_id'];
                $entry = $ledger->post($source, $source, 'Original collected vendor settlement', $postings);
            }
            VendorSettlementOutcome::create(['settlement_id' => $id, 'actor_id' => $actor->id, 'operation_id' => $data['operation_id'], 'ledger_entry_id' => $entry, 'counter_account_id' => $counter, 'vat_account_id' => $tax, 'cash_operator_id' => $custodian, 'reference_hash' => $hash, 'paid_on' => $data['paid_on'], 'private_evidence' => ['reference' => $data['payout_reference'] ?? null, 'verification' => $data['private_evidence']], 'created_at' => now('UTC')]);

            return ['id' => $id];
        });

        return VendorSettlement::findOrFail($id);
    }

    public function listingForOperator(User $actor, int $vendor, array $input): array
    {
        app(VendorOperatorAccess::class)->counter($actor, $vendor);
        $rows = VendorSettlement::where('vendor_id', $vendor)->whereNotNull('sealed_at')->orderByDesc('id')->paginate(20, ['*'], 'page', (int) ($input['page'] ?? 1));

        return $this->scope($actor, $vendor) + ['items' => $rows->map(fn ($row) => $this->project($row))->all(), 'page' => $rows->currentPage(), 'last_page' => $rows->lastPage()];
    }

    public function receiptForOperator(User $actor, int $vendor, int $id): array
    {
        app(VendorOperatorAccess::class)->counter($actor, $vendor);
        $row = VendorSettlement::where('vendor_id', $vendor)->whereNotNull('sealed_at')->findOrFail($id);
        $lines = VendorSettlementLine::where('settlement_id', $row->id)->orderBy('order_id')->get()->map(function ($line): array {
            $order = VendorOrder::findOrFail($line->order_id);

            return ['order_number' => $order->order_number, 'gross_cents' => $line->gross_cents, 'sales_net_cents' => $line->sales_net_cents, 'vat_cents' => $line->vat_cents];
        })->all();

        return $this->scope($actor, $vendor) + $this->project($row) + ['lines' => $lines];
    }

    private function scope(User $actor, int $vendor): array
    {
        return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'vendor_id' => $vendor];
    }

    private function project(VendorSettlement $row): array
    {
        $outcome = VendorSettlementOutcome::where('settlement_id', $row->id)->first();

        return ['id' => (int) $row->id, 'method' => $row->method, 'currency' => $row->currency, 'period_from' => $row->period_from, 'period_to' => $row->period_to, 'order_count' => $row->order_count, 'gross_cents' => $row->gross_cents, 'sales_net_cents' => $row->sales_net_cents, 'vat_cents' => $row->vat_cents, 'collected_by' => $row->collected_by, 'collector_fee_cents' => $row->collector_fee_cents, 'payout_cents' => $row->payout_cents, 'status' => $this->state($row), 'created_at' => $row->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'settled_at' => $outcome?->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'paid_on' => $outcome?->paid_on];
    }

    public function state(VendorSettlement $row): string
    {
        return VendorSettlementOutcome::where('settlement_id', $row->id)->exists() ? 'settled' : (VendorSettlementApproval::where('settlement_id', $row->id)->exists() ? 'approved' : 'prepared');
    }

    private function soundBalance(int $account): int
    {
        $actual = app(LedgerService::class)->balance($account)->cents;
        $cached = DB::table('ledger_accounts')->where('school_id', app(CurrentSchool::class)->id())->where('id', $account)->lockForUpdate()->value('balance_cents');
        abort_unless($cached !== null && (int) $cached === $actual, 409, 'Reconcile financial projections before settlement.');

        return $actual;
    }
}
