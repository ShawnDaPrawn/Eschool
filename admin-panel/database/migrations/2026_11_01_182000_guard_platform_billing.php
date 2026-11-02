<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * HQ billing in the database itself (hq-billing.md): an issued or discarded
 * invoice and its lines never change and nothing is deleted, whatever writes
 * (a query-builder update or raw SQL, not only the models). Only the reminder
 * columns of an issued invoice may change, and only a payment's reversal.
 * Also the reminder log, which makes a repeated reminder request send nothing
 * twice.
 */
return new class extends Migration
{
    /** platform_invoices columns frozen once issued (everything but the reminder columns and updated_at). */
    private const FROZEN = ['id', 'school_id', 'kind', 'status', 'reference', 'period_start', 'period_end', 'slot', 'credits_invoice_id',
        'package_key', 'package_version', 'price_source', 'contract_id', 'contracted_learners', 'learners', 'learners_counted_on', 'currency',
        'vat_rate_bp', 'subtotal_cents', 'vat_cents', 'total_cents', 'blocked_reason', 'reason', 'issued_on', 'due_on', 'computed_at',
        'computed_by', 'issued_at', 'issued_by', 'discarded_at', 'discarded_by', 'created_at', 'credit_request_key', 'credit_request_hash'];

    private const PAYMENT_FIXED = ['id', 'school_id', 'received_on', 'amount_cents', 'currency', 'bank_reference', 'note', 'recorded_by', 'created_at'];

    private const ALLOCATION_FIXED = ['id', 'school_id', 'invoice_id', 'payment_id', 'credit_note_id', 'amount_cents', 'created_by', 'created_at'];

    private const TRIGGERS = [
        'platform_invoices_frozen', 'platform_invoices_no_delete',
        'platform_invoice_lines_insert', 'platform_invoice_lines_update', 'platform_invoice_lines_delete',
        'platform_payments_update', 'platform_payments_no_delete',
        'platform_payment_allocations_update', 'platform_payment_allocations_no_delete',
    ];

    public function up(): void
    {
        Schema::create('platform_billing_reminders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            // The form's key: a repeated request returns the first reminder and sends nothing again.
            $t->uuid('request_key');
            $t->json('invoice_ids');
            $t->bigInteger('overdue_cents');
            $t->unsignedInteger('super_admins');
            $t->json('bursar_contact_ids');
            $t->string('email_title', 191);
            $t->text('email_body');
            // Set when the bursar emails were queued, after the reminder committed.
            $t->dateTime('mailed_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'request_key'], 'platform_billing_reminders_request_unique');
        });
        $this->createTriggers();
    }

    /** Public so a test that commits its own fixtures can remove them (drop, delete, create). */
    public function createTriggers(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        $same = fn (array $columns): string => implode(' AND ', array_map(fn (string $c): string => "NEW.`{$c}` <=> OLD.`{$c}`", $columns));
        DB::unprepared("CREATE TRIGGER platform_invoices_frozen BEFORE UPDATE ON platform_invoices FOR EACH ROW BEGIN
            IF OLD.status <> 'draft' AND NOT ({$same(self::FROZEN)}) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An issued or discarded platform invoice never changes'; END IF; END");
        DB::unprepared("CREATE TRIGGER platform_invoices_no_delete BEFORE DELETE ON platform_invoices FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Platform invoices are never deleted'");
        DB::unprepared("CREATE TRIGGER platform_invoice_lines_insert BEFORE INSERT ON platform_invoice_lines FOR EACH ROW BEGIN
            IF NOT EXISTS (SELECT 1 FROM platform_invoices i WHERE i.id = NEW.invoice_id AND i.status = 'draft' AND i.school_id = NEW.school_id) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lines are added only to a draft invoice of the same school'; END IF; END");
        DB::unprepared("CREATE TRIGGER platform_invoice_lines_update BEFORE UPDATE ON platform_invoice_lines FOR EACH ROW BEGIN
            IF NOT EXISTS (SELECT 1 FROM platform_invoices i WHERE i.id = OLD.invoice_id AND i.status = 'draft')
                OR NOT EXISTS (SELECT 1 FROM platform_invoices i WHERE i.id = NEW.invoice_id AND i.status = 'draft' AND i.school_id = NEW.school_id) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only a draft invoice\\'s lines change'; END IF; END");
        DB::unprepared("CREATE TRIGGER platform_invoice_lines_delete BEFORE DELETE ON platform_invoice_lines FOR EACH ROW BEGIN
            IF NOT EXISTS (SELECT 1 FROM platform_invoices i WHERE i.id = OLD.invoice_id AND i.status = 'draft') THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Only a draft invoice\\'s lines are removed'; END IF; END");
        DB::unprepared("CREATE TRIGGER platform_payments_update BEFORE UPDATE ON platform_payments FOR EACH ROW BEGIN
            IF OLD.reversed_at IS NOT NULL OR NEW.reversed_at IS NULL OR NOT ({$same(self::PAYMENT_FIXED)}) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'A platform payment is only ever reversed, once'; END IF; END");
        DB::unprepared("CREATE TRIGGER platform_payments_no_delete BEFORE DELETE ON platform_payments FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Platform payments are never deleted'");
        DB::unprepared("CREATE TRIGGER platform_payment_allocations_update BEFORE UPDATE ON platform_payment_allocations FOR EACH ROW BEGIN
            IF OLD.reversed_at IS NOT NULL OR NEW.reversed_at IS NULL OR NOT ({$same(self::ALLOCATION_FIXED)}) THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'An allocation is only ever reversed, once'; END IF; END");
        DB::unprepared("CREATE TRIGGER platform_payment_allocations_no_delete BEFORE DELETE ON platform_payment_allocations FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Platform payment allocations are never deleted'");
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_billing_reminders') && DB::table('platform_billing_reminders')->exists()) {
            throw new RuntimeException('Restore a tested backup to retain the billing reminder log.');
        }
        $this->dropTriggers();
        Schema::dropIfExists('platform_billing_reminders');
    }

    public function dropTriggers(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        foreach (self::TRIGGERS as $trigger) {
            DB::unprepared('DROP TRIGGER IF EXISTS '.$trigger);
        }
    }
};
