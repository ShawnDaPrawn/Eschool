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
        foreach (['students', 'session_years', 'fee_plans'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->unique(['school_id', 'id'], 'billing_'.$table.'_school_identity'));
        }
        $this->accountRules(true);
        Schema::create('fee_accounts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('reference', 32)->collation('utf8mb4_bin');
            $t->string('name', 160);
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->boolean('active');
            $t->unsignedInteger('revision');
            $t->unsignedBigInteger('ledger_account_id')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'reference']);
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'ledger_account_id'])->references(['school_id', 'id'])->on('ledger_accounts')->restrictOnDelete();
        });
        Schema::create('fee_account_holders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_account_id');
            $t->unsignedBigInteger('session_year_id');
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('share_bp');
            $t->unique(['school_id', 'fee_account_id', 'session_year_id', 'user_id'], 'fee_holder_identity');
            $t->foreign(['school_id', 'fee_account_id'])->references(['school_id', 'id'])->on('fee_accounts')->restrictOnDelete();
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
        });
        Schema::create('fee_account_learners', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_account_id');
            $t->unsignedBigInteger('learner_id');
            $t->unsignedBigInteger('session_year_id');
            $t->unique(['school_id', 'session_year_id', 'learner_id'], 'fee_learner_payer_year');
            $t->foreign(['school_id', 'fee_account_id'])->references(['school_id', 'id'])->on('fee_accounts')->restrictOnDelete();
            $t->foreign(['school_id', 'learner_id'])->references(['school_id', 'id'])->on('students')->restrictOnDelete();
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
        });
        Schema::create('bill_runs', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('session_year_id');
            $t->date('as_of');
            $t->date('due_on');
            $t->string('tax_treatment', 24)->collation('utf8mb4_bin');
            $t->string('state', 12)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->json('snapshot');
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->dateTime('approved_at', 6)->nullable();
            $t->dateTime('posted_at', 6)->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
        });
        Schema::create('invoices', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_account_id');
            $t->unsignedBigInteger('bill_run_id');
            $t->unsignedBigInteger('session_year_id');
            $t->unsignedBigInteger('ledger_entry_id');
            $t->string('number', 64)->collation('utf8mb4_bin');
            $t->string('account_name', 160);
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('total_cents');
            $t->json('holders');
            $t->date('issued_on');
            $t->date('due_on');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->dateTime('sealed_at', 6)->nullable();
            $t->unique(['school_id', 'id', 'session_year_id'], 'invoice_school_year_identity');
            $t->unique(['school_id', 'bill_run_id', 'fee_account_id'], 'invoice_run_account');
            $t->unique(['school_id', 'number']);
            $t->unique(['school_id', 'ledger_entry_id']);
            foreach (['fee_account_id' => 'fee_accounts', 'bill_run_id' => 'bill_runs', 'session_year_id' => 'session_years', 'ledger_entry_id' => 'ledger_entries'] as $column => $parent) {
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        Schema::create('invoice_lines', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('invoice_id');
            $t->unsignedBigInteger('session_year_id');
            $t->unsignedBigInteger('learner_id');
            $t->unsignedBigInteger('fee_plan_id');
            $t->unsignedInteger('plan_revision');
            $t->string('learner_name', 255);
            $t->string('description', 160);
            $t->date('period_start');
            $t->date('period_end');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->bigInteger('vat_cents');
            $t->json('holder_shares');
            $t->unique(['school_id', 'session_year_id', 'learner_id'], 'annual_tuition_charge_once');
            $t->foreign(['school_id', 'invoice_id', 'session_year_id'])->references(['school_id', 'id', 'session_year_id'])->on('invoices')->restrictOnDelete();
            foreach (['learner_id' => 'students', 'fee_plan_id' => 'fee_plans'] as $column => $parent) {
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        Schema::create('billing_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id')->collation('utf8mb4_bin');
            $t->char('request_hash', 64)->collation('utf8mb4_bin');
            $t->json('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'operation_id']);
        });
        DB::statement("ALTER TABLE fee_accounts ADD CONSTRAINT family_account_rules CHECK (revision > 0 AND currency REGEXP '^[A-Z]{3}$')");
        DB::statement('ALTER TABLE fee_account_holders ADD CONSTRAINT family_holder_share CHECK (share_bp BETWEEN 1 AND 10000)');
        DB::statement("ALTER TABLE bill_runs ADD CONSTRAINT bill_run_rules CHECK (state IN ('preview','approved','posted') AND revision > 0 AND tax_treatment = 'no_vat')");
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoice_total_rules CHECK (total_cents BETWEEN 1 AND 4500000000000000)');
        DB::statement('ALTER TABLE invoice_lines ADD CONSTRAINT invoice_line_rules CHECK (amount_cents > 0 AND vat_cents = 0 AND plan_revision > 0 AND period_start <= period_end)');
        DB::unprepared("CREATE TRIGGER invoice_insert BEFORE INSERT ON invoices FOR EACH ROW BEGIN
            IF NEW.sealed_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invoice must finalize after its lines'; END IF; END");
        $identity = $this->changed(['id', 'school_id', 'fee_account_id', 'bill_run_id', 'session_year_id', 'ledger_entry_id', 'number', 'account_name', 'currency', 'total_cents', 'holders', 'issued_on', 'due_on', 'created_by', 'created_at']);
        DB::unprepared("CREATE TRIGGER invoice_finalize BEFORE UPDATE ON invoices FOR EACH ROW BEGIN
            DECLARE line_count BIGINT; DECLARE line_total DECIMAL(65,0); DECLARE debit_total DECIMAL(65,0); DECLARE receivable BIGINT;
            IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR $identity THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invoice history is immutable'; END IF;
            SELECT COUNT(*), COALESCE(SUM(amount_cents),0) INTO line_count, line_total FROM invoice_lines WHERE school_id = NEW.school_id AND invoice_id = NEW.id;
            SELECT ledger_account_id INTO receivable FROM fee_accounts WHERE school_id = NEW.school_id AND id = NEW.fee_account_id;
            SELECT COALESCE(SUM(p.cents),0) INTO debit_total FROM ledger_postings p JOIN ledger_entries e ON e.school_id = p.school_id AND e.id = p.entry_id
                WHERE p.school_id = NEW.school_id AND p.entry_id = NEW.ledger_entry_id AND p.account_id = receivable AND p.direction = 'debit'
                AND e.posted_at IS NOT NULL AND e.currency = NEW.currency;
            IF line_count = 0 OR line_total <> NEW.total_cents OR debit_total <> NEW.total_cents THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invoice lines and posted receivable must agree'; END IF; END");
        DB::unprepared("CREATE TRIGGER invoice_line_insert BEFORE INSERT ON invoice_lines FOR EACH ROW BEGIN
            DECLARE seal DATETIME(6); DECLARE invoice_currency CHAR(3);
            SELECT sealed_at, currency INTO seal, invoice_currency FROM invoices WHERE school_id = NEW.school_id AND id = NEW.invoice_id;
            IF seal IS NOT NULL OR invoice_currency IS NULL OR invoice_currency <> NEW.currency THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Sealed invoice cannot accept lines'; END IF; END");
        foreach (['invoices' => ['delete'], 'invoice_lines' => ['update', 'delete'], 'billing_operations' => ['update', 'delete']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared("CREATE TRIGGER {$table}_no_{$action} BEFORE {$action} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Billing history is immutable'");
            }
        }
        $identity = $this->changed(['id', 'school_id', 'session_year_id', 'as_of', 'due_on', 'tax_treatment', 'fingerprint', 'snapshot', 'prepared_by', 'created_at']);
        DB::unprepared("CREATE TRIGGER bill_run_transition BEFORE UPDATE ON bill_runs FOR EACH ROW BEGIN
            DECLARE invoice_count BIGINT;
            IF $identity OR NEW.revision <> OLD.revision + 1 OR NOT (
                (OLD.state = 'preview' AND NEW.state = 'approved' AND NEW.approved_by IS NOT NULL AND NEW.approved_at IS NOT NULL AND NEW.posted_at IS NULL)
                OR (OLD.state = 'approved' AND NEW.state = 'posted' AND NEW.posted_at IS NOT NULL AND NEW.approved_by = OLD.approved_by AND NEW.approved_at = OLD.approved_at)) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bill run requires immutable preview and ordered approval'; END IF;
            IF NEW.state = 'posted' THEN
                SELECT COUNT(*) INTO invoice_count FROM invoices WHERE school_id = NEW.school_id AND bill_run_id = NEW.id AND sealed_at IS NOT NULL;
                IF invoice_count = 0 OR invoice_count <> COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot, '$.account_count')) AS UNSIGNED), -1) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Posted bill run must have every sealed invoice'; END IF;
            END IF; END");
    }

    public function down(): void
    {
        foreach (['fee_accounts', 'bill_runs', 'invoices', 'billing_operations'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain family billing and operation history.');
            }
        }
        if (DB::table('ledger_accounts')->where('type', 'fee_income')->exists()) {
            throw new RuntimeException('Retain fee income ledger accounts.');
        }
        foreach (['invoice_insert', 'invoice_finalize', 'invoice_line_insert', 'invoices_no_delete', 'invoice_lines_no_update', 'invoice_lines_no_delete', 'billing_operations_no_update', 'billing_operations_no_delete', 'bill_run_transition'] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
        foreach (['billing_operations', 'invoice_lines', 'invoices', 'bill_runs', 'fee_account_learners', 'fee_account_holders', 'fee_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
        $this->accountRules(false);
        foreach (['students', 'session_years', 'fee_plans'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropUnique('billing_'.$table.'_school_identity'));
        }
    }

    private function changed(array $columns): string
    {
        return implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", $columns));
    }

    private function accountRules(bool $income): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE ledger_accounts DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' ledger_account_rules');
        $extra = $income ? ",'fee_income'" : '';
        DB::statement("ALTER TABLE ledger_accounts ADD CONSTRAINT ledger_account_rules CHECK (
            type IN ('fee_receivable','family','wallet','vendor','clearing','due_to_school','cash_desk'$extra)
            AND currency REGEXP '^[A-Z]{3}$'
            AND normal_direction = CASE WHEN type IN ('fee_receivable','cash_desk','clearing') THEN 'debit' ELSE 'credit' END
            AND ((type = 'wallet' AND module = 'wallet') OR (type = 'vendor' AND module = 'vendors')
                OR (type IN ('fee_receivable','family'$extra) AND module = 'fees')
                OR (type IN ('cash_desk','clearing','due_to_school') AND module IN ('fees','wallet','vendors','ticketing')))
            AND balance_cents BETWEEN -9000000000000000 AND 9000000000000000
            AND (type <> 'wallet' OR balance_cents >= 0)
            AND (type NOT IN ('clearing','due_to_school') OR balance_cents = 0))");
    }
};
