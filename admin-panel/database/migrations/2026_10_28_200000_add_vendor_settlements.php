<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_settlements', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('vendor_id');
            $t->foreign(['school_id', 'vendor_id'])->references(['school_id', 'id'])->on('vendors')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->unsignedInteger('vendor_revision');
            $t->string('method', 20);
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->date('period_from');
            $t->date('period_to');
            $t->dateTime('period_start_utc', 6);
            $t->dateTime('period_end_utc', 6);
            $t->dateTime('eligible_through', 6);
            $t->unsignedInteger('order_count');
            $t->bigInteger('gross_cents');
            $t->bigInteger('sales_net_cents');
            $t->bigInteger('vat_cents');
            $t->string('collected_by', 12)->default('school');
            $t->bigInteger('collector_fee_cents')->default(0);
            $t->bigInteger('payout_cents');
            $t->text('private_vendor_snapshot');
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->dateTime('sealed_at', 6)->nullable();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->index(['school_id', 'vendor_id', 'id']);
        });
        Schema::create('vendor_settlement_lines', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('settlement_id');
            $t->foreign(['school_id', 'settlement_id'])->references(['school_id', 'id'])->on('vendor_settlements')->restrictOnDelete();
            $t->unsignedBigInteger('order_id');
            $t->foreign(['school_id', 'order_id'])->references(['school_id', 'id'])->on('vendor_orders')->restrictOnDelete();
            $t->bigInteger('gross_cents');
            $t->bigInteger('sales_net_cents');
            $t->bigInteger('vat_cents');
            $t->unique(['school_id', 'order_id']);
        });
        Schema::create('vendor_settlement_approvals', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('settlement_id');
            $t->foreign(['school_id', 'settlement_id'])->references(['school_id', 'id'])->on('vendor_settlements')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'settlement_id']);
            $t->unique(['school_id', 'operation_id']);
        });
        Schema::create('vendor_settlement_outcomes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('settlement_id');
            $t->foreign(['school_id', 'settlement_id'])->references(['school_id', 'id'])->on('vendor_settlements')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            foreach (['ledger_entry_id', 'counter_account_id', 'vat_account_id'] as $column) {
                $t->unsignedBigInteger($column)->nullable();
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($column === 'ledger_entry_id' ? 'ledger_entries' : 'ledger_accounts')->restrictOnDelete();
            }
            $t->foreignId('cash_operator_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->char('reference_hash', 64)->nullable()->collation('utf8mb4_bin');
            $t->date('paid_on');
            $t->text('private_evidence');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'settlement_id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'reference_hash']);
            $t->unique(['school_id', 'ledger_entry_id']);
        });
        DB::statement("ALTER TABLE vendor_settlements ADD CONSTRAINT vendor_settlement_shape CHECK (vendor_revision>0 AND method IN ('school_internal','cash','eft') AND currency REGEXP '^[A-Z]{3}$' AND period_from<=period_to AND period_start_utc<period_end_utc AND order_count BETWEEN 1 AND 500 AND gross_cents BETWEEN 0 AND 100000000000 AND gross_cents=sales_net_cents+vat_cents AND sales_net_cents>=0 AND vat_cents>=0 AND collected_by='school' AND collector_fee_cents=0 AND payout_cents=gross_cents)");
        DB::unprepared("CREATE TRIGGER vendor_settlement_header_insert BEFORE INSERT ON vendor_settlements FOR EACH ROW BEGIN IF NEW.sealed_at IS NOT NULL OR NOT EXISTS(SELECT 1 FROM vendors WHERE school_id=NEW.school_id AND id=NEW.vendor_id AND revision=NEW.vendor_revision AND currency=NEW.currency AND settlement_method=NEW.method) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Settlement starts with original explicit vendor terms'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_settlement_line_insert BEFORE INSERT ON vendor_settlement_lines FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM vendor_settlements s JOIN vendor_orders o ON o.school_id=s.school_id AND o.vendor_id=s.vendor_id JOIN vendor_order_actions a ON a.school_id=o.school_id AND a.order_id=o.id WHERE s.school_id=NEW.school_id AND s.id=NEW.settlement_id AND s.sealed_at IS NULL AND o.id=NEW.order_id AND o.sealed_at IS NOT NULL AND a.action='collected' AND a.created_at>=s.period_start_utc AND a.created_at<s.period_end_utc AND a.created_at<=s.eligible_through AND o.currency=s.currency AND o.amount_cents=NEW.gross_cents AND o.net_cents=NEW.sales_net_cents AND o.vat_cents=NEW.vat_cents) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Settlement retains only eligible collected original order money'; END IF; END");
        $fields = Schema::getColumnListing('vendor_settlements');
        $same = implode(' AND ', array_map(fn ($f) => "NEW.$f <=> OLD.$f", array_diff($fields, ['sealed_at'])));
        DB::unprepared("CREATE TRIGGER vendor_settlement_seal BEFORE UPDATE ON vendor_settlements FOR EACH ROW BEGIN IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR NOT ($same) OR NEW.order_count<>(SELECT COUNT(*) FROM vendor_settlement_lines WHERE school_id=NEW.school_id AND settlement_id=NEW.id) OR NEW.gross_cents<>(SELECT SUM(gross_cents) FROM vendor_settlement_lines WHERE school_id=NEW.school_id AND settlement_id=NEW.id) OR NEW.vat_cents<>(SELECT SUM(vat_cents) FROM vendor_settlement_lines WHERE school_id=NEW.school_id AND settlement_id=NEW.id) OR NEW.sales_net_cents<>(SELECT SUM(sales_net_cents) FROM vendor_settlement_lines WHERE school_id=NEW.school_id AND settlement_id=NEW.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Settlement seal retains exact original reviewed totals'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_settlement_approval_insert BEFORE INSERT ON vendor_settlement_approvals FOR EACH ROW BEGIN IF NOT EXISTS(SELECT 1 FROM vendor_settlements s WHERE s.school_id=NEW.school_id AND s.id=NEW.settlement_id AND s.sealed_at IS NOT NULL AND s.prepared_by<>NEW.actor_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Another Finance operator approves the sealed batch'; END IF; END");
        DB::unprepared("CREATE TRIGGER vendor_settlement_outcome_insert BEFORE INSERT ON vendor_settlement_outcomes FOR EACH ROW BEGIN
            IF NOT EXISTS(SELECT 1 FROM vendor_settlements s JOIN vendor_settlement_approvals a ON a.school_id=s.school_id AND a.settlement_id=s.id WHERE s.school_id=NEW.school_id AND s.id=NEW.settlement_id AND s.sealed_at IS NOT NULL) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only independently approved original settlement can post'; END IF;
            IF EXISTS(SELECT 1 FROM vendor_settlements s WHERE s.school_id=NEW.school_id AND s.id=NEW.settlement_id AND ((s.gross_cents=0 AND (NEW.ledger_entry_id IS NOT NULL OR NEW.counter_account_id IS NOT NULL OR NEW.vat_account_id IS NOT NULL OR NEW.reference_hash IS NOT NULL OR NEW.cash_operator_id IS NOT NULL)) OR (s.gross_cents>0 AND NOT EXISTS(SELECT 1 FROM ledger_entries e JOIN vendors v ON v.school_id=s.school_id AND v.id=s.vendor_id WHERE e.school_id=s.school_id AND e.id=NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency=s.currency AND e.source=CONCAT('vendor:settlement:',NEW.operation_id) AND EXISTS(SELECT 1 FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id AND account_id=v.ledger_account_id AND direction='debit' AND cents=s.gross_cents) AND (SELECT COUNT(*) FROM ledger_postings WHERE school_id=e.school_id AND entry_id=e.id)=IF(s.method='school_internal' AND s.vat_cents>0,3,2) AND EXISTS(SELECT 1 FROM ledger_postings p JOIN ledger_accounts c ON c.school_id=p.school_id AND c.id=p.account_id WHERE p.school_id=e.school_id AND p.entry_id=e.id AND p.account_id=NEW.counter_account_id AND p.direction='credit' AND p.cents=IF(s.method='school_internal',s.sales_net_cents,s.gross_cents) AND c.owner_user_id IS NULL AND c.currency=s.currency AND ((s.method='school_internal' AND c.type='fee_income' AND c.module='vendors' AND c.reference=CONCAT('vendor:sales:',s.currency) AND NEW.reference_hash IS NULL AND NEW.cash_operator_id IS NULL AND ((s.vat_cents=0 AND NEW.vat_account_id IS NULL) OR (s.vat_cents>0 AND EXISTS(SELECT 1 FROM ledger_postings p2 JOIN ledger_accounts t ON t.school_id=p2.school_id AND t.id=p2.account_id WHERE p2.school_id=e.school_id AND p2.entry_id=e.id AND p2.account_id=NEW.vat_account_id AND p2.direction='credit' AND p2.cents=s.vat_cents AND t.type='fee_vat' AND t.module='vendors' AND t.currency=s.currency AND t.owner_user_id IS NULL AND t.reference=CONCAT('vendor:vat:',s.currency))))) OR (s.method IN ('cash','eft') AND c.module='wallet' AND c.type=IF(s.method='cash','cash_desk','wallet_bank') AND c.reference=CONCAT('wallet:',s.method,':',s.currency) AND NEW.reference_hash IS NOT NULL AND NEW.vat_account_id IS NULL AND ((s.method='cash' AND NEW.cash_operator_id=NEW.actor_id) OR (s.method='eft' AND NEW.cash_operator_id IS NULL))))))))) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Settlement outcome requires exact original merchant payable and payout postings'; END IF;
        END");
        foreach (['vendor_settlement_lines', 'vendor_settlement_approvals', 'vendor_settlement_outcomes'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_update BEFORE UPDATE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Settlement history is immutable'");
        }
        foreach (['vendor_settlements', 'vendor_settlement_lines', 'vendor_settlement_approvals', 'vendor_settlement_outcomes'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_no_delete BEFORE DELETE ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Settlement history is retained'");
        }
        $this->ledgerTypes(true);
        $this->registry(true);
        DB::unprepared("CREATE TRIGGER vendor_settlement_disbursement AFTER INSERT ON vendor_settlement_outcomes FOR EACH ROW BEGIN IF NEW.reference_hash IS NOT NULL THEN INSERT INTO financial_disbursements(school_id,identity_hash,domain,record_id,method,currency,amount_cents,ledger_entry_id,created_at) SELECT NEW.school_id,NEW.reference_hash,'vendors',NEW.id,s.method,s.currency,s.payout_cents,NEW.ledger_entry_id,NEW.created_at FROM vendor_settlements s WHERE s.school_id=NEW.school_id AND s.id=NEW.settlement_id; END IF; END");
    }

    private function ledgerTypes(bool $vendors): void
    {
        $drop = str_contains(DB::selectOne('SELECT VERSION() AS version')->version, 'MariaDB') ? 'CONSTRAINT' : 'CHECK';
        DB::statement("ALTER TABLE ledger_accounts DROP $drop ledger_account_rules");
        $extra = $vendors ? " OR (type IN ('fee_income','fee_vat') AND module IN ('vendors','ticketing'))" : '';
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk','fee_income','fee_bank','fee_vat','fee_refund','wallet_bank') AND currency REGEXP '^[A-Z]{3}$' AND normal_direction=CASE WHEN type IN ('fee_receivable','cash_desk','clearing','fee_bank','wallet_bank') THEN 'debit' ELSE 'credit' END AND ((type IN ('wallet','wallet_bank') AND module='wallet') OR (type='vendor' AND module='vendors') OR (type IN ('fee_receivable','family','fee_income','fee_bank','fee_vat','fee_refund') AND module='fees') OR (type IN ('cash_desk','clearing','due_to_school') AND module IN ('fees','wallet','vendors','ticketing')) $extra) AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000 AND (type NOT IN ('wallet','fee_refund') OR balance_cents>=0) AND (type NOT IN ('clearing','due_to_school') OR balance_cents=0))");
    }

    private function registry(bool $vendors): void
    {
        $drop = str_contains(DB::selectOne('SELECT VERSION() AS version')->version, 'MariaDB') ? 'CONSTRAINT' : 'CHECK';
        DB::statement("ALTER TABLE financial_disbursements DROP $drop financial_disbursement_shape");
        $domains = $vendors ? "'fees','wallet','vendors'" : "'fees','wallet'";
        DB::statement("ALTER TABLE financial_disbursements ADD CONSTRAINT financial_disbursement_shape CHECK (domain IN ($domains) AND method IN ('cash','eft') AND currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND CHAR_LENGTH(identity_hash)=64)");
        DB::unprepared('DROP TRIGGER financial_disbursement_insert');
        $vendor = $vendors ? " OR (NEW.domain='vendors' AND NOT EXISTS(SELECT 1 FROM vendor_settlement_outcomes a JOIN vendor_settlements s ON s.school_id=a.school_id AND s.id=a.settlement_id WHERE a.school_id=NEW.school_id AND a.id=NEW.record_id AND a.reference_hash=NEW.identity_hash AND s.method=NEW.method AND s.currency=NEW.currency AND s.payout_cents=NEW.amount_cents AND a.ledger_entry_id=NEW.ledger_entry_id))" : '';
        DB::unprepared("CREATE TRIGGER financial_disbursement_insert BEFORE INSERT ON financial_disbursements FOR EACH ROW BEGIN IF (NEW.domain='fees' AND NOT EXISTS(SELECT 1 FROM fee_refund_actions a JOIN fee_refunds r ON r.school_id=a.school_id AND r.id=a.fee_refund_id WHERE a.school_id=NEW.school_id AND a.id=NEW.record_id AND a.action='paid' AND a.reference_hash=NEW.identity_hash AND r.method=NEW.method AND a.currency=NEW.currency AND a.amount_cents=NEW.amount_cents AND a.ledger_entry_id=NEW.ledger_entry_id)) OR (NEW.domain='wallet' AND NOT EXISTS(SELECT 1 FROM wallet_withdrawal_actions a JOIN wallet_withdrawals r ON r.school_id=a.school_id AND r.id=a.withdrawal_id WHERE a.school_id=NEW.school_id AND a.id=NEW.record_id AND a.status='paid' AND a.reference_hash=NEW.identity_hash AND r.method=NEW.method AND r.currency=NEW.currency AND r.amount_cents=NEW.amount_cents AND a.ledger_entry_id=NEW.ledger_entry_id)) $vendor THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Outgoing identity retains its immutable verified original payout'; END IF; END");
    }

    public function down(): void
    {
        foreach (['vendor_settlements', 'vendor_settlement_lines', 'vendor_settlement_approvals', 'vendor_settlement_outcomes'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain original vendor settlement history.');
            }
        }
        DB::unprepared('DROP TRIGGER vendor_settlement_disbursement');
        $this->registry(false);
        $this->ledgerTypes(false);
        foreach (['vendor_settlement_outcomes', 'vendor_settlement_approvals', 'vendor_settlement_lines', 'vendor_settlements'] as $table) {
            Schema::drop($table);
        }
    }
};
