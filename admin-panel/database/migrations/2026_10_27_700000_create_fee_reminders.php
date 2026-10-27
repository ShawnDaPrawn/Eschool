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
        Schema::create('fee_reminder_policies', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('enabled')->default(false);
            $t->unsignedSmallInteger('min_days_overdue');
            $t->unsignedSmallInteger('cadence_days');
            $t->unsignedTinyInteger('local_hour');
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        Schema::create('fee_reminders', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->unsignedBigInteger('fee_account_id');
            $t->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('policy_revision');
            $t->date('issued_on');
            $t->foreignId('notification_id')->nullable()->constrained('notifications')->nullOnDelete();
            $t->dateTime('bound_at', 6)->nullable();
            $t->dateTime('expires_at', 6);
            $t->dateTime('created_at', 6);
            $t->unique(['school_id', 'fee_account_id', 'recipient_id', 'issued_on'], 'fee_reminder_daily_once');
            $t->unique(['school_id', 'notification_id'], 'fee_reminder_notice_once');
            $t->index(['school_id', 'recipient_id', 'expires_at'], 'fee_reminder_reader');
            $t->foreign(['school_id', 'fee_account_id'])->references(['school_id', 'id'])->on('fee_accounts')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE fee_reminder_policies ADD CONSTRAINT fee_reminder_policy_rules CHECK (revision > 0 AND min_days_overdue BETWEEN 1 AND 365 AND cadence_days BETWEEN 1 AND 365 AND local_hour BETWEEN 0 AND 23)');
        DB::statement('ALTER TABLE fee_reminders ADD CONSTRAINT fee_reminder_revision CHECK (policy_revision > 0 AND expires_at > created_at)');
        $identity = implode(' OR ', array_map(fn ($c) => "NOT (NEW.$c <=> OLD.$c)", ['id', 'school_id', 'fee_account_id', 'recipient_id', 'policy_revision', 'issued_on', 'expires_at', 'created_at']));
        DB::unprepared("CREATE TRIGGER fee_reminder_link BEFORE UPDATE ON fee_reminders FOR EACH ROW BEGIN
            DECLARE notice_kind VARCHAR(80);
            IF OLD.bound_at IS NOT NULL OR NEW.bound_at IS NULL OR NEW.notification_id IS NULL OR $identity THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fee reminder intent is immutable'; END IF;
            SELECT type INTO notice_kind FROM notifications WHERE school_id = NEW.school_id AND id = NEW.notification_id;
            IF notice_kind IS NULL OR notice_kind <> 'fees.ledger_reminder' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reminder needs its private fee notice'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_reminder_insert BEFORE INSERT ON fee_reminders FOR EACH ROW BEGIN
            IF NEW.notification_id IS NOT NULL OR NEW.bound_at IS NOT NULL THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Create the reminder before binding its notice'; END IF; END");
        DB::unprepared("CREATE TRIGGER fee_reminder_no_delete BEFORE DELETE ON fee_reminders FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fee reminder history is retained'");
    }

    public function down(): void
    {
        if (DB::table('fee_reminders')->exists() || DB::table('fee_reminder_policies')->exists()) {
            throw new RuntimeException('Retain fee reminder history and school policy.');
        }
        Schema::dropIfExists('fee_reminders');
        Schema::dropIfExists('fee_reminder_policies');
    }
};
