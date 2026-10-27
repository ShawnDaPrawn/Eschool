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
        Schema::create('fee_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('name', 160);
            $t->string('scope', 12)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->string('tax_treatment', 24)->collation('utf8mb4_bin');
            $t->string('state', 12)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->timestamps();
            $t->unique(['school_id', 'id']);
        });
        DB::statement("ALTER TABLE fee_items ADD CONSTRAINT fee_item_rules CHECK (scope IN ('learner','family') AND state IN ('draft','active','archived') AND tax_treatment='no_vat' AND currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND revision>0)");
        Schema::table('bill_runs', fn (Blueprint $t) => $t->string('kind', 16)->default('annual')->collation('utf8mb4_bin'));
        DB::statement("ALTER TABLE bill_runs ADD CONSTRAINT bill_run_kind CHECK (kind IN ('annual','fee_item'))");
        Schema::create('fee_charges', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_item_id');
            $t->unsignedBigInteger('fee_account_id');
            $t->unsignedBigInteger('session_year_id');
            $t->unsignedBigInteger('learner_id')->nullable();
            $t->unsignedBigInteger('bill_run_id');
            $t->unsignedInteger('item_revision');
            $t->string('scope', 12)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('target_id');
            $t->text('source_reference')->collation('utf8mb4_bin');
            $t->char('reference_hash', 64)->collation('utf8mb4_bin');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->json('snapshot');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->dateTime('sealed_at', 6)->nullable();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'bill_run_id']);
            $t->unique(['school_id', 'fee_item_id', 'session_year_id', 'scope', 'target_id', 'reference_hash'], 'fee_charge_occurrence_once');
            foreach (['fee_item_id' => 'fee_items', 'fee_account_id' => 'fee_accounts', 'session_year_id' => 'session_years', 'learner_id' => 'students', 'bill_run_id' => 'bill_runs'] as $col => $parent) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE fee_charges ADD CONSTRAINT fee_charge_rules CHECK (scope IN ('learner','family') AND amount_cents BETWEEN 1 AND 100000000000 AND item_revision>0 AND currency REGEXP '^[A-Z]{3}$' AND ((scope='learner' AND learner_id IS NOT NULL AND target_id=learner_id) OR (scope='family' AND learner_id IS NULL AND target_id=fee_account_id)))");
        Schema::table('invoice_lines', function (Blueprint $t): void {
            $t->unsignedBigInteger('fee_charge_id')->nullable();
            $t->foreign(['school_id', 'fee_charge_id'])->references(['school_id', 'id'])->on('fee_charges')->restrictOnDelete();
            $t->unique(['school_id', 'fee_charge_id', 'learner_id'], 'fee_charge_learner_once');
            $t->dropForeign(['school_id', 'fee_plan_id']);
            $t->dropUnique('annual_tuition_charge_once');
        });
        DB::statement('ALTER TABLE invoice_lines MODIFY fee_plan_id BIGINT UNSIGNED NULL, MODIFY plan_revision INT UNSIGNED NULL, ADD annual_learner_id BIGINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN fee_charge_id IS NULL THEN learner_id ELSE NULL END) STORED');
        Schema::table('invoice_lines', function (Blueprint $t): void {
            $t->unique(['school_id', 'session_year_id', 'annual_learner_id'], 'annual_tuition_charge_once');
            $t->foreign(['school_id', 'fee_plan_id'])->references(['school_id', 'id'])->on('fee_plans')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE invoice_lines ADD CONSTRAINT invoice_line_source CHECK ((fee_charge_id IS NULL AND fee_plan_id IS NOT NULL AND plan_revision IS NOT NULL) OR (fee_charge_id IS NOT NULL AND fee_plan_id IS NULL AND plan_revision IS NULL))');
        $this->guards(true);
        DB::unprepared("CREATE TRIGGER fee_item_revision BEFORE UPDATE ON fee_items FOR EACH ROW BEGIN
   IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 OR ((NEW.scope<>OLD.scope OR NEW.currency<>OLD.currency) AND EXISTS(SELECT 1 FROM fee_charges WHERE school_id=OLD.school_id AND fee_item_id=OLD.id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item revision, scope and currency must preserve charge history'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_charge_insert BEFORE INSERT ON fee_charges FOR EACH ROW BEGIN IF NEW.sealed_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Finalize charge after its posted invoice'; END IF; END");
        $identity = $this->changed(['id', 'school_id', 'fee_item_id', 'fee_account_id', 'session_year_id', 'learner_id', 'bill_run_id', 'item_revision', 'scope', 'target_id', 'source_reference', 'reference_hash', 'fingerprint', 'currency', 'amount_cents', 'snapshot', 'created_by', 'created_at']);
        DB::unprepared("CREATE TRIGGER fee_charge_finalize BEFORE UPDATE ON fee_charges FOR EACH ROW BEGIN
   DECLARE run_kind VARCHAR(16);DECLARE run_state VARCHAR(12);DECLARE total DECIMAL(65,0);DECLARE line_total DECIMAL(65,0);DECLARE invoice_count INT;DECLARE line_count INT;
   IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR $identity THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee charge history is immutable'; END IF;
   SELECT kind,state INTO run_kind,run_state FROM bill_runs WHERE school_id=NEW.school_id AND id=NEW.bill_run_id;
   SELECT COUNT(*),COALESCE(SUM(total_cents),0) INTO invoice_count,total FROM invoices WHERE school_id=NEW.school_id AND bill_run_id=NEW.bill_run_id AND fee_account_id=NEW.fee_account_id AND session_year_id=NEW.session_year_id AND currency=NEW.currency AND sealed_at IS NOT NULL;
   SELECT COUNT(*),COALESCE(SUM(amount_cents),0) INTO line_count,line_total FROM invoice_lines WHERE school_id=NEW.school_id AND fee_charge_id=NEW.id;
   IF run_kind <> 'fee_item' OR run_state <> 'posted' OR invoice_count<>1 OR total<>NEW.amount_cents OR line_total<>NEW.amount_cents OR line_count<>JSON_LENGTH(JSON_EXTRACT(NEW.snapshot,'$.lines')) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee charge and posted invoice must agree'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_charges_no_delete BEFORE DELETE ON fee_charges FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee charge history is immutable'");
    }

    public function down(): void
    {
        if (DB::table('fee_charges')->exists() || DB::table('fee_items')->exists()) {
            throw new RuntimeException('Retain fee item and charge history.');
        }
        $this->guards(false);
        foreach (['fee_item_revision', 'fee_charge_insert', 'fee_charge_finalize', 'fee_charges_no_delete'] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
        $this->dropCheck('invoice_lines', 'invoice_line_source');
        Schema::table('invoice_lines', function (Blueprint $t): void {
            $t->dropForeign(['school_id', 'fee_charge_id']);
            $t->dropForeign(['school_id', 'fee_plan_id']);
            $t->dropUnique('fee_charge_learner_once');
            $t->dropUnique('annual_tuition_charge_once');
        });
        DB::statement('ALTER TABLE invoice_lines DROP COLUMN annual_learner_id, DROP COLUMN fee_charge_id, MODIFY fee_plan_id BIGINT UNSIGNED NOT NULL, MODIFY plan_revision INT UNSIGNED NOT NULL');
        Schema::table('invoice_lines', function (Blueprint $t): void {
            $t->unique(['school_id', 'session_year_id', 'learner_id'], 'annual_tuition_charge_once');
            $t->foreign(['school_id', 'fee_plan_id'])->references(['school_id', 'id'])->on('fee_plans')->restrictOnDelete();
        });
        Schema::dropIfExists('fee_charges');
        Schema::dropIfExists('fee_items');
        $this->dropCheck('bill_runs', 'bill_run_kind');
        Schema::table('bill_runs', fn (Blueprint $t) => $t->dropColumn('kind'));
    }

    private function guards(bool $items): void
    {
        foreach (['invoice_line_insert', 'bill_run_transition'] as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
        $itemGuard = $items ? '
   DECLARE run_kind VARCHAR(16);DECLARE invoice_run BIGINT;DECLARE invoice_account BIGINT;DECLARE charge_run BIGINT;DECLARE charge_account BIGINT;DECLARE charge_year BIGINT;DECLARE charge_snapshot JSON;DECLARE idx INT DEFAULT 0;DECLARE line JSON;DECLARE matched INT DEFAULT 0;DECLARE charge_seal DATETIME(6);' : '';
        $itemCheck = $items ? "
   SELECT i.bill_run_id,i.fee_account_id,b.kind INTO invoice_run,invoice_account,run_kind FROM invoices i JOIN bill_runs b ON b.school_id=i.school_id AND b.id=i.bill_run_id WHERE i.school_id=NEW.school_id AND i.id=NEW.invoice_id;
   IF (run_kind='annual' AND NEW.fee_charge_id IS NOT NULL) OR (run_kind='fee_item' AND NEW.fee_charge_id IS NULL) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Invoice source kind mismatch'; END IF;
   IF NEW.fee_charge_id IS NOT NULL THEN
    SELECT bill_run_id,fee_account_id,session_year_id,snapshot,sealed_at INTO charge_run,charge_account,charge_year,charge_snapshot,charge_seal FROM fee_charges WHERE school_id=NEW.school_id AND id=NEW.fee_charge_id;
    WHILE idx<COALESCE(JSON_LENGTH(JSON_EXTRACT(charge_snapshot,'$.lines')),0) DO
     SET line=JSON_EXTRACT(charge_snapshot,CONCAT('$.lines[',idx,']'));
     IF CAST(JSON_UNQUOTE(JSON_EXTRACT(line,'$.learner_id')) AS UNSIGNED)=NEW.learner_id AND CAST(JSON_UNQUOTE(JSON_EXTRACT(line,'$.amount_cents')) AS SIGNED)=NEW.amount_cents AND JSON_LENGTH(NEW.holder_shares)=JSON_LENGTH(JSON_EXTRACT(line,'$.holder_shares')) AND JSON_CONTAINS(NEW.holder_shares,JSON_EXTRACT(line,'$.holder_shares')) AND JSON_CONTAINS(JSON_EXTRACT(line,'$.holder_shares'),NEW.holder_shares) THEN SET matched=matched+1; END IF;
     SET idx=idx+1;
    END WHILE;
    IF charge_run IS NULL OR charge_seal IS NOT NULL OR charge_run<>invoice_run OR charge_account<>invoice_account OR charge_year<>NEW.session_year_id OR matched<>1 OR NEW.description<>JSON_UNQUOTE(JSON_EXTRACT(charge_snapshot,'$.item_name')) OR NEW.period_start<>JSON_UNQUOTE(JSON_EXTRACT(charge_snapshot,'$.charged_on')) OR NEW.period_end<>JSON_UNQUOTE(JSON_EXTRACT(charge_snapshot,'$.charged_on')) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item line must match its reviewed charge'; END IF;
   END IF;" : '';
        DB::unprepared("CREATE TRIGGER invoice_line_insert BEFORE INSERT ON invoice_lines FOR EACH ROW BEGIN
   DECLARE seal DATETIME(6);DECLARE invoice_currency CHAR(3);$itemGuard
   SELECT sealed_at,currency INTO seal,invoice_currency FROM invoices WHERE school_id=NEW.school_id AND id=NEW.invoice_id;
   IF seal IS NOT NULL OR invoice_currency IS NULL OR invoice_currency<>NEW.currency THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Sealed invoice cannot accept lines'; END IF;$itemCheck END");
        $columns = ['id', 'school_id', 'session_year_id', 'as_of', 'due_on', 'tax_treatment', 'fingerprint', 'snapshot', 'prepared_by', 'created_at'];
        if ($items) {
            $columns[] = 'kind';
        }$identity = $this->changed($columns);
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

    private function changed(array $columns): string
    {
        return implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", $columns));
    }

    private function dropCheck(string $table, string $constraint): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE '.$table.' DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' '.$constraint);
    }
};
