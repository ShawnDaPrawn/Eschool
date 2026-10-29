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
        Schema::create('fee_late_charge_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('session_year_id');
            $t->foreign(['school_id', 'session_year_id'])->references(['school_id', 'id'])->on('session_years')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->boolean('enabled');
            $t->string('scope', 24)->nullable();
            $t->string('basis', 8)->nullable();
            foreach (['flat_cents', 'cap_cents'] as $c) {
                $t->bigInteger($c)->nullable();
            }
            foreach (['rate_bp', 'grace_days', 'due_days', 'vat_rate_bp'] as $c) {
                $t->unsignedInteger($c)->nullable();
            }
            $t->string('tax_treatment', 24)->nullable();
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'session_year_id', 'currency'], 'late_policy_year_currency');
        });
        DB::statement(<<<'SQL'
ALTER TABLE fee_late_charge_policies ADD CONSTRAINT late_policy_shape CHECK (
 currency REGEXP '^[A-Z]{3}$' AND revision>0 AND enabled IN (0,1) AND (enabled=0 OR (
 scope IN ('annual_only','all_original_charges') AND scope IS NOT NULL AND basis IS NOT NULL
 AND ((basis='flat' AND flat_cents BETWEEN 1 AND 100000000000 AND flat_cents IS NOT NULL AND rate_bp IS NULL) OR (basis='rate' AND rate_bp BETWEEN 1 AND 10000 AND rate_bp IS NOT NULL AND flat_cents IS NULL))
 AND cap_cents BETWEEN 1 AND 100000000000 AND cap_cents IS NOT NULL AND grace_days BETWEEN 0 AND 3650 AND grace_days IS NOT NULL AND due_days BETWEEN 0 AND 3650 AND due_days IS NOT NULL
 AND tax_treatment IN ('no_vat','vat_exempt','vat_zero','vat_inclusive') AND tax_treatment IS NOT NULL AND vat_rate_bp IS NOT NULL
 AND ((tax_treatment='vat_inclusive' AND vat_rate_bp BETWEEN 1 AND 10000) OR (tax_treatment IN ('no_vat','vat_exempt','vat_zero') AND vat_rate_bp=0)))))
SQL);
        Schema::create('fee_late_policy_revisions', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('policy_id');
            $t->foreign(['school_id', 'policy_id'])->references(['school_id', 'id'])->on('fee_late_charge_policies')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->json('snapshot');
            $t->text('private_reason');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'policy_id', 'revision'], 'late_policy_revision_identity');
        });
        $fields = ['id', 'school_id', 'session_year_id', 'currency', 'enabled', 'scope', 'basis', 'flat_cents', 'rate_bp', 'cap_cents', 'grace_days', 'due_days', 'tax_treatment', 'vat_rate_bp', 'revision', 'changed_by'];
        $match = implode(' AND ', array_map(fn ($c) => "JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.$c')) <=> COALESCE(CAST(p.$c AS CHAR),'null')", $fields));
        DB::unprepared("CREATE TRIGGER late_policy_revision_insert BEFORE INSERT ON fee_late_policy_revisions FOR EACH ROW BEGIN
            IF NOT EXISTS(SELECT 1 FROM fee_late_charge_policies p WHERE p.school_id=NEW.school_id AND p.id=NEW.policy_id AND p.revision=NEW.revision AND p.changed_by=NEW.actor_id AND ($match)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain the exact original school late-charge policy revision';END IF;END");
        DB::unprepared("CREATE TRIGGER late_policy_revision BEFORE UPDATE ON fee_late_charge_policies FOR EACH ROW BEGIN
            IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.session_year_id<>OLD.session_year_id OR NEW.currency<>OLD.currency OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Late-charge policy identity and sequential revision are fixed';END IF;END");
        Schema::create('fee_late_charges', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            foreach (['fee_account_id' => 'fee_accounts', 'original_invoice_line_id' => 'invoice_lines', 'policy_revision_id' => 'fee_late_policy_revisions', 'replacement_charge_id' => 'fee_charges'] as $c => $table) {
                $t->unsignedBigInteger($c);
                $t->foreign(['school_id', $c])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
            $t->foreignId('holder_user_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            foreach (['basis_cents', 'amount_cents', 'vat_cents'] as $c) {
                $t->bigInteger($c);
            }$t->text('reason');
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'replacement_charge_id']);
            $t->unique(['school_id', 'original_invoice_line_id', 'holder_user_id'], 'late_original_payer_once');
        });
        DB::statement("ALTER TABLE fee_late_charges ADD CONSTRAINT late_charge_bounds CHECK (currency REGEXP '^[A-Z]{3}$' AND basis_cents BETWEEN 1 AND 100000000000 AND amount_cents BETWEEN 1 AND 100000000000 AND vat_cents BETWEEN 0 AND amount_cents)");
        DB::unprepared(<<<'SQL'
CREATE TRIGGER late_charge_insert BEFORE INSERT ON fee_late_charges FOR EACH ROW BEGIN
 DECLARE matches INT;DECLARE policy_snapshot JSON;DECLARE gross DECIMAL(65,0);DECLARE expected_vat DECIMAL(65,0);
 SELECT COUNT(*) INTO matches FROM invoice_lines l JOIN invoices i ON i.school_id=l.school_id AND i.id=l.invoice_id
 LEFT JOIN fee_charges original_charge ON original_charge.school_id=l.school_id AND original_charge.id=l.fee_charge_id
 JOIN fee_late_policy_revisions r ON r.school_id=l.school_id AND r.id=NEW.policy_revision_id
 JOIN fee_late_charge_policies p ON p.school_id=r.school_id AND p.id=r.policy_id
 JOIN fee_charges c ON c.school_id=l.school_id AND c.id=NEW.replacement_charge_id
 WHERE l.school_id=NEW.school_id AND l.id=NEW.original_invoice_line_id AND i.fee_account_id=NEW.fee_account_id AND i.sealed_at IS NOT NULL
 AND (l.fee_charge_id IS NULL OR original_charge.kind='fee_item') AND NEW.basis_cents<=l.amount_cents
 AND p.enabled=1 AND p.revision=r.revision AND p.session_year_id=i.session_year_id AND p.currency=NEW.currency
 AND (p.scope='all_original_charges' OR l.fee_charge_id IS NULL)
 AND c.kind='late_charge' AND c.fee_item_id IS NULL AND c.sealed_at IS NULL AND c.created_by=NEW.actor_id
 AND c.fee_account_id=NEW.fee_account_id AND c.session_year_id=i.session_year_id AND c.learner_id=l.learner_id AND c.currency=NEW.currency AND c.amount_cents=NEW.amount_cents AND c.vat_cents=NEW.vat_cents
 AND c.item_revision=r.revision AND CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.basis_cents')) AS SIGNED)=NEW.basis_cents AND CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.policy_revision_id')) AS UNSIGNED)=NEW.policy_revision_id AND JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.tax_treatment'))=p.tax_treatment AND JSON_LENGTH(JSON_EXTRACT(c.snapshot,'$.lines'))=1
 AND JSON_LENGTH(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares'))=1
 AND CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares[0].user_id')) AS UNSIGNED)=NEW.holder_user_id
 AND CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares[0].amount_cents')) AS SIGNED)=NEW.amount_cents
 AND COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(c.snapshot,'$.lines[0].holder_shares[0].vat_cents')) AS SIGNED),0)=NEW.vat_cents;
 IF matches<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Late charge requires original non-compounding debt, policy and exact new payer invoice';END IF;
 SELECT snapshot INTO policy_snapshot FROM fee_late_policy_revisions WHERE school_id=NEW.school_id AND id=NEW.policy_revision_id;
 SET gross=IF(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.basis'))='flat',CAST(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.flat_cents')) AS SIGNED),(CAST(NEW.basis_cents AS DECIMAL(65,0))*CAST(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.rate_bp')) AS SIGNED)+5000) DIV 10000);
 SET gross=LEAST(gross,CAST(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.cap_cents')) AS SIGNED));
 SET expected_vat=IF(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.tax_treatment'))='vat_inclusive',(gross*CAST(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.vat_rate_bp')) AS SIGNED)+((10000+CAST(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.vat_rate_bp')) AS SIGNED)) DIV 2)) DIV (10000+CAST(JSON_UNQUOTE(JSON_EXTRACT(policy_snapshot,'$.vat_rate_bp')) AS SIGNED)),0);
 IF gross<>NEW.amount_cents OR expected_vat<>NEW.vat_cents THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Late charge must match the original configured gross rate, cap and VAT';END IF;
END
SQL);
        foreach (['fee_late_policy_revisions', 'fee_late_charges'] as $table) {
            foreach (['UPDATE', 'DELETE'] as $action) {
                DB::unprepared('CREATE TRIGGER '.$table.'_no_'.strtolower($action)." BEFORE $action ON $table FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Late-charge financial history is immutable'");
            }
        }
        DB::unprepared("CREATE TRIGGER late_policies_no_delete BEFORE DELETE ON fee_late_charge_policies FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain late-charge policy history'");
        $this->rules(true);
        $this->chargeGuard(true);
    }

    public function down(): void
    {
        foreach (['fee_late_charge_policies', 'fee_late_policy_revisions', 'fee_late_charges'] as $table) {
            if (DB::table($table)->exists()) {
                throw new RuntimeException('Retain late-charge policy, occurrence and invoice history.');
            }
        }
        $this->chargeGuard(false);
        $this->rules(false);
        Schema::drop('fee_late_charges');
        Schema::drop('fee_late_policy_revisions');
        Schema::drop('fee_late_charge_policies');
    }

    private function rules(bool $late): void
    {
        foreach (['fee_charges' => ['fee_charge_kind_source'], 'bill_runs' => ['bill_run_kind', 'bill_run_rules']] as $table => $checks) {
            foreach ($checks as $check) {
                $this->dropCheck($table, $check);
            }
        }
        $extra = $late ? ",'late_charge'" : '';
        DB::statement("ALTER TABLE fee_charges ADD CONSTRAINT fee_charge_kind_source CHECK ((kind='fee_item' AND fee_item_id IS NOT NULL) OR (kind IN ('payer_correction'$extra) AND fee_item_id IS NULL))");
        DB::statement("ALTER TABLE bill_runs ADD CONSTRAINT bill_run_kind CHECK (kind IN ('annual','fee_item','payer_correction'$extra)), ADD CONSTRAINT bill_run_rules CHECK (state IN ('preview','approved','posted') AND revision>0 AND ((kind='annual' AND tax_treatment='no_vat') OR (kind IN ('fee_item','payer_correction'$extra) AND tax_treatment IN ('no_vat','vat_exempt','vat_zero','vat_inclusive','vat_exclusive'))))");
    }

    private function chargeGuard(bool $late): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS fee_charge_finalize');
        $columns = ['id', 'school_id', 'fee_item_id', 'fee_account_id', 'session_year_id', 'learner_id', 'bill_run_id', 'item_revision', 'scope', 'target_id', 'source_reference', 'reference_hash', 'fingerprint', 'currency', 'amount_cents', 'snapshot', 'created_by', 'created_at'];
        $columns[] = 'kind';
        $identity = implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", $columns));
        $kind = 'NEW.kind';
        $lineage = "IF NEW.kind='payer_correction' AND NOT EXISTS(SELECT 1 FROM fee_payer_corrections WHERE school_id=NEW.school_id AND replacement_charge_id=NEW.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A replacement debt requires original unpaid correction lineage';END IF;";
        if ($late) {
            $lineage .= "IF NEW.kind='late_charge' AND NOT EXISTS(SELECT 1 FROM fee_late_charges WHERE school_id=NEW.school_id AND replacement_charge_id=NEW.id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A late invoice requires its original policy and debt occurrence';END IF;";
        }

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
