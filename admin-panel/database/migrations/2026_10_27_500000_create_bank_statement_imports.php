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
        Schema::create('bank_statement_imports', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('digest', 64)->collation('utf8mb4_bin');
            $t->unsignedInteger('row_count');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->dateTime('sealed_at', 6)->nullable();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'digest']);
        });
        Schema::create('bank_statement_lines', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->char('source_hash', 64)->collation('utf8mb4_bin');
            $t->char('payload_hash', 64)->collation('utf8mb4_bin');
            $t->date('received_on');
            $t->string('direction', 6)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->text('details');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'source_hash']);
        });
        Schema::create('bank_statement_import_rows', function (Blueprint $t): void {
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('bank_statement_import_id');
            $t->unsignedBigInteger('bank_statement_line_id');
            $t->primary(['school_id', 'bank_statement_import_id', 'bank_statement_line_id'], 'bank_import_row_pk');
            $t->foreign(['school_id', 'bank_statement_import_id'], 'bank_import_row_import_fk')->references(['school_id', 'id'])->on('bank_statement_imports')->restrictOnDelete();
            $t->foreign(['school_id', 'bank_statement_line_id'], 'bank_import_row_line_fk')->references(['school_id', 'id'])->on('bank_statement_lines')->restrictOnDelete();
        });
        Schema::create('bank_statement_resolutions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('bank_statement_line_id');
            $t->string('kind', 12)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('payment_id')->nullable();
            $t->text('reason');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'bank_statement_line_id'], 'bank_line_resolve_once');
            $t->unique(['school_id', 'payment_id'], 'bank_payment_match_once');
            $t->foreign(['school_id', 'bank_statement_line_id'], 'bank_resolution_line_fk')->references(['school_id', 'id'])->on('bank_statement_lines')->restrictOnDelete();
            $t->foreign(['school_id', 'payment_id'], 'bank_resolution_payment_fk')->references(['school_id', 'id'])->on('payments')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE bank_statement_imports ADD CONSTRAINT bank_import_count CHECK (row_count BETWEEN 1 AND 500)');
        DB::statement("ALTER TABLE bank_statement_lines ADD CONSTRAINT bank_line_rules CHECK (direction IN ('credit','debit') AND amount_cents BETWEEN 1 AND 100000000000 AND currency REGEXP '^[A-Z]{3}$')");
        DB::statement("ALTER TABLE bank_statement_resolutions ADD CONSTRAINT bank_resolution_rules CHECK ((kind = 'matched' AND payment_id IS NOT NULL) OR (kind = 'excluded' AND payment_id IS NULL))");
        DB::unprepared("CREATE TRIGGER bank_import_insert BEFORE INSERT ON bank_statement_imports FOR EACH ROW BEGIN
            IF NEW.sealed_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Seal bank import after its rows'; END IF; END");
        DB::unprepared("CREATE TRIGGER bank_import_row_insert BEFORE INSERT ON bank_statement_import_rows FOR EACH ROW BEGIN
            DECLARE seal DATETIME(6);
            SELECT sealed_at INTO seal FROM bank_statement_imports WHERE school_id = NEW.school_id AND id = NEW.bank_statement_import_id;
            IF seal IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bank import is sealed'; END IF; END");
        $identity = implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", ['id', 'school_id', 'digest', 'row_count', 'created_by', 'created_at']));
        DB::unprepared("CREATE TRIGGER bank_import_finalize BEFORE UPDATE ON bank_statement_imports FOR EACH ROW BEGIN
            DECLARE rows_count INT;
            SELECT COUNT(*) INTO rows_count FROM bank_statement_import_rows WHERE school_id = NEW.school_id AND bank_statement_import_id = NEW.id;
            IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR $identity OR rows_count <> NEW.row_count THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bank import history and row count must agree'; END IF; END");
        DB::unprepared("CREATE TRIGGER bank_resolution_insert BEFORE INSERT ON bank_statement_resolutions FOR EACH ROW BEGIN
            DECLARE line_direction VARCHAR(6); DECLARE line_cents BIGINT; DECLARE line_currency CHAR(3); DECLARE line_date DATE; DECLARE source_digest CHAR(64);
            DECLARE pay_method VARCHAR(12); DECLARE pay_cents BIGINT; DECLARE pay_currency CHAR(3); DECLARE pay_date DATE; DECLARE pay_reference VARCHAR(96);
            SELECT direction, amount_cents, currency, received_on, source_hash INTO line_direction, line_cents, line_currency, line_date, source_digest
                FROM bank_statement_lines WHERE school_id = NEW.school_id AND id = NEW.bank_statement_line_id;
            IF NOT EXISTS (SELECT 1 FROM bank_statement_import_rows r JOIN bank_statement_imports i ON i.school_id = r.school_id AND i.id = r.bank_statement_import_id
                WHERE r.school_id = NEW.school_id AND r.bank_statement_line_id = NEW.bank_statement_line_id AND i.sealed_at IS NOT NULL) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Resolve only a complete bank import'; END IF;
            IF NEW.kind = 'matched' THEN
                SELECT method, amount_cents, currency, received_on, source_reference INTO pay_method, pay_cents, pay_currency, pay_date, pay_reference
                    FROM payments WHERE school_id = NEW.school_id AND id = NEW.payment_id;
                IF line_direction <> 'credit' OR pay_method IS NULL OR pay_method <> 'eft' OR pay_cents <> line_cents OR pay_currency <> line_currency
                    OR pay_date <> line_date OR SHA2(pay_reference,256) <> source_digest THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bank match must agree with its unique EFT receipt'; END IF;
            END IF; END");
        foreach (['bank_statement_imports' => ['delete'], 'bank_statement_import_rows' => ['update', 'delete'], 'bank_statement_lines' => ['update', 'delete'], 'bank_statement_resolutions' => ['update', 'delete']] as $table => $actions) {
            foreach ($actions as $action) {
                DB::unprepared("CREATE TRIGGER {$table}_no_{$action} BEFORE {$action} ON {$table} FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bank reconciliation history is immutable'");
            }
        }
    }

    public function down(): void
    {
        foreach (['bank_statement_imports', 'bank_statement_lines', 'bank_statement_import_rows', 'bank_statement_resolutions'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain bank import and reconciliation history.');
            }
        }
        foreach (['bank_statement_resolutions', 'bank_statement_import_rows', 'bank_statement_lines', 'bank_statement_imports'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
