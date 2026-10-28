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
        Schema::create('fee_discounts', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('session_year_id');
            $t->string('name', 160);
            $t->string('kind', 20)->collation('utf8mb4_bin');
            $t->string('source_kind', 20)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('learner_id')->nullable();
            $t->unsignedBigInteger('fee_plan_id')->nullable();
            $t->unsignedBigInteger('fee_item_id')->nullable();
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('amount_kind', 12)->collation('utf8mb4_bin');
            $t->bigInteger('amount_value');
            $t->unsignedInteger('sibling_position')->nullable();
            $t->unsignedInteger('minimum_paid_bp')->nullable();
            $t->date('payment_deadline')->nullable();
            $t->json('staff_roles')->nullable();
            $t->string('overlap', 12)->collation('utf8mb4_bin');
            $t->boolean('independent_approval');
            $t->string('state', 12)->collation('utf8mb4_bin');
            $t->unsignedInteger('revision');
            $t->foreignId('prepared_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            foreach (['session_year_id' => 'session_years', 'learner_id' => 'students', 'fee_plan_id' => 'fee_plans', 'fee_item_id' => 'fee_items'] as $col => $parent) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE fee_discounts ADD CONSTRAINT fee_discount_shape CHECK (revision>0 AND independent_approval IN (0,1) AND kind IN ('sibling','staff','early_settlement','bursary','exemption') AND source_kind IN ('annual_tuition','fee_item') AND ((source_kind='annual_tuition' AND fee_item_id IS NULL) OR (source_kind='fee_item' AND fee_item_id IS NOT NULL AND fee_plan_id IS NULL)) AND (kind NOT IN ('bursary','exemption') OR learner_id IS NOT NULL) AND starts_on<=ends_on AND amount_kind IN ('percent','fixed') AND amount_value>0 AND ((amount_kind='percent' AND amount_value<=10000) OR (amount_kind='fixed' AND amount_value<=100000000000)) AND (kind<>'exemption' OR (amount_kind='percent' AND amount_value=10000)) AND ((kind='sibling' AND sibling_position IS NOT NULL AND sibling_position BETWEEN 2 AND 100) OR (kind<>'sibling' AND sibling_position IS NULL)) AND ((kind='early_settlement' AND minimum_paid_bp IS NOT NULL AND minimum_paid_bp BETWEEN 1 AND 10000 AND payment_deadline IS NOT NULL) OR (kind<>'early_settlement' AND minimum_paid_bp IS NULL AND payment_deadline IS NULL)) AND ((kind='staff' AND staff_roles IS NOT NULL AND JSON_TYPE(staff_roles)='ARRAY' AND JSON_LENGTH(staff_roles) BETWEEN 1 AND 20) OR (kind<>'staff' AND staff_roles IS NULL)) AND overlap IN ('exclusive','remaining') AND state IN ('draft','active','archived') AND (state<>'active' OR (approved_by IS NOT NULL AND (independent_approval=0 OR approved_by<>prepared_by))))");
        DB::unprepared("CREATE TRIGGER fee_discount_update BEFORE UPDATE ON fee_discounts FOR EACH ROW BEGIN IF NEW.school_id<>OLD.school_id OR NEW.id<>OLD.id OR NEW.session_year_id<>OLD.session_year_id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee discount identity and revision are retained'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_discount_no_delete BEFORE DELETE ON fee_discounts FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Archive fee discount rules instead of deleting history'");
        Schema::create('fee_discount_runs', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            foreach (['fee_discount_id', 'fee_account_id', 'learner_id', 'session_year_id'] as $col) {
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
            $t->unsignedBigInteger('posted_learner_id')->nullable()->storedAs("CASE WHEN state='posted' THEN learner_id ELSE NULL END");
            $t->unique(['school_id', 'fee_discount_id', 'session_year_id', 'posted_learner_id'], 'fee_discount_once_per_learner');
            foreach (['fee_discount_id' => 'fee_discounts', 'fee_account_id' => 'fee_accounts', 'learner_id' => 'students', 'session_year_id' => 'session_years'] as $col => $parent) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on($parent)->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE fee_discount_runs ADD CONSTRAINT fee_discount_run_shape CHECK (revision>0 AND amount_cents>0 AND amount_cents<=100000000000 AND JSON_TYPE(snapshot)='OBJECT' AND state IN ('preview','approved','posted') AND ((state='posted' AND sealed_at IS NOT NULL AND credit_note_ids IS NOT NULL AND JSON_TYPE(credit_note_ids)='ARRAY' AND JSON_LENGTH(credit_note_ids)>0) OR (state<>'posted' AND sealed_at IS NULL AND credit_note_ids IS NULL)) AND (state='preview' OR approved_by IS NOT NULL))");
        DB::unprepared("CREATE TRIGGER fee_discount_run_insert BEFORE INSERT ON fee_discount_runs FOR EACH ROW BEGIN IF NEW.state<>'preview' OR NEW.revision<>1 OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.rule_id')) AS UNSIGNED) <=> NEW.fee_discount_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.fee_account_id')) AS UNSIGNED) <=> NEW.fee_account_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.learner_id')) AS UNSIGNED) <=> NEW.learner_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.session_year_id')) AS UNSIGNED) <=> NEW.session_year_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.amount_cents')) AS SIGNED) <=> NEW.amount_cents) OR NOT(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.currency')) <=> NEW.currency) OR NOT EXISTS(SELECT 1 FROM fee_discounts WHERE id=NEW.fee_discount_id AND school_id=NEW.school_id AND session_year_id=NEW.session_year_id AND currency=NEW.currency AND state='active') OR NOT EXISTS(SELECT 1 FROM fee_accounts WHERE id=NEW.fee_account_id AND school_id=NEW.school_id AND currency=NEW.currency) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Discount review requires its own active rule and account'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_discount_run_update BEFORE UPDATE ON fee_discount_runs FOR EACH ROW BEGIN
   DECLARE actual_count BIGINT; DECLARE actual_total BIGINT;
   IF OLD.sealed_at IS NOT NULL OR NEW.school_id<>OLD.school_id OR NEW.id<>OLD.id OR NEW.fee_discount_id<>OLD.fee_discount_id OR NEW.fee_account_id<>OLD.fee_account_id OR NEW.learner_id<>OLD.learner_id OR NEW.session_year_id<>OLD.session_year_id OR NEW.operation_id<>OLD.operation_id OR NEW.fingerprint<>OLD.fingerprint OR NEW.currency<>OLD.currency OR NEW.amount_cents<>OLD.amount_cents OR NOT(NEW.snapshot <=> OLD.snapshot) OR NEW.prepared_by<>OLD.prepared_by OR NEW.private_explanation<>OLD.private_explanation OR NEW.revision<>OLD.revision+1 OR NOT((OLD.state='preview' AND NEW.state='approved') OR (OLD.state='approved' AND NEW.state='posted' AND NEW.approved_by=OLD.approved_by)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Discount reviews and posted history are immutable'; END IF;
   IF NEW.state='approved' AND EXISTS(SELECT 1 FROM fee_discounts WHERE id=NEW.fee_discount_id AND school_id=NEW.school_id AND independent_approval=1) AND NEW.approved_by=NEW.prepared_by THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='This discount requires another approver'; END IF;
   IF NEW.state='posted' THEN
    SELECT COUNT(*),COALESCE(SUM(n.amount_cents),0) INTO actual_count,actual_total FROM JSON_TABLE(NEW.credit_note_ids,'$[*]' COLUMNS(slot FOR ORDINALITY,note_id BIGINT PATH '$')) x JOIN fee_credit_notes n ON n.id=x.note_id AND n.school_id=NEW.school_id AND n.fee_account_id=NEW.fee_account_id AND n.currency=NEW.currency AND n.sealed_at IS NOT NULL AND n.invoice_line_id=CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,CONCAT('$.credits[',x.slot-1,'].input.invoice_line_id'))) AS UNSIGNED) AND n.holder_user_id=CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,CONCAT('$.credits[',x.slot-1,'].input.holder_user_id'))) AS UNSIGNED) AND n.fingerprint=JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,CONCAT('$.credits[',x.slot-1,'].preview.fingerprint'))) JOIN invoice_lines l ON l.id=n.invoice_line_id AND l.school_id=NEW.school_id AND l.learner_id=NEW.learner_id AND l.session_year_id=NEW.session_year_id;
    IF actual_count<>JSON_LENGTH(NEW.credit_note_ids) OR actual_total<>NEW.amount_cents OR actual_count<>JSON_LENGTH(JSON_EXTRACT(NEW.snapshot,'$.credits')) OR actual_count<>(SELECT COUNT(DISTINCT note_id) FROM JSON_TABLE(NEW.credit_note_ids,'$[*]' COLUMNS(note_id BIGINT PATH '$')) y) OR EXISTS(SELECT 1 FROM fee_discount_runs WHERE school_id=NEW.school_id AND fee_discount_id=NEW.fee_discount_id AND learner_id=NEW.learner_id AND session_year_id=NEW.session_year_id AND sealed_at IS NOT NULL) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Discount must retain exact original-holder credits and apply once'; END IF;
   END IF;
  END");
        DB::unprepared("CREATE TRIGGER fee_discount_run_no_delete BEFORE DELETE ON fee_discount_runs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Discount application history is retained'");
    }

    public function down(): void
    {
        if (DB::table('fee_discount_runs')->exists() || DB::table('fee_discounts')->exists()) {
            throw new RuntimeException('Retain approved discount rules and financial history.');
        }
        Schema::dropIfExists('fee_discount_runs');
        Schema::dropIfExists('fee_discounts');
    }
};
