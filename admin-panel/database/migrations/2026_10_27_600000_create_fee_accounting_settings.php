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
        Schema::create('fee_accounting_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->json('mappings');
            $t->char('xero_currency', 3)->nullable();
            $t->char('quickbooks_currency', 3)->nullable();
            $t->string('xero_date_format', 8);
            $t->string('quickbooks_date_format', 8);
            $t->string('xero_tax_label', 80);
            $t->unsignedInteger('revision');
            $t->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $t->timestamps();
        });
        DB::statement("ALTER TABLE fee_accounting_settings ADD CONSTRAINT fee_accounting_settings_rules CHECK (revision > 0 AND (xero_currency IS NULL OR xero_currency REGEXP '^[A-Z]{3}$') AND (quickbooks_currency IS NULL OR quickbooks_currency REGEXP '^[A-Z]{3}$') AND xero_date_format IN ('d/m/Y','m/d/Y','Y-m-d') AND quickbooks_date_format IN ('d/m/Y','m/d/Y','Y-m-d'))");
    }

    public function down(): void
    {
        if (DB::table('fee_accounting_settings')->exists()) {
            throw new RuntimeException('Retain or explicitly retire the school accounting configuration.');
        }
        Schema::dropIfExists('fee_accounting_settings');
    }
};
