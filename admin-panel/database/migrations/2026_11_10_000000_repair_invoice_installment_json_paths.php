<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Apply the same MySQL JSON-path compatibility repair to existing installations. */
return new class extends Migration
{
    public function up(): void
    {
        $billing = require __DIR__.'/2026_10_27_800000_expand_fee_plan_rules_and_schedules.php';
        DB::unprepared('DROP TRIGGER IF EXISTS invoice_installment_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS invoice_installment_complete');
        $billing->createInstallmentTriggers();
    }

    public function down(): void
    {
        // Only the path encoding changed. Retain the identical billing guards;
        // the original installment migration removes them on a full rollback.
    }
};
