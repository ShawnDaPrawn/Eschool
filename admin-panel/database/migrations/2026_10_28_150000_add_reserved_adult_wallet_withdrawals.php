<?php

declare(strict_types=1);

use App\Models\Money\FeeRefundAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Preserve and verify new-ledger refund history, never import abandoned seed money.
        $paidRows = DB::table('fee_refund_actions as a')->join('fee_refunds as r', fn ($j) => $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.fee_refund_id'))->where('a.action', 'paid')->get(['a.*', 'r.method']);
        foreach ($paidRows as $row) {
            $model = new FeeRefundAction;
            $model->setRawAttributes((array) $row);
            $raw = $model->private_evidence;
            if (! is_string($raw)) {
                throw new RuntimeException('Retained refund payout proof is missing.');
            }
            $evidence = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            if (! is_string($evidence['reference'] ?? null) || ! hash_equals($row->reference_hash, hash('sha256', json_encode([$row->method, $row->currency, $evidence['reference']], JSON_THROW_ON_ERROR)))) {
                throw new RuntimeException('Original refund payout identity does not reconcile.');
            }
        }
        Schema::create('wallet_withdrawal_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('cash_threshold_cents');
            $t->boolean('cash_at_boundary');
            $t->boolean('accepting_requests');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        DB::statement("ALTER TABLE wallet_withdrawal_policies ADD CONSTRAINT wallet_withdrawal_policy_shape CHECK (revision>0 AND currency REGEXP '^[A-Z]{3}$' AND cash_threshold_cents BETWEEN 1 AND 100000000000 AND (currency<>'ZAR' OR cash_threshold_cents=25000))");
        DB::unprepared("CREATE TRIGGER wallet_withdrawal_policy_guard BEFORE UPDATE ON wallet_withdrawal_policies FOR EACH ROW BEGIN IF NEW.school_id<>OLD.school_id OR NEW.currency<>OLD.currency OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Withdrawal policy retains school, currency and ordered revisions'; END IF; END");
        DB::unprepared("CREATE TRIGGER wallet_withdrawal_policy_no_delete BEFORE DELETE ON wallet_withdrawal_policies FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Withdrawal policy history is retained'");
        Schema::create('wallet_withdrawal_slots', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->dateTime('starts_at', 6);
            $t->dateTime('ends_at', 6);
            $t->unsignedInteger('capacity');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
        });
        DB::statement('ALTER TABLE wallet_withdrawal_slots ADD CONSTRAINT wallet_withdrawal_slot_shape CHECK (capacity BETWEEN 1 AND 1000 AND ends_at>starts_at AND starts_at>created_at)');
        Schema::create('wallet_withdrawal_slot_closures', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('slot_id');
            $t->foreign(['school_id', 'slot_id'])->references(['school_id', 'id'])->on('wallet_withdrawal_slots')->restrictOnDelete();
            $t->unique(['school_id', 'slot_id']);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->text('private_reason');
            $t->dateTime('created_at', 6);
        });
        Schema::create('wallet_withdrawals', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('holder_user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('wallet_account_id');
            $t->unsignedBigInteger('pending_account_id');
            $t->unsignedBigInteger('reserved_entry_id');
            $t->foreign(['school_id', 'wallet_account_id'])->references(['school_id', 'id'])->on('wallet_accounts')->restrictOnDelete();
            foreach (['pending_account_id' => 'ledger_accounts', 'reserved_entry_id' => 'ledger_entries'] as $field => $table) {
                $t->foreign(['school_id', $field])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->string('method', 12);
            $t->unsignedInteger('policy_revision');
            $t->unsignedBigInteger('slot_id')->nullable();
            $t->foreign(['school_id', 'slot_id'])->references(['school_id', 'id'])->on('wallet_withdrawal_slots')->restrictOnDelete();
            $t->text('private_bank_instructions')->nullable();
            $t->text('private_reason')->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'reserved_entry_id']);
            $t->unique(['school_id', 'pending_account_id']);
        });
        DB::statement("ALTER TABLE wallet_withdrawals ADD CONSTRAINT wallet_withdrawal_shape CHECK (amount_cents BETWEEN 1 AND 100000000000 AND policy_revision>0 AND ((method='cash' AND slot_id IS NOT NULL AND private_bank_instructions IS NULL) OR (method='eft' AND slot_id IS NULL AND private_bank_instructions IS NOT NULL AND CHAR_LENGTH(private_bank_instructions)>0)))");
        Schema::create('wallet_withdrawal_actions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('withdrawal_id');
            $t->foreign(['school_id', 'withdrawal_id'])->references(['school_id', 'id'])->on('wallet_withdrawals')->restrictOnDelete();
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->string('status', 12);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('ledger_entry_id')->nullable();
            $t->unsignedBigInteger('counter_account_id')->nullable();
            foreach (['ledger_entry_id' => 'ledger_entries', 'counter_account_id' => 'ledger_accounts'] as $field => $table) {
                $t->foreign(['school_id', $field])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->foreignId('cash_operator_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->date('paid_on')->nullable();
            $t->char('reference_hash', 64)->nullable()->collation('utf8mb4_bin');
            $t->text('private_evidence')->nullable();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            $t->unique(['school_id', 'ledger_entry_id']);
        });
        DB::statement("ALTER TABLE wallet_withdrawal_actions ADD CONSTRAINT wallet_withdrawal_action_shape CHECK ((status='approved' AND paid_on IS NULL AND ledger_entry_id IS NULL AND counter_account_id IS NULL AND reference_hash IS NULL AND cash_operator_id IS NULL) OR (status IN ('cancelled','rejected') AND paid_on IS NULL AND ledger_entry_id IS NOT NULL AND counter_account_id IS NOT NULL AND reference_hash IS NULL AND cash_operator_id IS NULL) OR (status='paid' AND paid_on IS NOT NULL AND ledger_entry_id IS NOT NULL AND counter_account_id IS NOT NULL AND reference_hash IS NOT NULL AND CHAR_LENGTH(reference_hash)=64 AND private_evidence IS NOT NULL AND CHAR_LENGTH(private_evidence)>0))");
        Schema::create('financial_disbursements', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('identity_hash', 64)->collation('utf8mb4_bin');
            $t->string('domain', 12);
            $t->unsignedBigInteger('record_id');
            $t->string('method', 12);
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->unsignedBigInteger('ledger_entry_id');
            $t->dateTime('created_at', 6);
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
            $t->unique(['school_id', 'identity_hash']);
            $t->unique(['school_id', 'domain', 'record_id']);
            $t->unique(['school_id', 'ledger_entry_id']);
        });
        DB::statement("ALTER TABLE financial_disbursements ADD CONSTRAINT financial_disbursement_shape CHECK (domain IN ('fees','wallet') AND method IN ('cash','eft') AND currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND CHAR_LENGTH(identity_hash)=64)");
        DB::unprepared("CREATE TRIGGER wallet_withdrawal_insert BEFORE INSERT ON wallet_withdrawals FOR EACH ROW BEGIN
            IF NOT EXISTS(SELECT 1 FROM wallet_accounts w JOIN ledger_accounts a ON a.school_id=w.school_id AND a.id=w.ledger_account_id JOIN ledger_accounts h ON h.school_id=w.school_id AND h.id=NEW.pending_account_id JOIN ledger_entries e ON e.school_id=w.school_id AND e.id=NEW.reserved_entry_id JOIN wallet_withdrawal_policies r ON r.school_id=w.school_id WHERE w.school_id=NEW.school_id AND w.id=NEW.wallet_account_id AND w.user_id=NEW.holder_user_id AND w.currency=NEW.currency AND a.owner_user_id=NEW.holder_user_id AND h.owner_user_id IS NULL AND h.type='wallet' AND h.module='wallet' AND h.currency=NEW.currency AND h.reference=CONCAT('wallet:withdrawal:',NEW.operation_id) AND e.posted_at IS NOT NULL AND e.currency=NEW.currency AND e.source=CONCAT('wallet-withdrawal:reserved:',NEW.operation_id) AND r.currency=NEW.currency AND r.revision=NEW.policy_revision AND r.accepting_requests=1 AND NEW.method=IF(NEW.amount_cents<r.cash_threshold_cents OR (NEW.amount_cents=r.cash_threshold_cents AND r.cash_at_boundary=1),'cash','eft'))
            OR (SELECT COUNT(*) FROM ledger_postings WHERE school_id=NEW.school_id AND entry_id=NEW.reserved_entry_id)<>2
            OR NOT EXISTS(SELECT 1 FROM ledger_postings p JOIN wallet_accounts w ON w.school_id=p.school_id AND w.ledger_account_id=p.account_id WHERE p.school_id=NEW.school_id AND p.entry_id=NEW.reserved_entry_id AND w.id=NEW.wallet_account_id AND p.direction='debit' AND p.cents=NEW.amount_cents)
            OR NOT EXISTS(SELECT 1 FROM ledger_postings WHERE school_id=NEW.school_id AND entry_id=NEW.reserved_entry_id AND account_id=NEW.pending_account_id AND direction='credit' AND cents=NEW.amount_cents)
            THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Withdrawal retains its original holder, explicit policy and exact reservation'; END IF;
            IF NEW.method='cash' AND (NOT EXISTS(SELECT 1 FROM wallet_withdrawal_slots s WHERE s.school_id=NEW.school_id AND s.id=NEW.slot_id AND s.starts_at>NEW.created_at) OR EXISTS(SELECT 1 FROM wallet_withdrawal_slot_closures WHERE school_id=NEW.school_id AND slot_id=NEW.slot_id) OR (SELECT COUNT(*) FROM wallet_withdrawals w WHERE w.school_id=NEW.school_id AND w.slot_id=NEW.slot_id AND NOT EXISTS(SELECT 1 FROM wallet_withdrawal_actions x WHERE x.school_id=w.school_id AND x.withdrawal_id=w.id AND x.status IN ('cancelled','rejected')))>=(SELECT capacity FROM wallet_withdrawal_slots WHERE school_id=NEW.school_id AND id=NEW.slot_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Cash pickup requires an available published slot'; END IF;
        END");
        DB::unprepared("CREATE TRIGGER wallet_withdrawal_action_insert BEFORE INSERT ON wallet_withdrawal_actions FOR EACH ROW BEGIN
            IF EXISTS(SELECT 1 FROM wallet_withdrawal_actions WHERE school_id=NEW.school_id AND withdrawal_id=NEW.withdrawal_id AND status IN ('paid','cancelled','rejected')) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Withdrawal already has a final outcome'; END IF;
            IF NEW.status='approved' AND (EXISTS(SELECT 1 FROM wallet_withdrawal_actions WHERE school_id=NEW.school_id AND withdrawal_id=NEW.withdrawal_id AND status='approved') OR EXISTS(SELECT 1 FROM wallet_withdrawals WHERE school_id=NEW.school_id AND id=NEW.withdrawal_id AND holder_user_id=NEW.actor_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Withdrawal requires independent Finance approval'; END IF;
            IF NEW.status<>'approved' THEN
                IF NEW.status='paid' AND NOT EXISTS(SELECT 1 FROM wallet_withdrawal_actions WHERE school_id=NEW.school_id AND withdrawal_id=NEW.withdrawal_id AND status='approved') THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Only approved withdrawals can be paid'; END IF;
                IF NOT EXISTS(SELECT 1 FROM wallet_withdrawals w JOIN wallet_accounts a ON a.school_id=w.school_id AND a.id=w.wallet_account_id JOIN ledger_entries e ON e.school_id=w.school_id AND e.id=NEW.ledger_entry_id WHERE w.school_id=NEW.school_id AND w.id=NEW.withdrawal_id AND e.posted_at IS NOT NULL AND e.currency=w.currency AND e.source=CONCAT('wallet-withdrawal:',NEW.status,':',NEW.operation_id) AND (SELECT COUNT(*) FROM ledger_postings WHERE school_id=w.school_id AND entry_id=e.id)=2 AND EXISTS(SELECT 1 FROM ledger_postings WHERE school_id=w.school_id AND entry_id=e.id AND account_id=w.pending_account_id AND direction='debit' AND cents=w.amount_cents) AND EXISTS(SELECT 1 FROM ledger_postings p JOIN ledger_accounts c ON c.school_id=p.school_id AND c.id=p.account_id WHERE p.school_id=w.school_id AND p.entry_id=e.id AND p.account_id=NEW.counter_account_id AND p.direction='credit' AND p.cents=w.amount_cents AND ((NEW.status IN ('cancelled','rejected') AND p.account_id=a.ledger_account_id) OR (NEW.status='paid' AND NEW.actor_id<>w.holder_user_id AND c.module='wallet' AND c.owner_user_id IS NULL AND c.currency=w.currency AND c.type=IF(w.method='cash','cash_desk','wallet_bank') AND c.reference=CONCAT('wallet:',w.method,':',w.currency) AND ((w.method='cash' AND NEW.cash_operator_id IS NOT NULL AND NEW.cash_operator_id=NEW.actor_id) OR (w.method='eft' AND NEW.cash_operator_id IS NULL)))))) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Withdrawal outcome requires exact original reservation or actual outside payout'; END IF;
            END IF;
        END");
        DB::unprepared("CREATE TRIGGER financial_disbursement_insert BEFORE INSERT ON financial_disbursements FOR EACH ROW BEGIN IF
            (NEW.domain='fees' AND NOT EXISTS(SELECT 1 FROM fee_refund_actions a JOIN fee_refunds r ON r.school_id=a.school_id AND r.id=a.fee_refund_id WHERE a.school_id=NEW.school_id AND a.id=NEW.record_id AND a.action='paid' AND a.reference_hash=NEW.identity_hash AND r.method=NEW.method AND a.currency=NEW.currency AND a.amount_cents=NEW.amount_cents AND a.ledger_entry_id=NEW.ledger_entry_id)) OR
            (NEW.domain='wallet' AND NOT EXISTS(SELECT 1 FROM wallet_withdrawal_actions a JOIN wallet_withdrawals r ON r.school_id=a.school_id AND r.id=a.withdrawal_id WHERE a.school_id=NEW.school_id AND a.id=NEW.record_id AND a.status='paid' AND a.reference_hash=NEW.identity_hash AND r.method=NEW.method AND r.currency=NEW.currency AND r.amount_cents=NEW.amount_cents AND a.ledger_entry_id=NEW.ledger_entry_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Outgoing identity retains its immutable verified original payout'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_refund_disbursement AFTER INSERT ON fee_refund_actions FOR EACH ROW BEGIN IF NEW.action='paid' THEN INSERT INTO financial_disbursements(school_id,identity_hash,domain,record_id,method,currency,amount_cents,ledger_entry_id,created_at) SELECT NEW.school_id,NEW.reference_hash,'fees',NEW.id,r.method,NEW.currency,NEW.amount_cents,NEW.ledger_entry_id,NEW.created_at FROM fee_refunds r WHERE r.school_id=NEW.school_id AND r.id=NEW.fee_refund_id; END IF; END");
        DB::unprepared("CREATE TRIGGER wallet_withdrawal_disbursement AFTER INSERT ON wallet_withdrawal_actions FOR EACH ROW BEGIN IF NEW.status='paid' THEN INSERT INTO financial_disbursements(school_id,identity_hash,domain,record_id,method,currency,amount_cents,ledger_entry_id,created_at) SELECT NEW.school_id,NEW.reference_hash,'wallet',NEW.id,r.method,r.currency,r.amount_cents,NEW.ledger_entry_id,NEW.created_at FROM wallet_withdrawals r WHERE r.school_id=NEW.school_id AND r.id=NEW.withdrawal_id; END IF; END");
        foreach ($paidRows as $row) {
            DB::table('financial_disbursements')->insert(['school_id' => $row->school_id, 'identity_hash' => $row->reference_hash, 'domain' => 'fees', 'record_id' => $row->id, 'method' => $row->method, 'currency' => $row->currency, 'amount_cents' => $row->amount_cents, 'ledger_entry_id' => $row->ledger_entry_id, 'created_at' => $row->created_at]);
        }
        foreach (['wallet_withdrawal_slots', 'wallet_withdrawal_slot_closures', 'wallet_withdrawals', 'wallet_withdrawal_actions', 'financial_disbursements'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $event) {
                DB::unprepared("CREATE TRIGGER {$table}_no_".strtolower($event)." BEFORE $event ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Withdrawal and outside payout history is immutable'");
            }
        }
    }

    public function down(): void
    {
        foreach (['wallet_withdrawal_policies', 'wallet_withdrawal_slots', 'wallet_withdrawal_slot_closures', 'wallet_withdrawals', 'wallet_withdrawal_actions', 'financial_disbursements'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain withdrawals, reservations, pickup slots and outside payout identity.');
            }
        }
        DB::unprepared('DROP TRIGGER IF EXISTS fee_refund_disbursement');
        foreach (['financial_disbursements', 'wallet_withdrawal_actions', 'wallet_withdrawals', 'wallet_withdrawal_slot_closures', 'wallet_withdrawal_slots', 'wallet_withdrawal_policies'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
