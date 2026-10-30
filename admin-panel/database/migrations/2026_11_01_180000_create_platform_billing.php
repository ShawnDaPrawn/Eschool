<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Navigate Ed billing its schools (Action Plan 1.17 and SA.8, Build 1):
 * monthly invoices and credit notes with their lines, EFT payments and how
 * each payment or credit note settles invoices. All amounts are integer
 * cents. Written and read only from /saas; no school reads these tables.
 * Specification: engineering/specs/platform/hq-billing.md.
 */
return new class extends Migration
{
    private const TABLES = ['platform_payment_allocations', 'platform_payments', 'platform_invoice_lines', 'platform_invoices'];

    public function up(): void
    {
        Schema::create('platform_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            // invoice or credit_note; draft, issued or discarded (a draft HQ decided not to bill).
            $t->string('kind', 12)->default('invoice');
            $t->string('status', 12)->default('draft');
            // Given when issued, from one sequence per year under a row lock: NE-2026-0001.
            $t->string('reference', 24)->nullable()->unique();
            $t->date('period_start');
            $t->date('period_end');
            // "2026-11" for a monthly invoice, so a run for the month never makes a second one; null on credit notes.
            $t->string('slot', 7)->nullable();
            $t->foreignId('credits_invoice_id')->nullable()->constrained('platform_invoices')->restrictOnDelete();
            $t->string('package_key', 32)->nullable();
            $t->unsignedInteger('package_version')->nullable();
            // contract or list: which price the base line used.
            $t->string('price_source', 8)->nullable();
            $t->foreignId('contract_id')->nullable()->constrained('school_contracts')->restrictOnDelete();
            $t->unsignedInteger('contracted_learners')->nullable();
            $t->unsignedInteger('learners')->default(0);
            $t->date('learners_counted_on')->nullable();
            $t->char('currency', 3)->default('ZAR');
            // VAT rate in basis points (1500 is 15 %), kept with the invoice.
            $t->unsignedSmallInteger('vat_rate_bp');
            $t->bigInteger('subtotal_cents')->default(0);
            $t->bigInteger('vat_cents')->default(0);
            $t->bigInteger('total_cents')->default(0);
            // Why a draft cannot be issued yet (no price for the school), or empty.
            $t->string('blocked_reason', 255)->nullable();
            // A credit note's reason.
            $t->string('reason', 500)->nullable();
            $t->date('issued_on')->nullable();
            $t->date('due_on')->nullable();
            $t->dateTime('computed_at')->nullable();
            $t->foreignId('computed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('issued_at')->nullable();
            $t->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('discarded_at')->nullable();
            $t->foreignId('discarded_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('reminded_at')->nullable();
            $t->unsignedSmallInteger('reminders')->default(0);
            $t->timestamps();
            $t->unique(['school_id', 'slot'], 'platform_invoices_month_unique');
            $t->index(['status', 'kind', 'due_on'], 'platform_invoices_due_index');
            $t->index(['period_start', 'status'], 'platform_invoices_period_index');
        });
        Schema::create('platform_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('invoice_id')->constrained('platform_invoices')->restrictOnDelete();
            $t->unsignedSmallInteger('position');
            // base, module, discount or credit.
            $t->string('kind', 12);
            $t->string('product_module', 32)->nullable();
            $t->string('description', 191);
            $t->unsignedInteger('learners')->nullable();
            $t->bigInteger('unit_cents')->nullable();
            // Excluding VAT; the line's VAT is rounded on the line (half up), then the invoice adds the lines.
            $t->bigInteger('amount_cents');
            $t->bigInteger('vat_cents');
            $t->timestamps();
        });
        Schema::create('platform_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->date('received_on');
            $t->unsignedBigInteger('amount_cents');
            $t->char('currency', 3)->default('ZAR');
            $t->string('bank_reference', 96);
            $t->string('note', 500)->nullable();
            $t->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            // Never deleted: a mistaken payment is reversed with a reason.
            $t->dateTime('reversed_at')->nullable();
            $t->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('reversal_reason', 500)->nullable();
            $t->timestamps();
            $t->index(['school_id', 'bank_reference'], 'platform_payments_reference_index');
        });
        Schema::create('platform_payment_allocations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('invoice_id')->constrained('platform_invoices')->restrictOnDelete();
            // What settles it: a payment, or a credit note's credit.
            $t->foreignId('payment_id')->nullable()->constrained('platform_payments')->restrictOnDelete();
            $t->foreignId('credit_note_id')->nullable()->constrained('platform_invoices')->restrictOnDelete();
            $t->unsignedBigInteger('amount_cents');
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('reversed_at')->nullable();
            $t->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain Navigate Ed\'s invoices and payments.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
