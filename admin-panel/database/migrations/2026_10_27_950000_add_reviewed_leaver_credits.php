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
        Schema::create('fee_leaver_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('prior_policyless_basis', 16)->collation('utf8mb4_bin');
            $t->string('prior_reductions', 24)->collation('utf8mb4_bin');
            $t->boolean('independent_approval');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique('school_id');
            $t->unique(['school_id', 'id']);
        });
        DB::statement("ALTER TABLE fee_leaver_policies ADD CONSTRAINT fee_leaver_policy_shape CHECK (revision>0 AND prior_policyless_basis IN ('daily','calendar_month','no_credit') AND prior_reductions IN ('retain_full','proportion_reductions') AND independent_approval IN (0,1))");
        DB::unprepared("CREATE TRIGGER fee_leaver_policy_update BEFORE UPDATE ON fee_leaver_policies FOR EACH ROW BEGIN IF NEW.id<>OLD.id OR NEW.school_id<>OLD.school_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Leaver policy identity and revision are retained'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_leaver_policy_no_delete BEFORE DELETE ON fee_leaver_policies FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain school leaver credit rules'");
        DB::unprepared("CREATE TRIGGER fee_discount_review_private_identity BEFORE UPDATE ON fee_discount_runs FOR EACH ROW BEGIN IF NOT(BINARY NEW.private_explanation <=> BINARY OLD.private_explanation) OR NOT(NEW.created_at <=> OLD.created_at) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain exact original review evidence and creation time'; END IF; END");
        Schema::create('fee_leaver_runs', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            foreach (['fee_leaver_policy_id', 'fee_account_id', 'invoice_id', 'learner_id', 'session_year_id'] as $col) {
                $t->unsignedBigInteger($col);
            }
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->string('state', 12)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->json('snapshot');
            $t->json('credit_note_ids')->nullable();
            $t->text('private_explanation');
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->dateTime('sealed_at', 6)->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->unique(['school_id', 'operation_id']);
            foreach (['fee_leaver_policy_id' => 'fee_leaver_policies', 'fee_account_id' => 'fee_accounts', 'invoice_id' => 'invoices', 'learner_id' => 'students', 'session_year_id' => 'session_years'] as $col => $parent) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE fee_leaver_runs ADD CONSTRAINT fee_leaver_run_shape CHECK (revision>0 AND amount_cents BETWEEN 1 AND 100000000000 AND JSON_TYPE(snapshot)='OBJECT' AND state IN ('preview','approved','posted') AND ((state='posted' AND sealed_at IS NOT NULL AND credit_note_ids IS NOT NULL AND JSON_TYPE(credit_note_ids)='ARRAY' AND JSON_LENGTH(credit_note_ids)>0) OR (state<>'posted' AND sealed_at IS NULL AND credit_note_ids IS NULL)) AND (state='preview' OR approved_by IS NOT NULL))");
        DB::unprepared("CREATE TRIGGER fee_leaver_run_insert BEFORE INSERT ON fee_leaver_runs FOR EACH ROW BEGIN IF NEW.state<>'preview' OR NEW.revision<>1 OR NOT EXISTS(SELECT 1 FROM invoices WHERE school_id=NEW.school_id AND id=NEW.invoice_id AND fee_account_id=NEW.fee_account_id AND session_year_id=NEW.session_year_id AND currency=NEW.currency AND sealed_at IS NOT NULL) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.amount_cents')) AS SIGNED) <=> NEW.amount_cents) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.learner_id')) AS UNSIGNED) <=> NEW.learner_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.invoice_id')) AS UNSIGNED) <=> NEW.invoice_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.fee_account_id')) AS UNSIGNED) <=> NEW.fee_account_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.session_year_id')) AS UNSIGNED) <=> NEW.session_year_id) OR NOT(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.currency')) <=> NEW.currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Leaver review retains its own original invoice and learner'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_leaver_run_update BEFORE UPDATE ON fee_leaver_runs FOR EACH ROW BEGIN
   DECLARE actual_count BIGINT; DECLARE actual_total BIGINT;
   IF OLD.sealed_at IS NOT NULL OR NEW.school_id<>OLD.school_id OR NEW.id<>OLD.id OR NEW.fee_leaver_policy_id<>OLD.fee_leaver_policy_id OR NEW.fee_account_id<>OLD.fee_account_id OR NEW.invoice_id<>OLD.invoice_id OR NEW.learner_id<>OLD.learner_id OR NEW.session_year_id<>OLD.session_year_id OR NEW.operation_id<>OLD.operation_id OR NEW.fingerprint<>OLD.fingerprint OR NEW.currency<>OLD.currency OR NEW.amount_cents<>OLD.amount_cents OR NOT(NEW.snapshot <=> OLD.snapshot) OR NOT(BINARY NEW.private_explanation <=> BINARY OLD.private_explanation) OR NOT(NEW.created_at <=> OLD.created_at) OR NEW.prepared_by<>OLD.prepared_by OR NEW.revision<>OLD.revision+1 OR NOT((OLD.state='preview' AND NEW.state='approved') OR (OLD.state='approved' AND NEW.state='posted' AND NEW.approved_by=OLD.approved_by)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Leaver credit review and history are immutable'; END IF;
   IF NEW.state='approved' AND JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.policy.independent_approval'))='true' AND NEW.approved_by=NEW.prepared_by THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Another Finance approver is required'; END IF;
   IF NEW.state='posted' THEN
    SELECT COUNT(*),COALESCE(SUM(n.amount_cents),0) INTO actual_count,actual_total FROM JSON_TABLE(NEW.credit_note_ids,'$[*]' COLUMNS(slot FOR ORDINALITY,note_id BIGINT PATH '$')) x JOIN fee_credit_notes n ON n.id=x.note_id AND n.school_id=NEW.school_id AND n.fee_account_id=NEW.fee_account_id AND n.currency=NEW.currency AND n.reason_code='leaver_credit' AND n.sealed_at IS NOT NULL AND n.invoice_line_id=CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,CONCAT('$.credits[',x.slot-1,'].input.invoice_line_id'))) AS UNSIGNED) AND n.holder_user_id=CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,CONCAT('$.credits[',x.slot-1,'].input.holder_user_id'))) AS UNSIGNED) AND n.fingerprint=JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,CONCAT('$.credits[',x.slot-1,'].preview.fingerprint'))) JOIN invoice_lines l ON l.id=n.invoice_line_id AND l.school_id=NEW.school_id AND l.invoice_id=NEW.invoice_id AND l.learner_id=NEW.learner_id AND l.session_year_id=NEW.session_year_id AND l.fee_charge_id IS NULL;
    IF actual_count<>JSON_LENGTH(NEW.credit_note_ids) OR actual_total<>NEW.amount_cents OR actual_count<>JSON_LENGTH(JSON_EXTRACT(NEW.snapshot,'$.credits')) OR actual_count<>(SELECT COUNT(DISTINCT note_id) FROM JSON_TABLE(NEW.credit_note_ids,'$[*]' COLUMNS(note_id BIGINT PATH '$')) y) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Leaver credit retains exact sealed original tuition credits'; END IF;
   END IF;
  END");
        DB::unprepared("CREATE TRIGGER fee_leaver_run_no_delete BEFORE DELETE ON fee_leaver_runs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Retain leaver credit history'");
    }

    public function down(): void
    {
        if (DB::table('fee_leaver_policies')->exists() || DB::table('fee_leaver_runs')->exists()) {
            throw new RuntimeException('Retain leaver policy and original financial history.');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS fee_discount_review_private_identity');
        Schema::dropIfExists('fee_leaver_runs');
        Schema::dropIfExists('fee_leaver_policies');
    }
};
