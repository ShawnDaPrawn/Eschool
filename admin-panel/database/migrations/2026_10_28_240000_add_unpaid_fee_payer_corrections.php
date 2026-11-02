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
        Schema::table('fee_charges', fn (Blueprint $t) => $t->string('kind', 16)->default('fee_item')->collation('utf8mb4_bin'));
        $this->feeItemNullability(true);
        DB::statement("ALTER TABLE fee_charges ADD CONSTRAINT fee_charge_kind_source CHECK ((kind='fee_item' AND fee_item_id IS NOT NULL) OR (kind='payer_correction' AND fee_item_id IS NULL))");
        $this->runRules(true);
        Schema::create('fee_payer_corrections', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            foreach (['fee_account_id' => 'fee_accounts', 'original_invoice_line_id' => 'invoice_lines', 'credit_note_id' => 'fee_credit_notes', 'replacement_charge_id' => 'fee_charges'] as $column => $parent) {
                $t->unsignedBigInteger($column);
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
            foreach (['original_holder_id', 'new_holder_id', 'actor_id'] as $column) {
                $t->foreignId($column)->constrained('users')->restrictOnDelete();
            }
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->bigInteger('vat_cents');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'credit_note_id']);
            $t->unique(['school_id', 'replacement_charge_id']);
        });
        DB::statement("ALTER TABLE fee_payer_corrections ADD CONSTRAINT payer_correction_bounds CHECK (currency REGEXP '^[A-Z]{3}$' AND amount_cents BETWEEN 1 AND 100000000000 AND vat_cents BETWEEN 0 AND amount_cents AND original_holder_id<>new_holder_id)");
        DB::unprepared(<<<'SQL'
CREATE TRIGGER payer_correction_insert BEFORE INSERT ON fee_payer_corrections FOR EACH ROW BEGIN
    DECLARE matches INT;
    SELECT COUNT(*) INTO matches FROM fee_credit_notes n
    JOIN invoice_lines l ON l.school_id=n.school_id AND l.id=n.invoice_line_id
    JOIN invoices i ON i.school_id=l.school_id AND i.id=l.invoice_id
    JOIN fee_charges c ON c.school_id=n.school_id AND c.id=NEW.replacement_charge_id
    WHERE n.school_id=NEW.school_id AND n.id=NEW.credit_note_id AND n.sealed_at IS NOT NULL
      AND n.invoice_line_id=NEW.original_invoice_line_id AND n.fee_account_id=NEW.fee_account_id
      AND n.holder_user_id=NEW.original_holder_id AND n.created_by=NEW.actor_id
      AND n.currency=NEW.currency AND n.amount_cents=NEW.amount_cents AND n.unpaid_cents=NEW.amount_cents AND n.released_cents=0 AND n.vat_cents=NEW.vat_cents
      AND c.fee_account_id=NEW.fee_account_id AND c.session_year_id=i.session_year_id AND c.currency=n.currency
      AND c.kind='payer_correction' AND c.fee_item_id IS NULL AND c.sealed_at IS NULL AND c.created_by=NEW.actor_id
      AND c.amount_cents=NEW.amount_cents AND c.vat_cents=NEW.vat_cents AND c.learner_id=l.learner_id
      AND JSON_LENGTH(JSON_EXTRACT(c.snapshot,'$.lines'))=1
      AND JSON_LENGTH(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares'))=1
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares[0].user_id')) AS UNSIGNED)=NEW.new_holder_id
      AND CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares[0].amount_cents')) AS SIGNED)=NEW.amount_cents
      AND COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares[0].vat_cents')) AS SIGNED),0)=NEW.vat_cents;
    IF matches<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payer correction must preserve original unpaid credit and exact replacement debt';END IF;
END
SQL);
        foreach (['UPDATE', 'DELETE'] as $action) {
            DB::unprepared('CREATE TRIGGER payer_corrections_no_'.strtolower($action)." BEFORE $action ON fee_payer_corrections FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Payer correction history is immutable'");
        }
        $this->chargeGuard(true);
    }

    public function down(): void
    {
        if (DB::table('fee_payer_corrections')->exists() || DB::table('fee_charges')->where('kind', '<>', 'fee_item')->exists()) {
            throw new RuntimeException('Retain payer correction, original credit and replacement invoice history.');
        }
        $this->chargeGuard(false);
        Schema::drop('fee_payer_corrections');
        $this->dropCheck('fee_charges', 'fee_charge_kind_source');
        Schema::table('fee_charges', fn (Blueprint $t) => $t->dropColumn('kind'));
        $this->feeItemNullability(false);
        $this->runRules(false);
    }

    /** Preserve the composite tenant/source reference while changing nullability. */
    private function feeItemNullability(bool $nullable): void
    {
        $foreign = collect(Schema::getForeignKeys('fee_charges'))->first(fn (array $key): bool => $key['columns'] === ['school_id', 'fee_item_id'] && $key['foreign_table'] === 'fee_items'
                && $key['foreign_columns'] === ['school_id', 'id']);
        if ($foreign === null) {
            throw new RuntimeException('The composite fee-item reference must exist before changing charge nullability.');
        }
        $oldName = str_replace('`', '``', $foreign['name']);
        $newName = 'fee_charges_fee_item_'.($nullable ? 'optional' : 'required');
        if ($newName === $foreign['name']) {
            $newName .= '_restored';
        }
        $null = $nullable ? 'NULL' : 'NOT NULL';
        // MySQL rejects tightening a column under its existing FK, and rejects
        // dropping and re-adding the same FK name in one statement. Alternate
        // names keep this atomic, with identical columns and RESTRICT behavior.
        // Foreign-key checks remain enabled throughout the migration.
        DB::statement("ALTER TABLE fee_charges DROP FOREIGN KEY `$oldName`,
            MODIFY fee_item_id BIGINT UNSIGNED $null,
            ADD CONSTRAINT `$newName` FOREIGN KEY (school_id, fee_item_id)
                REFERENCES fee_items (school_id, id) ON DELETE RESTRICT");
    }

    private function runRules(bool $extended): void
    {
        $this->dropCheck('bill_runs', 'bill_run_kind');
        $this->dropCheck('bill_runs', 'bill_run_rules');
        $kinds = $extended ? "'annual','fee_item','payer_correction'" : "'annual','fee_item'";
        $charged = $extended ? "'fee_item','payer_correction'" : "'fee_item'";
        DB::statement("ALTER TABLE bill_runs ADD CONSTRAINT bill_run_kind CHECK (kind IN ($kinds)), ADD CONSTRAINT bill_run_rules CHECK (state IN ('preview','approved','posted') AND revision>0 AND ((kind='annual' AND tax_treatment='no_vat') OR (kind IN ($charged) AND tax_treatment IN ('no_vat','vat_exempt','vat_zero','vat_inclusive','vat_exclusive'))))");
    }

    private function chargeGuard(bool $extended): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS fee_charge_finalize');
        $columns = ['id', 'school_id', 'fee_item_id', 'fee_account_id', 'session_year_id', 'learner_id', 'bill_run_id', 'item_revision', 'scope', 'target_id', 'source_reference', 'reference_hash', 'fingerprint', 'currency', 'amount_cents', 'snapshot', 'created_by', 'created_at'];
        if ($extended) {
            $columns[] = 'kind';
        }
        $identity = implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", $columns));
        $kind = $extended ? 'NEW.kind' : "'fee_item'";
        $lineage = $extended ? "IF NEW.kind='payer_correction' AND NOT EXISTS(SELECT 1 FROM fee_payer_corrections WHERE school_id=NEW.school_id AND replacement_charge_id=NEW.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A replacement debt requires original unpaid correction lineage';END IF;" : '';
        DB::unprepared("CREATE TRIGGER fee_charge_finalize BEFORE UPDATE ON fee_charges FOR EACH ROW BEGIN
            DECLARE run_kind VARCHAR(16);DECLARE run_state VARCHAR(12);DECLARE total DECIMAL(65,0);DECLARE line_total DECIMAL(65,0);DECLARE invoice_count INT;DECLARE line_count INT;
            IF OLD.sealed_at IS NOT NULL OR NEW.sealed_at IS NULL OR $identity THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee charge history is immutable';END IF;
            SELECT kind,state INTO run_kind,run_state FROM bill_runs WHERE school_id=NEW.school_id AND id=NEW.bill_run_id;
            SELECT COUNT(*),COALESCE(SUM(total_cents),0) INTO invoice_count,total FROM invoices WHERE school_id=NEW.school_id AND bill_run_id=NEW.bill_run_id AND fee_account_id=NEW.fee_account_id AND session_year_id=NEW.session_year_id AND currency=NEW.currency AND sealed_at IS NOT NULL;
            SELECT COUNT(*),COALESCE(SUM(amount_cents),0) INTO line_count,line_total FROM invoice_lines WHERE school_id=NEW.school_id AND fee_charge_id=NEW.id;
            IF run_kind<>$kind OR run_state<>'posted' OR invoice_count<>1 OR total<>NEW.amount_cents OR line_total<>NEW.amount_cents OR line_count<>JSON_LENGTH(JSON_EXTRACT(NEW.snapshot,'$.lines')) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee charge and posted invoice must agree';END IF;
            $lineage END");
    }

    private function dropCheck(string $table, string $constraint): void
    {
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE '.$table.' DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' '.$constraint);
    }
};
