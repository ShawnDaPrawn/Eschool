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
        Schema::table('payment_allocations', fn (Blueprint $t) => $t->unique(['school_id', 'id', 'payment_id'], 'credit_release_allocation_identity'));
        Schema::create('fee_credit_notes', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_account_id');
            $t->unsignedBigInteger('invoice_line_id');
            $t->foreignId('holder_user_id')->constrained('users')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->bigInteger('unpaid_cents');
            $t->bigInteger('released_cents');
            $t->string('reason_code', 24)->collation('utf8mb4_bin');
            $t->text('reason')->collation('utf8mb4_bin');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->json('snapshot');
            $t->unsignedBigInteger('income_account_id');
            $t->unsignedBigInteger('receivable_account_id');
            $t->unsignedBigInteger('credit_account_id')->nullable();
            $t->unsignedBigInteger('ledger_entry_id');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->date('issued_on');
            $t->dateTime('created_at', 6);
            $t->dateTime('sealed_at', 6)->nullable();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'ledger_entry_id']);
            $t->index(['school_id', 'invoice_line_id', 'holder_user_id'], 'credit_line_holder');
            foreach (['fee_account_id' => 'fee_accounts', 'invoice_line_id' => 'invoice_lines', 'income_account_id' => 'ledger_accounts', 'receivable_account_id' => 'ledger_accounts', 'credit_account_id' => 'ledger_accounts', 'ledger_entry_id' => 'ledger_entries'] as $column => $parent) {
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        Schema::create('fee_credit_releases', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('credit_note_id');
            $t->unsignedBigInteger('allocation_id');
            $t->unsignedBigInteger('payment_id');
            $t->bigInteger('amount_cents');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'credit_note_id', 'allocation_id'], 'credit_release_once');
            $t->foreign(['school_id', 'credit_note_id'], 'credit_release_note_fk')->references(['school_id', 'id'])->on('fee_credit_notes')->restrictOnDelete();
            $t->foreign(['school_id', 'allocation_id', 'payment_id'], 'credit_release_allocation_fk')->references(['school_id', 'id', 'payment_id'])->on('payment_allocations')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE fee_credit_notes ADD CONSTRAINT credit_note_rules CHECK (amount_cents BETWEEN 1 AND 100000000000 AND unpaid_cents >= 0 AND released_cents >= 0 AND unpaid_cents + released_cents = amount_cents AND reason_code IN ('billing_error','fee_reduction','leaver_credit','other') AND currency REGEXP '^[A-Z]{3}$' AND ((released_cents = 0 AND credit_account_id IS NULL) OR (released_cents > 0 AND credit_account_id IS NOT NULL)))");
        DB::statement('ALTER TABLE fee_credit_releases ADD CONSTRAINT credit_release_positive CHECK (amount_cents BETWEEN 1 AND 100000000000)');
        DB::unprepared("CREATE TRIGGER credit_note_insert BEFORE INSERT ON fee_credit_notes FOR EACH ROW BEGIN
            IF NEW.sealed_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Finalize credit after its releases'; END IF; END");
        DB::unprepared("CREATE TRIGGER credit_release_insert BEFORE INSERT ON fee_credit_releases FOR EACH ROW BEGIN
            DECLARE note_seal DATETIME(6); DECLARE note_line BIGINT; DECLARE note_holder BIGINT; DECLARE note_account BIGINT; DECLARE note_credit BIGINT;
            DECLARE allocation_line BIGINT; DECLARE allocation_payment BIGINT; DECLARE original BIGINT; DECLARE payment_holder BIGINT; DECLARE payment_account BIGINT; DECLARE payment_credit BIGINT; DECLARE run_seal DATETIME(6); DECLARE released DECIMAL(65,0);
            SELECT sealed_at, invoice_line_id, holder_user_id, fee_account_id, credit_account_id INTO note_seal, note_line, note_holder, note_account, note_credit FROM fee_credit_notes WHERE school_id = NEW.school_id AND id = NEW.credit_note_id;
            SELECT a.invoice_line_id, a.payment_id, a.amount_cents, p.holder_user_id, p.fee_account_id, p.credit_account_id, r.sealed_at INTO allocation_line, allocation_payment, original, payment_holder, payment_account, payment_credit, run_seal
                FROM payment_allocations a JOIN payments p ON p.school_id = a.school_id AND p.id = a.payment_id JOIN payment_allocation_runs r ON r.school_id = a.school_id AND r.id = a.allocation_run_id
                WHERE a.school_id = NEW.school_id AND a.id = NEW.allocation_id;
            SELECT COALESCE(SUM(amount_cents),0) INTO released FROM fee_credit_releases WHERE school_id = NEW.school_id AND allocation_id = NEW.allocation_id;
            IF note_seal IS NOT NULL OR note_line IS NULL OR run_seal IS NULL OR allocation_line <> note_line OR allocation_payment <> NEW.payment_id OR payment_holder <> note_holder OR payment_account <> note_account OR NOT (payment_credit <=> note_credit) OR released + NEW.amount_cents > original THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Release must restore its original holder and allocation'; END IF; END");
        $identity = implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", ['id', 'school_id', 'fee_account_id', 'invoice_line_id', 'holder_user_id', 'currency', 'amount_cents', 'unpaid_cents', 'released_cents', 'reason_code', 'reason', 'fingerprint', 'snapshot', 'income_account_id', 'receivable_account_id', 'credit_account_id', 'ledger_entry_id', 'created_by', 'issued_on', 'created_at']));
        DB::unprepared("CREATE TRIGGER credit_note_finalize BEFORE UPDATE ON fee_credit_notes FOR EACH ROW BEGIN
            DECLARE shares JSON; DECLARE share_cents BIGINT DEFAULT 0; DECLARE idx INT DEFAULT 0; DECLARE line_account BIGINT; DECLARE line_currency CHAR(3); DECLARE invoice_seal DATETIME(6); DECLARE run_state VARCHAR(12); DECLARE receivable BIGINT;
            DECLARE prior_credits DECIMAL(65,0); DECLARE paid DECIMAL(65,0); DECLARE prior_released DECIMAL(65,0); DECLARE released DECIMAL(65,0); DECLARE unpaid DECIMAL(65,0); DECLARE debits DECIMAL(65,0); DECLARE receivable_credit DECIMAL(65,0); DECLARE held_credit DECIMAL(65,0); DECLARE posting_count INT;
            IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR $identity THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credit history is immutable'; END IF;
            SELECT l.holder_shares, i.fee_account_id, l.currency, i.sealed_at, b.state, f.ledger_account_id INTO shares, line_account, line_currency, invoice_seal, run_state, receivable
                FROM invoice_lines l JOIN invoices i ON i.school_id = l.school_id AND i.id = l.invoice_id JOIN bill_runs b ON b.school_id = i.school_id AND b.id = i.bill_run_id JOIN fee_accounts f ON f.school_id = i.school_id AND f.id = i.fee_account_id WHERE l.school_id = NEW.school_id AND l.id = NEW.invoice_line_id;
            WHILE idx < COALESCE(JSON_LENGTH(shares),0) DO
                IF CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].user_id'))) AS UNSIGNED) = NEW.holder_user_id THEN SET share_cents = CAST(JSON_UNQUOTE(JSON_EXTRACT(shares, CONCAT('$[',idx,'].amount_cents'))) AS SIGNED); END IF; SET idx = idx + 1;
            END WHILE;
            SELECT COALESCE(SUM(amount_cents),0) INTO prior_credits FROM fee_credit_notes WHERE school_id = NEW.school_id AND invoice_line_id = NEW.invoice_line_id AND holder_user_id = NEW.holder_user_id AND sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(a.amount_cents),0) INTO paid FROM payment_allocations a JOIN payments p ON p.school_id = a.school_id AND p.id = a.payment_id JOIN payment_allocation_runs r ON r.school_id = a.school_id AND r.id = a.allocation_run_id WHERE a.school_id = NEW.school_id AND a.invoice_line_id = NEW.invoice_line_id AND p.holder_user_id = NEW.holder_user_id AND r.sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(c.amount_cents),0) INTO prior_released FROM fee_credit_releases c JOIN fee_credit_notes n ON n.school_id = c.school_id AND n.id = c.credit_note_id WHERE c.school_id = NEW.school_id AND n.invoice_line_id = NEW.invoice_line_id AND n.holder_user_id = NEW.holder_user_id AND n.sealed_at IS NOT NULL;
            SELECT COALESCE(SUM(amount_cents),0) INTO released FROM fee_credit_releases WHERE school_id = NEW.school_id AND credit_note_id = NEW.id;
            SET unpaid = share_cents - prior_credits - paid + prior_released;
            SELECT COALESCE(SUM(CASE WHEN p.direction = 'debit' AND p.account_id = NEW.income_account_id AND a.type = 'fee_income' AND a.module = 'fees' THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = NEW.receivable_account_id THEN p.cents ELSE 0 END),0),
                COALESCE(SUM(CASE WHEN p.direction = 'credit' AND p.account_id = NEW.credit_account_id AND a.type = 'family' AND a.module = 'fees' THEN p.cents ELSE 0 END),0), COUNT(*) INTO debits, receivable_credit, held_credit, posting_count
                FROM ledger_postings p JOIN ledger_entries e ON e.school_id = p.school_id AND e.id = p.entry_id JOIN ledger_accounts a ON a.school_id = p.school_id AND a.id = p.account_id
                WHERE p.school_id = NEW.school_id AND p.entry_id = NEW.ledger_entry_id AND e.posted_at IS NOT NULL AND e.currency = NEW.currency;
            IF line_account IS NULL OR line_account <> NEW.fee_account_id OR line_currency <> NEW.currency OR invoice_seal IS NULL OR run_state <> 'posted' OR receivable <> NEW.receivable_account_id OR unpaid < 0 OR NEW.amount_cents + prior_credits > share_cents OR NEW.unpaid_cents <> LEAST(NEW.amount_cents, unpaid) OR released <> NEW.released_cents OR debits <> NEW.amount_cents OR receivable_credit <> NEW.unpaid_cents OR held_credit <> NEW.released_cents OR posting_count <> IF(NEW.unpaid_cents > 0 AND NEW.released_cents > 0,3,2) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credit, original share, releases and posted entry must agree'; END IF; END");
        foreach (['fee_credit_notes' => ['delete'], 'fee_credit_releases' => ['update', 'delete']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared("CREATE TRIGGER {$table}_no_{$action} BEFORE {$action} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Credit history is immutable'");
            }
        }
        $this->allocationGuard(true);
    }

    public function down(): void
    {
        if (DB::table('fee_credit_notes')->exists() || DB::table('fee_credit_releases')->exists()) {
            throw new RuntimeException('Retain credit notes and released payment history.');
        }
        $this->allocationGuard(false);
        foreach (['credit_note_insert', 'credit_release_insert', 'credit_note_finalize', 'fee_credit_notes_no_delete', 'fee_credit_releases_no_update', 'fee_credit_releases_no_delete'] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
        Schema::dropIfExists('fee_credit_releases');
        Schema::dropIfExists('fee_credit_notes');
        Schema::table('payment_allocations', fn (Blueprint $t) => $t->dropUnique('credit_release_allocation_identity'));
    }

    private function allocationGuard(bool $credits): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS allocation_row_insert');
        $adjustments = $credits ? 'SELECT COALESCE(SUM(c.amount_cents),0) INTO adjustments FROM fee_credit_releases c JOIN fee_credit_notes n ON n.school_id = c.school_id AND n.id = c.credit_note_id WHERE c.school_id = NEW.school_id AND c.payment_id = NEW.payment_id AND n.sealed_at IS NOT NULL;
            SET used_payment = used_payment - adjustments;
            SELECT COALESCE(SUM(c.amount_cents),0) INTO adjustments FROM fee_credit_releases c JOIN fee_credit_notes n ON n.school_id = c.school_id AND n.id = c.credit_note_id WHERE c.school_id = NEW.school_id AND n.invoice_line_id = NEW.invoice_line_id AND n.holder_user_id = holder_id AND n.sealed_at IS NOT NULL;
            SET used_share = used_share - adjustments;
            SELECT COALESCE(SUM(amount_cents),0) INTO adjustments FROM fee_credit_notes WHERE school_id = NEW.school_id AND invoice_line_id = NEW.invoice_line_id AND holder_user_id = holder_id AND sealed_at IS NOT NULL;
            SET share_cents = share_cents - adjustments;' : '';
        DB::unprepared("CREATE TRIGGER allocation_row_insert BEFORE INSERT ON payment_allocations FOR EACH ROW BEGIN
            DECLARE run_seal DATETIME(6); DECLARE run_payment BIGINT; DECLARE account_id BIGINT; DECLARE holder_id BIGINT; DECLARE pin BIGINT;
            DECLARE line_account BIGINT; DECLARE line_learner BIGINT; DECLARE invoice_seal DATETIME(6); DECLARE invoice_state VARCHAR(12);
            DECLARE shares JSON; DECLARE share_cents BIGINT DEFAULT 0; DECLARE idx INT DEFAULT 0;
            DECLARE adjustments DECIMAL(65,0); DECLARE used_payment DECIMAL(65,0); DECLARE used_share DECIMAL(65,0); DECLARE paid BIGINT;
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
            $adjustments
            IF run_seal IS NOT NULL OR run_payment IS NULL OR run_payment <> NEW.payment_id OR line_account IS NULL OR line_account <> account_id OR invoice_seal IS NULL OR invoice_state <> 'posted'
                OR (pin IS NOT NULL AND pin <> line_learner) OR used_payment + NEW.amount_cents > paid OR used_share + NEW.amount_cents > share_cents THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Allocation exceeds its payment, payer share, learner or sealed document'; END IF; END");
    }
};
