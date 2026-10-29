<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class VendorSettlementReconciliation
{
    public function inspect(): array
    {
        $report = ['vendor_settlement_mismatches' => 0, 'unattributed_vendor_settlement_entries' => 0, 'vendor_disbursement_mismatches' => 0, 'vendor_collection_phase_mismatches' => 0];
        if (! Schema::hasTable('vendor_settlements')) {
            return $report;
        }
        $school = app(CurrentSchool::class)->id();
        foreach (DB::table('vendor_settlements')->where('school_id', $school)->get() as $s) {
            $lines = DB::table('vendor_settlement_lines')->where('school_id', $school)->where('settlement_id', $s->id)->get();
            $vendor = DB::table('vendors')->where('school_id', $school)->where('id', $s->vendor_id)->first();
            $approval = DB::table('vendor_settlement_approvals')->where('school_id', $school)->where('settlement_id', $s->id)->first();
            $outcome = DB::table('vendor_settlement_outcomes')->where('school_id', $school)->where('settlement_id', $s->id)->first();
            $sound = $s->sealed_at !== null && $vendor !== null && $vendor->currency === $s->currency && (int) $s->order_count === $lines->count() && (int) $s->gross_cents === (int) $lines->sum('gross_cents') && (int) $s->sales_net_cents === (int) $lines->sum('sales_net_cents') && (int) $s->vat_cents === (int) $lines->sum('vat_cents') && (int) $s->gross_cents === (int) $s->sales_net_cents + (int) $s->vat_cents && $s->collected_by === 'school' && (int) $s->collector_fee_cents === 0 && (int) $s->payout_cents === (int) $s->gross_cents;
            foreach ($lines as $line) {
                $o = DB::table('vendor_orders')->where('school_id', $school)->where('id', $line->order_id)->first();
                $a = DB::table('vendor_order_actions')->where('school_id', $school)->where('order_id', $line->order_id)->first();
                $sound = $sound && $o !== null && $o->sealed_at !== null && (int) $o->vendor_id === (int) $s->vendor_id && $o->currency === $s->currency && (int) $o->amount_cents === (int) $line->gross_cents && (int) $o->net_cents === (int) $line->sales_net_cents && (int) $o->vat_cents === (int) $line->vat_cents && $a !== null && $a->action === 'collected' && $a->created_at >= $s->period_start_utc && $a->created_at < $s->period_end_utc && $a->created_at <= $s->eligible_through;
            }
            if ($approval !== null) {
                $sound = $sound && (int) $approval->actor_id !== (int) $s->prepared_by;
            }
            if ($outcome !== null) {
                $sound = $sound && $approval !== null && $this->entry($s, $outcome, $vendor);
                if (in_array($s->method, ['cash', 'eft'], true) && (int) $s->payout_cents > 0) {
                    $registry = DB::table('financial_disbursements')->where('school_id', $school)->where('domain', 'vendors')->where('record_id', $outcome->id)->where('identity_hash', $outcome->reference_hash)->where('method', $s->method)->where('currency', $s->currency)->where('amount_cents', $s->payout_cents)->where('ledger_entry_id', $outcome->ledger_entry_id)->exists();
                    if (! $registry) {
                        $report['vendor_disbursement_mismatches']++;
                    }
                }
            }
            if (! $sound) {
                $report['vendor_settlement_mismatches']++;
            }
        }
        $report['unattributed_vendor_settlement_entries'] = DB::table('ledger_entries as e')->where('e.school_id', $school)->where('e.source', 'like', 'vendor:settlement:%')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('vendor_settlement_outcomes as o')->whereColumn('o.school_id', 'e.school_id')->whereColumn('o.ledger_entry_id', 'e.id'))->count();
        $report['vendor_disbursement_mismatches'] += DB::table('financial_disbursements as d')->where('d.school_id', $school)->where('d.domain', 'vendors')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('vendor_settlement_outcomes as o')->join('vendor_settlements as s', fn ($j) => $j->on('s.school_id', '=', 'o.school_id')->on('s.id', '=', 'o.settlement_id'))->whereColumn('o.school_id', 'd.school_id')->whereColumn('o.id', 'd.record_id')->whereColumn('o.reference_hash', 'd.identity_hash')->whereColumn('o.ledger_entry_id', 'd.ledger_entry_id')->whereColumn('s.method', 'd.method')->whereColumn('s.currency', 'd.currency')->whereColumn('s.payout_cents', 'd.amount_cents'))->count();
        foreach (DB::table('vendor_settlements')->where('school_id', $school)->distinct()->pluck('currency') as $currency) {
            foreach (['clearing' => 'clearing', 'due-school' => 'due_to_school'] as $ref => $type) {
                $a = DB::table('ledger_accounts')->where('school_id', $school)->where('reference', 'vendor:collection:'.$ref.':'.$currency)->first();
                $postings = $a === null ? 1 : DB::table('ledger_postings')->where('school_id', $school)->where('account_id', $a->id)->count();
                if ($a === null || $a->type !== $type || $a->module !== 'vendors' || $a->currency !== $currency || (int) $a->balance_cents !== 0 || $postings !== 0) {
                    $report['vendor_collection_phase_mismatches']++;
                }
            }
        }

        return $report;
    }

    private function entry(object $s, object $o, ?object $v): bool
    {
        $school = app(CurrentSchool::class)->id();
        if ((int) $s->gross_cents === 0) {
            return $o->ledger_entry_id === null && $o->reference_hash === null && $o->counter_account_id === null && $o->vat_account_id === null && $o->cash_operator_id === null;
        }
        if ($v === null) {
            return false;
        }
        $e = DB::table('ledger_entries')->where('school_id', $school)->where('id', $o->ledger_entry_id)->first();
        $p = DB::table('ledger_postings')->where('school_id', $school)->where('entry_id', $o->ledger_entry_id)->get();
        $internal = $s->method === 'school_internal';
        if ($e === null || $e->posted_at === null || $e->source !== 'vendor:settlement:'.$o->operation_id || $e->currency !== $s->currency || $p->count() !== ($internal && (int) $s->vat_cents > 0 ? 3 : 2)) {
            return false;
        }
        if ($p->where('account_id', $v->ledger_account_id)->where('direction', 'debit')->where('cents', $s->gross_cents)->count() !== 1) {
            return false;
        }
        $counter = DB::table('ledger_accounts')->where('school_id', $school)->where('id', $o->counter_account_id)->first();
        if ($counter === null || $counter->currency !== $s->currency || $counter->owner_user_id !== null || $p->where('account_id', $counter->id)->where('direction', 'credit')->where('cents', $internal ? $s->sales_net_cents : $s->payout_cents)->count() !== 1) {
            return false;
        }
        if (! $internal) {
            return $counter->module === 'wallet' && $counter->type === ($s->method === 'cash' ? 'cash_desk' : 'wallet_bank') && $counter->reference === 'wallet:'.$s->method.':'.$s->currency && $o->reference_hash !== null && $o->vat_account_id === null && ($s->method === 'cash' ? (int) $o->cash_operator_id === (int) $o->actor_id : $o->cash_operator_id === null);
        }
        if ($counter->module !== 'vendors' || $counter->type !== 'fee_income' || $counter->reference !== 'vendor:sales:'.$s->currency || $o->reference_hash !== null || $o->cash_operator_id !== null) {
            return false;
        }
        if ((int) $s->vat_cents === 0) {
            return $o->vat_account_id === null;
        }
        $tax = DB::table('ledger_accounts')->where('school_id', $school)->where('id', $o->vat_account_id)->first();

        return $tax !== null && $tax->module === 'vendors' && $tax->type === 'fee_vat' && $tax->currency === $s->currency && $tax->reference === 'vendor:vat:'.$s->currency && $tax->owner_user_id === null && $p->where('account_id', $tax->id)->where('direction', 'credit')->where('cents', $s->vat_cents)->count() === 1;
    }

    public static function healthy(array $report): bool
    {
        foreach (['vendor_settlement_mismatches', 'unattributed_vendor_settlement_entries', 'vendor_disbursement_mismatches', 'vendor_collection_phase_mismatches'] as $key) {
            if (($report[$key] ?? -1) !== 0) {
                return false;
            }
        }

        return true;
    }
}
