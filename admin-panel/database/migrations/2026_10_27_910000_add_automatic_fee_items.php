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
        Schema::create('fee_item_eligibility_rules', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_item_id');
            $t->string('mode', 16)->collation('utf8mb4_bin');
            $t->string('target_scope', 12)->collation('utf8mb4_bin');
            $t->unsignedBigInteger('target_id')->nullable();
            $t->json('applies_to');
            $t->foreignId('activity_group_id')->nullable()->constrained('activity_groups')->restrictOnDelete();
            $t->foreignId('teaching_group_id')->nullable()->constrained('teaching_groups')->restrictOnDelete();
            $t->foreignId('subject_id')->nullable()->constrained('subjects')->restrictOnDelete();
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'fee_item_id']);
            $t->foreign(['school_id', 'fee_item_id'])->references(['school_id', 'id'])->on('fee_items')->restrictOnDelete();
        });
        DB::statement("ALTER TABLE fee_item_eligibility_rules ADD CONSTRAINT fee_item_eligibility_shape CHECK (revision>0 AND mode IN ('manual','compulsory','membership') AND target_scope IN ('all','grade','class') AND ((target_scope='all' AND target_id IS NULL) OR (target_scope<>'all' AND target_id IS NOT NULL)) AND JSON_TYPE(applies_to)='ARRAY' AND JSON_LENGTH(applies_to)<=20 AND (mode<>'manual' OR (target_scope='all' AND JSON_LENGTH(applies_to)=0)) AND ((mode='membership' AND (activity_group_id IS NOT NULL)+(teaching_group_id IS NOT NULL)+(subject_id IS NOT NULL)=1) OR (mode<>'membership' AND activity_group_id IS NULL AND teaching_group_id IS NULL AND subject_id IS NULL)))");
        foreach (['INSERT', 'UPDATE'] as $action) {
            $revision = $action === 'UPDATE' ? "IF NEW.school_id<>OLD.school_id OR NEW.fee_item_id<>OLD.fee_item_id OR NEW.id<>OLD.id OR NEW.revision<>OLD.revision+1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item eligibility identity and revisions are retained'; END IF;" : '';
            DB::unprepared('CREATE TRIGGER fee_item_eligibility_'.strtolower($action)." BEFORE $action ON fee_item_eligibility_rules FOR EACH ROW BEGIN
                $revision
                IF (NEW.activity_group_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM activity_groups WHERE id=NEW.activity_group_id AND school_id=NEW.school_id)) OR (NEW.teaching_group_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM teaching_groups WHERE id=NEW.teaching_group_id AND school_id=NEW.school_id)) OR (NEW.subject_id IS NOT NULL AND NOT EXISTS(SELECT 1 FROM subjects WHERE id=NEW.subject_id AND school_id=NEW.school_id)) OR (NEW.target_scope='grade' AND NOT EXISTS(SELECT 1 FROM grade_levels WHERE id=NEW.target_id)) OR (NEW.target_scope='class' AND NOT EXISTS(SELECT 1 FROM classes WHERE id=NEW.target_id AND school_id=NEW.school_id)) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item rules require school-owned targets'; END IF; END");
        }
        DB::unprepared("CREATE TRIGGER fee_item_eligibility_no_delete BEFORE DELETE ON fee_item_eligibility_rules FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item eligibility is revisioned, not deleted'");
        Schema::create('fee_item_batches', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_item_id');
            $t->unsignedBigInteger('session_year_id');
            $t->char('operation_id', 36)->collation('utf8mb4_bin');
            $t->char('fingerprint', 64)->collation('utf8mb4_bin');
            $t->char('currency', 3)->collation('utf8mb4_bin');
            $t->bigInteger('amount_cents');
            $t->json('snapshot');
            $t->json('charge_ids');
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'operation_id']);
            foreach (['fee_item_id' => 'fee_items', 'session_year_id' => 'session_years'] as $column => $table) {
                $t->foreign(['school_id', $column])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
        });
        DB::statement("ALTER TABLE fee_item_batches ADD CONSTRAINT fee_item_batch_shape CHECK (amount_cents>0 AND amount_cents<=9000000000000000 AND JSON_TYPE(charge_ids)='ARRAY' AND JSON_LENGTH(charge_ids) BETWEEN 1 AND 1500 AND JSON_TYPE(snapshot)='OBJECT')");
        DB::unprepared("CREATE TRIGGER fee_item_batch_insert BEFORE INSERT ON fee_item_batches FOR EACH ROW BEGIN
            DECLARE actual_count BIGINT; DECLARE actual_total BIGINT;
            SELECT COUNT(*),COALESCE(SUM(c.amount_cents),0) INTO actual_count,actual_total FROM JSON_TABLE(NEW.charge_ids,'$[*]' COLUMNS(slot FOR ORDINALITY,charge_id BIGINT PATH '$')) x JOIN fee_charges c ON c.school_id=NEW.school_id AND c.id=x.charge_id AND c.fee_item_id=NEW.fee_item_id AND c.session_year_id=NEW.session_year_id AND c.currency=NEW.currency AND c.sealed_at IS NOT NULL AND c.fingerprint=JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,CONCAT('$.charges[',x.slot-1,'].preview.fingerprint'))) AND c.reference_hash=JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.reference_hash'));
            IF NOT(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.operation_id')) <=> NEW.operation_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.fee_item_id')) AS UNSIGNED) <=> NEW.fee_item_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.session_year_id')) AS UNSIGNED) <=> NEW.session_year_id) OR NOT(CAST(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.amount_cents')) AS SIGNED) <=> NEW.amount_cents) OR NOT(JSON_UNQUOTE(JSON_EXTRACT(NEW.snapshot,'$.currency')) <=> NEW.currency) OR NOT(JSON_LENGTH(JSON_EXTRACT(NEW.snapshot,'$.charges')) <=> actual_count) OR actual_count<>JSON_LENGTH(NEW.charge_ids) OR actual_total<>NEW.amount_cents OR actual_count<>(SELECT COUNT(DISTINCT charge_id) FROM JSON_TABLE(NEW.charge_ids,'$[*]' COLUMNS(charge_id BIGINT PATH '$')) d) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item batch must retain its exact sealed charges'; END IF; END");
        foreach (['UPDATE', 'DELETE'] as $action) {
            DB::unprepared('CREATE TRIGGER fee_item_batch_no_'.strtolower($action)." BEFORE $action ON fee_item_batches FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fee item batch history is immutable'");
        }
    }

    public function down(): void
    {
        if (DB::table('fee_item_batches')->exists() || DB::table('fee_item_eligibility_rules')->exists()) {
            throw new RuntimeException('Retain automatic fee item rules and batch history.');
        }
        Schema::dropIfExists('fee_item_batches');
        Schema::dropIfExists('fee_item_eligibility_rules');
    }
};
