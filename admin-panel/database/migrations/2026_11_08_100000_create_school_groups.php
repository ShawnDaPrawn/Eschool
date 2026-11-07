<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * School groups (Action Plan 5.7 and SA.18): one owner of many schools.
 * HQ creates a group, invites schools and names the group's administrators;
 * each school's Super Admin agrees (or declines) in the school's own panel,
 * and may leave at any time. Group administrators see counts and shares
 * across the group's current schools, never a learner's record.
 *
 * Group billing: one invoice per group and month that rolls up the member
 * schools' own monthly drafts (platform_invoices). A school's month is
 * billed alone or through the group, never both: the school's own draft is
 * claimed by the group draft (one standing claim per school and month), and
 * becomes "grouped" (frozen, never issued alone) when the group invoice is
 * issued. Integer cents throughout. Specification:
 * engineering/specs/platform/school-groups.md.
 */
return new class extends Migration
{
    private const TABLES = ['school_group_payments', 'school_group_invoice_lines', 'school_group_invoice_schools', 'school_group_invoices',
        'school_group_admins', 'school_group_members', 'school_groups'];

    /** school_group_invoices columns frozen once issued or discarded. */
    private const FROZEN = ['id', 'school_group_id', 'status', 'reference', 'slot', 'period_start', 'period_end', 'currency', 'vat_rate_bp',
        'schools', 'learners', 'subtotal_cents', 'vat_cents', 'total_cents', 'blocked_reason', 'issued_on', 'due_on', 'computed_at', 'computed_by',
        'issued_at', 'issued_by', 'discarded_at', 'discarded_by', 'created_at'];

    private const PAYMENT_FIXED = ['id', 'school_group_id', 'group_invoice_id', 'received_on', 'amount_cents', 'currency', 'bank_reference', 'note', 'recorded_by', 'created_at'];

    private const TRIGGERS = ['school_group_invoices_frozen', 'school_group_invoices_no_delete',
        'school_group_invoice_lines_insert', 'school_group_invoice_lines_update', 'school_group_invoice_lines_delete',
        'school_group_invoice_schools_insert', 'school_group_invoice_schools_update', 'school_group_invoice_schools_delete',
        'school_group_payments_update', 'school_group_payments_no_delete'];

    public function up(): void
    {
        Schema::create('school_groups', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120)->unique();
            $t->string('status', 12)->default('active');
            $t->string('notes', 500)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
        Schema::create('school_group_members', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('school_group_id')->constrained('school_groups')->restrictOnDelete();
            // invited, active, declined, withdrawn (by HQ before an answer), left (by the school), removed (by HQ).
            $t->string('status', 12);
            // 1 while invited or active, null after: one open membership per school, in any group.
            $t->unsignedTinyInteger('open_key')->nullable();
            // school (billed alone, the default) or group (rolled up on the group's invoice).
            $t->string('billing', 8)->default('school');
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('invited_at');
            $t->string('invitation_note', 500)->nullable();
            // The school's Super Admin who agreed or declined, and the wording they saw.
            $t->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('decided_at')->nullable();
            $t->string('agreement_version', 32)->nullable();
            $t->char('agreement_hash', 64)->nullable();
            $t->dateTime('ended_at')->nullable();
            $t->foreignId('ended_by')->nullable()->constrained('users')->nullOnDelete();
            // school or hq.
            $t->string('ended_side', 8)->nullable();
            $t->string('end_reason', 500)->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'open_key'], 'school_group_members_open_unique');
            $t->index(['school_group_id', 'status'], 'school_group_members_group_index');
        });
        Schema::create('school_group_admins', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_group_id')->constrained('school_groups')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $t->string('role', 16)->default('admin');
            // 1 while the grant stands, null once revoked: one standing grant per person and group.
            $t->unsignedTinyInteger('open_key')->nullable();
            $t->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('reason', 500);
            $t->dateTime('revoked_at')->nullable();
            $t->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('revoke_reason', 500)->nullable();
            $t->timestamps();
            $t->unique(['school_group_id', 'user_id', 'open_key'], 'school_group_admins_open_unique');
            $t->index(['user_id', 'open_key'], 'school_group_admins_user_index');
        });
        Schema::create('school_group_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_group_id')->constrained('school_groups')->restrictOnDelete();
            $t->string('status', 12)->default('draft');
            // 1 while a draft or issued, null once discarded: one standing invoice per group and month.
            $t->unsignedTinyInteger('open_key')->nullable();
            // From the same yearly sequence as school invoices (NE-2026-0001), so references never repeat.
            $t->string('reference', 24)->nullable()->unique();
            $t->string('slot', 7);
            $t->date('period_start');
            $t->date('period_end');
            $t->char('currency', 3)->default('ZAR');
            $t->unsignedSmallInteger('vat_rate_bp');
            $t->unsignedSmallInteger('schools')->default(0);
            $t->unsignedInteger('learners')->default(0);
            $t->bigInteger('subtotal_cents')->default(0);
            $t->bigInteger('vat_cents')->default(0);
            $t->bigInteger('total_cents')->default(0);
            $t->string('blocked_reason', 255)->nullable();
            $t->date('issued_on')->nullable();
            $t->date('due_on')->nullable();
            $t->dateTime('computed_at')->nullable();
            $t->foreignId('computed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('issued_at')->nullable();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('discarded_at')->nullable();
            $t->foreignId('discarded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_group_id', 'slot', 'open_key'], 'school_group_invoices_month_unique');
        });
        Schema::create('school_group_invoice_schools', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('group_invoice_id')->constrained('school_group_invoices')->restrictOnDelete();
            // The school's own monthly draft this group invoice rolls up.
            $t->foreignId('platform_invoice_id')->constrained('platform_invoices')->restrictOnDelete();
            $t->string('slot', 7);
            // 1 while the group invoice stands, null once it was discarded: one standing claim per school and month.
            $t->unsignedTinyInteger('open_key')->nullable();
            $t->unsignedInteger('learners')->default(0);
            $t->bigInteger('subtotal_cents');
            $t->bigInteger('vat_cents');
            $t->bigInteger('total_cents');
            // When the school's draft was computed as it was copied: issuing refuses a draft recomputed since.
            $t->dateTime('invoice_computed_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'slot', 'open_key'], 'school_group_invoice_schools_month_unique');
            $t->unique(['platform_invoice_id', 'open_key'], 'school_group_invoice_schools_invoice_unique');
        });
        Schema::create('school_group_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('group_invoice_id')->constrained('school_group_invoices')->restrictOnDelete();
            $t->foreignId('source_invoice_id')->constrained('platform_invoices')->restrictOnDelete();
            $t->unsignedSmallInteger('position');
            $t->string('kind', 12);
            $t->string('product_module', 32)->nullable();
            $t->string('description', 191);
            $t->unsignedInteger('learners')->nullable();
            $t->bigInteger('unit_cents')->nullable();
            $t->bigInteger('amount_cents');
            $t->bigInteger('vat_cents');
            $t->timestamps();
        });
        Schema::create('school_group_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_group_id')->constrained('school_groups')->restrictOnDelete();
            $t->foreignId('group_invoice_id')->constrained('school_group_invoices')->restrictOnDelete();
            $t->date('received_on');
            $t->unsignedBigInteger('amount_cents');
            $t->char('currency', 3)->default('ZAR');
            $t->string('bank_reference', 96);
            $t->string('note', 500)->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('reversed_at')->nullable();
            $t->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('reversal_reason', 500)->nullable();
            $t->timestamps();
            $t->index(['school_group_id', 'bank_reference'], 'school_group_payments_reference_index');
        });
        $this->createTriggers();
    }

    /** Below the models: an issued group invoice, its lines and claims never change, and payments are only reversed. */
    public function createTriggers(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        $same = fn (array $columns): string => implode(' AND ', array_map(fn (string $c): string => "NEW.`{$c}` <=> OLD.`{$c}`", $columns));
        DB::unprepared("CREATE TRIGGER school_group_invoices_frozen BEFORE UPDATE ON school_group_invoices FOR EACH ROW BEGIN
            IF OLD.status <> 'draft' AND NOT ({$same([...self::FROZEN, 'open_key'])}) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An issued or discarded group invoice never changes'; END IF; END");
        DB::unprepared("CREATE TRIGGER school_group_invoices_no_delete BEFORE DELETE ON school_group_invoices FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Group invoices are never deleted'");
        foreach (['school_group_invoice_lines' => 'lines', 'school_group_invoice_schools' => 'schools'] as $table => $what) {
            DB::unprepared("CREATE TRIGGER {$table}_insert BEFORE INSERT ON {$table} FOR EACH ROW BEGIN
                IF NOT EXISTS (SELECT 1 FROM school_group_invoices i WHERE i.id = NEW.group_invoice_id AND i.status = 'draft') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Group invoice {$what} are added only to a draft'; END IF; END");
            DB::unprepared("CREATE TRIGGER {$table}_update BEFORE UPDATE ON {$table} FOR EACH ROW BEGIN
                IF NOT EXISTS (SELECT 1 FROM school_group_invoices i WHERE i.id = OLD.group_invoice_id AND i.status = 'draft')
                    OR NOT EXISTS (SELECT 1 FROM school_group_invoices i WHERE i.id = NEW.group_invoice_id AND i.status = 'draft') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only a draft group invoice\\'s {$what} change'; END IF; END");
            DB::unprepared("CREATE TRIGGER {$table}_delete BEFORE DELETE ON {$table} FOR EACH ROW BEGIN
                IF NOT EXISTS (SELECT 1 FROM school_group_invoices i WHERE i.id = OLD.group_invoice_id AND i.status = 'draft') THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only a draft group invoice\\'s {$what} are removed'; END IF; END");
        }
        DB::unprepared("CREATE TRIGGER school_group_payments_update BEFORE UPDATE ON school_group_payments FOR EACH ROW BEGIN
            IF OLD.reversed_at IS NOT NULL OR NEW.reversed_at IS NULL OR NOT ({$same(self::PAYMENT_FIXED)}) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A group payment is only ever reversed, once'; END IF; END");
        DB::unprepared("CREATE TRIGGER school_group_payments_no_delete BEFORE DELETE ON school_group_payments FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Group payments are never deleted'");
    }

    public function down(): void
    {
        foreach (['school_group_invoices', 'school_group_payments', 'school_group_members'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain school groups, their agreements and their invoices.');
            }
        }
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            foreach (self::TRIGGERS as $trigger) {
                DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
