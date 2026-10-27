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
        Schema::table('invoice_lines', fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'payment_line_school_identity'));
        $this->accountRules(true);
        Schema::create('fee_payment_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->string('allocation_order', 24)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('payments', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_account_id');
            $t->foreignId('holder_user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedBigInteger('pinned_learner_id')->nullable();
            $t->string('method', 12)->collation('utf8mb4_bin');
            $t->string('provider', 12)->collation('utf8mb4_bin');
            $t->string('source_reference', 96)->collation('utf8mb4_bin');
            $t->string('status', 12)->collation('utf8mb4_bin');
            $t->string('collected_by', 16)->collation('utf8mb4_bin');
            $t->string('settlement_reference', 96)->nullable();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->bigInteger('fee_cents');
            $t->bigInteger('net_cents');
            $t->date('received_on');
            $t->unsignedBigInteger('debit_account_id');
            $t->unsignedBigInteger('credit_account_id');
            $t->unsignedBigInteger('ledger_entry_id');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'method', 'source_reference'], 'payment_business_source');
            $t->unique(['school_id', 'ledger_entry_id']);
            foreach (['fee_account_id' => 'fee_accounts', 'pinned_learner_id' => 'students', 'debit_account_id' => 'ledger_accounts', 'credit_account_id' => 'ledger_accounts', 'ledger_entry_id' => 'ledger_entries'] as $c => $parent) {
                $t->foreign(['school_id', $c])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        Schema::create('payment_allocation_runs', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('payment_id');
            $t->unsignedBigInteger('ledger_entry_id');
            $t->string('allocation_order', 24)->collation('utf8mb4_bin');
            $t->unsignedInteger('policy_revision');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->json('snapshot');
            $t->bigInteger('amount_cents');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->dateTime('sealed_at', 6)->nullable();
            $t->unique(['school_id', 'id', 'payment_id'], 'allocation_run_payment_identity');
            $t->unique(['school_id', 'ledger_entry_id']);
            $t->foreign(['school_id', 'payment_id'])->references(['school_id', 'id'])->on('payments')->restrictOnDelete();
            $t->foreign(['school_id', 'ledger_entry_id'])->references(['school_id', 'id'])->on('ledger_entries')->restrictOnDelete();
        });
        Schema::create('payment_allocations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('allocation_run_id');
            $t->unsignedBigInteger('payment_id');
            $t->unsignedBigInteger('invoice_line_id');
            $t->bigInteger('amount_cents');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'allocation_run_id', 'invoice_line_id'], 'allocation_run_line_once');
            $t->index(['school_id', 'payment_id', 'invoice_line_id'], 'allocation_payment_line');
            $t->foreign(['school_id', 'allocation_run_id', 'payment_id'], 'allocation_row_run_payment_fk')->references(['school_id', 'id', 'payment_id'])->on('payment_allocation_runs')->restrictOnDelete();
            $t->foreign(['school_id', 'invoice_line_id'])->references(['school_id', 'id'])->on('invoice_lines')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE fee_payment_policies ADD CONSTRAINT payment_policy_rules CHECK (allocation_order IN ('oldest_first','tuition_first') AND revision > 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payment_rules CHECK (method IN ('cash','eft') AND provider = 'manual' AND status = 'settled' AND collected_by = 'school' AND amount_cents BETWEEN 1 AND 100000000000 AND fee_cents = 0 AND net_cents = amount_cents AND currency REGEXP '^[A-Z]{3}$')");
        DB::statement("ALTER TABLE payment_allocation_runs ADD CONSTRAINT allocation_run_rules CHECK (amount_cents BETWEEN 1 AND 100000000000 AND allocation_order IN ('oldest_first','tuition_first') AND policy_revision > 0)");
        DB::statement('ALTER TABLE payment_allocations ADD CONSTRAINT allocation_positive CHECK (amount_cents BETWEEN 1 AND 100000000000)');
        DB::unprepared("CREATE TRIGGER payment_receipt_insert BEFORE INSERT ON payments FOR EACH ROW BEGIN
            DECLARE debits DECIMAL(65,0); DECLARE credits DECIMAL(65,0); DECLARE target_currency CHAR(3);
            SELECT currency INTO target_currency FROM fee_accounts WHERE school_id = NEW.school_id AND id = NEW.fee_account_id;
            SELECT COALESCE(SUM(CASE WHEN p.direction = 'debit' AND p.account_id = NEW.debit_account_id AND a.type = IF(NEW.method = 'eft','fee_bank','cash_desk') THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = NEW.credit_account_id AND a.type = 'family' THEN p.cents ELSE 0 END),0)
                INTO debits, credits FROM ledger_postings p JOIN ledger_accounts a ON a.school_id = p.school_id AND a.id = p.account_id
                JOIN ledger_entries e ON e.school_id = p.school_id AND e.id = p.entry_id
                WHERE p.school_id = NEW.school_id AND p.entry_id = NEW.ledger_entry_id AND a.module = 'fees' AND e.posted_at IS NOT NULL AND e.currency = NEW.currency;
            IF target_currency IS NULL OR target_currency <> NEW.currency OR debits <> NEW.amount_cents OR credits <> NEW.amount_cents THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Receipt requires matching posted bank/cash and family credit'; END IF; END");
        DB::unprepared("CREATE TRIGGER allocation_run_insert BEFORE INSERT ON payment_allocation_runs FOR EACH ROW BEGIN
            IF NEW.sealed_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Finalize allocation after its rows'; END IF; END");
        DB::unprepared("CREATE TRIGGER allocation_row_insert BEFORE INSERT ON payment_allocations FOR EACH ROW BEGIN
            DECLARE run_seal DATETIME(6); DECLARE run_payment BIGINT; DECLARE account_id BIGINT; DECLARE holder_id BIGINT; DECLARE pin BIGINT;
            DECLARE line_account BIGINT; DECLARE line_learner BIGINT; DECLARE invoice_seal DATETIME(6); DECLARE invoice_state VARCHAR(12);
            DECLARE shares JSON; DECLARE share_cents BIGINT DEFAULT 0; DECLARE idx INT DEFAULT 0;
            DECLARE used_payment DECIMAL(65,0); DECLARE used_share DECIMAL(65,0); DECLARE paid BIGINT;
            SELECT sealed_at, payment_id INTO run_seal, run_payment FROM payment_allocation_runs WHERE school_id = NEW.school_id AND id = NEW.allocation_run_id;
            SELECT fee_account_id, holder_user_id, pinned_learner_id, amount_cents INTO account_id, holder_id, pin, paid FROM payments WHERE school_id = NEW.school_id AND id = NEW.payment_id;
            SELECT i.fee_account_id, l.learner_id, i.sealed_at, b.state, l.holder_shares INTO line_account, line_learner, invoice_seal, invoice_state, shares
                FROM invoice_lines l JOIN invoices i ON i.school_id = l.school_id AND i.id = l.invoice_id
                JOIN bill_runs b ON b.school_id = i.school_id AND b.id = i.bill_run_id WHERE l.school_id = NEW.school_id AND l.id = NEW.invoice_line_id;
            WHILE idx < COALESCE(JSON_LENGTH(shares),0) DO
                IF CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].user_id'))) AS UNSIGNED) = holder_id THEN
                    SET share_cents = CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].amount_cents'))) AS SIGNED);
                END IF; SET idx = idx + 1;
            END WHILE;
            SELECT COALESCE(SUM(amount_cents),0) INTO used_payment FROM payment_allocations WHERE school_id = NEW.school_id AND payment_id = NEW.payment_id;
            SELECT COALESCE(SUM(a.amount_cents),0) INTO used_share FROM payment_allocations a JOIN payments p ON p.school_id = a.school_id AND p.id = a.payment_id
                WHERE a.school_id = NEW.school_id AND a.invoice_line_id = NEW.invoice_line_id AND p.holder_user_id = holder_id;
            IF run_seal IS NOT NULL OR run_payment IS NULL OR run_payment <> NEW.payment_id OR line_account IS NULL OR line_account <> account_id OR invoice_seal IS NULL OR invoice_state <> 'posted'
                OR (pin IS NOT NULL AND pin <> line_learner) OR used_payment + NEW.amount_cents > paid OR used_share + NEW.amount_cents > share_cents THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Allocation exceeds its payment, payer share, learner or sealed document'; END IF; END");
        $identity = implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", ['id', 'school_id', 'payment_id', 'ledger_entry_id', 'allocation_order', 'policy_revision', 'fingerprint', 'snapshot', 'amount_cents', 'created_by', 'created_at']));
        DB::unprepared("CREATE TRIGGER allocation_finalize BEFORE UPDATE ON payment_allocation_runs FOR EACH ROW BEGIN
            DECLARE row_total DECIMAL(65,0); DECLARE credit_account BIGINT; DECLARE receivable BIGINT; DECLARE debits DECIMAL(65,0); DECLARE credits DECIMAL(65,0); DECLARE currency CHAR(3);
            IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR $identity THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Allocation history is immutable'; END IF;
            SELECT COALESCE(SUM(amount_cents),0) INTO row_total FROM payment_allocations WHERE school_id = NEW.school_id AND allocation_run_id = NEW.id;
            SELECT p.credit_account_id, a.ledger_account_id, p.currency INTO credit_account, receivable, currency FROM payments p JOIN fee_accounts a ON a.school_id = p.school_id AND a.id = p.fee_account_id
                WHERE p.school_id = NEW.school_id AND p.id = NEW.payment_id;
            SELECT COALESCE(SUM(CASE WHEN p.direction = 'debit' AND p.account_id = credit_account THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = receivable THEN p.cents ELSE 0 END),0) INTO debits, credits
                FROM ledger_postings p JOIN ledger_entries e ON e.school_id = p.school_id AND e.id = p.entry_id
                WHERE p.school_id = NEW.school_id AND p.entry_id = NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency = currency;
            IF row_total <> NEW.amount_cents OR debits <> NEW.amount_cents OR credits <> NEW.amount_cents THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Allocation rows and posted credit/receivable must agree'; END IF; END");
        foreach (['payments' => ['update', 'delete'], 'payment_allocation_runs' => ['delete'], 'payment_allocations' => ['update', 'delete']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared("CREATE TRIGGER {$table}_no_{$action} BEFORE {$action} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Payment history is immutable'");
            }
        }
    }

    public function down(): void
    {
        foreach (['payments', 'payment_allocation_runs', 'payment_allocations', 'fee_payment_policies'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain payment and allocation history.');
            }
        }
        if (DB::table('ledger_accounts')->where('type', 'fee_bank')->exists()) {
            throw new RuntimeException('Retain fee bank ledger accounts.');
        }
        foreach (['payment_receipt_insert', 'allocation_run_insert', 'allocation_row_insert', 'allocation_finalize', 'payments_no_update', 'payments_no_delete', 'payment_allocation_runs_no_delete', 'payment_allocations_no_update', 'payment_allocations_no_delete'] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
        foreach (['payment_allocations', 'payment_allocation_runs', 'payments', 'fee_payment_policies'] as $table) {
            Schema::dropIfExists($table);
        }
        $this->accountRules(false);
        Schema::table('invoice_lines', fn (Blueprint $t) => $t->dropUnique('payment_line_school_identity'));
    }

    private function accountRules(bool $bank): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE ledger_accounts DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' ledger_account_rules');
        $extra = $bank ? ",'fee_bank'" : '';
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (
            type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk','fee_income'$extra)
            AND currency REGEXP '^[A-Z]{3}$'
            AND normal_direction = CASE WHEN type IN ('fee_receivable','cash_desk','clearing'$extra) THEN 'debit' ELSE 'credit' END
            AND ((type = 'wallet' AND module = 'wallet') OR (type = 'vendor' AND module = 'vendors')
                OR (type IN ('fee_receivable','family','fee_income'$extra) AND module = 'fees')
                OR (type IN ('cash_desk','clearing','due_to_school') AND module IN ('fees','wallet','vendors','ticketing')))
            AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000
            AND (type <> 'wallet' OR balance_cents >= 0) AND (type NOT IN ('clearing','due_to_school') OR balance_cents = 0))");
    }
};
