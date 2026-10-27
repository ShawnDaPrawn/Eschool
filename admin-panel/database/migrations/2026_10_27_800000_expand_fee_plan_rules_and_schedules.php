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
        Schema::table('fee_plans', function (Blueprint $t): void {
            $t->json('applies_to')->nullable();
            $t->json('payment_options')->nullable();
            $t->string('default_payment_option', 32)->nullable();
        });
        Schema::table('invoice_lines', function (Blueprint $t): void {
            $t->unsignedTinyInteger('installment_number')->default(1);
            $t->date('installment_due_on')->nullable();
            $t->dropUnique('annual_tuition_charge_once');
            $t->unique(['school_id', 'session_year_id', 'annual_learner_id', 'installment_number'], 'annual_tuition_charge_once');
        });
        DB::statement('ALTER TABLE invoice_lines ADD CONSTRAINT invoice_installment_bounds CHECK (installment_number BETWEEN 1 AND 24 AND (fee_charge_id IS NULL OR (installment_number=1 AND installment_due_on IS NULL)))');
        Schema::create('fee_payment_choices', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('session_year_id');
            $t->unsignedBigInteger('learner_id');
            $t->unsignedBigInteger('fee_plan_id');
            $t->string('option_key', 32);
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'session_year_id', 'learner_id'], 'fee_payment_choice_once');
            foreach (['session_year_id' => 'session_years', 'learner_id' => 'students', 'fee_plan_id' => 'fee_plans'] as $col => $table) {
                $t->foreign(['school_id', $col])->references(['school_id', 'id'])->on($table)->restrictOnDelete();
            }
        });
        DB::unprepared("CREATE TRIGGER invoice_installment_insert BEFORE INSERT ON invoice_lines FOR EACH ROW BEGIN
            DECLARE invoice_account BIGINT;DECLARE parts JSON;DECLARE row_data JSON;DECLARE part_data JSON;DECLARE p INT DEFAULT 0;DECLARE matched INT DEFAULT 0;
            IF NEW.fee_charge_id IS NULL THEN
                SELECT JSON_EXTRACT(b.snapshot,CONCAT('$.installments.',CHAR(34),NEW.learner_id,CHAR(34))) ,i.fee_account_id INTO row_data,invoice_account FROM invoices i JOIN bill_runs b ON b.school_id=i.school_id AND b.id=i.bill_run_id WHERE i.school_id=NEW.school_id AND i.id=NEW.invoice_id AND i.session_year_id=NEW.session_year_id;
                IF CAST(JSON_UNQUOTE(JSON_EXTRACT(row_data,'$.account_id')) AS UNSIGNED)=invoice_account AND JSON_UNQUOTE(JSON_EXTRACT(row_data,'$.description'))=NEW.description AND JSON_UNQUOTE(JSON_EXTRACT(row_data,'$.period_start'))=NEW.period_start AND JSON_UNQUOTE(JSON_EXTRACT(row_data,'$.period_end'))=NEW.period_end AND CAST(JSON_UNQUOTE(JSON_EXTRACT(row_data,'$.plan_id')) AS UNSIGNED)=NEW.fee_plan_id AND CAST(JSON_UNQUOTE(JSON_EXTRACT(row_data,'$.plan_revision')) AS UNSIGNED)=NEW.plan_revision THEN
                    SET parts=JSON_EXTRACT(row_data,'$.installment_lines');
                    WHILE p<COALESCE(JSON_LENGTH(parts),0) DO
                        SET part_data=JSON_EXTRACT(parts,CONCAT('$[',p,']'));
                        IF CAST(JSON_UNQUOTE(JSON_EXTRACT(part_data,'$.installment_number')) AS UNSIGNED)=NEW.installment_number AND JSON_UNQUOTE(JSON_EXTRACT(part_data,'$.due_on'))=NEW.installment_due_on AND CAST(JSON_UNQUOTE(JSON_EXTRACT(part_data,'$.amount_cents')) AS SIGNED)=NEW.amount_cents AND JSON_LENGTH(NEW.holder_shares)=JSON_LENGTH(JSON_EXTRACT(part_data,'$.holder_shares')) AND JSON_CONTAINS(NEW.holder_shares,JSON_EXTRACT(part_data,'$.holder_shares')) AND JSON_CONTAINS(JSON_EXTRACT(part_data,'$.holder_shares'),NEW.holder_shares) THEN SET matched=matched+1;END IF;
                        SET p=p+1;
                    END WHILE;
                END IF;
                IF matched<>1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Annual installment must match the reviewed billing snapshot';END IF;
                IF EXISTS(SELECT 1 FROM invoice_lines WHERE school_id=NEW.school_id AND session_year_id=NEW.session_year_id AND annual_learner_id=NEW.learner_id AND invoice_id<>NEW.invoice_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Annual learner tuition already belongs to another invoice';END IF;
            END IF;
        END");
        DB::unprepared("CREATE TRIGGER invoice_installment_complete BEFORE UPDATE ON invoices FOR EACH ROW BEGIN
            DECLARE kind VARCHAR(16);DECLARE budget JSON;DECLARE line_count BIGINT;
            IF NEW.sealed_at IS NOT NULL THEN
                SELECT b.kind,JSON_EXTRACT(b.snapshot,CONCAT('$.invoice_schedules.',CHAR(34),NEW.fee_account_id,CHAR(34))) INTO kind,budget FROM bill_runs b WHERE b.school_id=NEW.school_id AND b.id=NEW.bill_run_id;
                IF kind='annual' THEN
                    SELECT COUNT(*) INTO line_count FROM invoice_lines WHERE school_id=NEW.school_id AND invoice_id=NEW.id;
                    IF budget IS NULL OR line_count<>COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(budget,'$.line_count')) AS UNSIGNED),-1) OR NEW.total_cents<>COALESCE(CAST(JSON_UNQUOTE(JSON_EXTRACT(budget,'$.total_cents')) AS SIGNED),-1) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Seal every reviewed installment with its exact annual total';END IF;
                END IF;
            END IF;
        END");

    }

    public function down(): void
    {
        if (DB::table('fee_payment_choices')->exists() || DB::table('fee_plans')->whereNotNull('applies_to')->orWhereNotNull('payment_options')->exists() || DB::table('invoice_lines')->whereNotNull('installment_due_on')->exists()) {
            throw new RuntimeException('Retain expanded plan and installment history.');
        }
        DB::unprepared('DROP TRIGGER IF EXISTS invoice_installment_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS invoice_installment_complete');
        Schema::dropIfExists('fee_payment_choices');
        $maria = str_contains(strtolower((string) DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        DB::statement('ALTER TABLE invoice_lines DROP '.($maria ? 'CONSTRAINT' : 'CHECK').' invoice_installment_bounds');
        Schema::table('invoice_lines', function (Blueprint $t): void {
            $t->dropUnique('annual_tuition_charge_once');
            $t->dropColumn(['installment_number', 'installment_due_on']);
            $t->unique(['school_id', 'session_year_id', 'annual_learner_id'], 'annual_tuition_charge_once');
        });
        Schema::table('fee_plans', fn (Blueprint $t) => $t->dropColumn(['applies_to', 'payment_options', 'default_payment_option']));
    }
};
