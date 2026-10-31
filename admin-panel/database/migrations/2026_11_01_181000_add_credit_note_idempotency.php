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
        Schema::table('platform_invoices', function (Blueprint $table) {
            $table->uuid('credit_request_key')->nullable();
            $table->char('credit_request_hash', 64)->nullable();
            $table->unique(['school_id', 'credit_request_key'], 'platform_credit_request_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('platform_invoices')->whereNotNull('credit_request_key')->exists()) {
            throw new RuntimeException('Retain credit-note replay protection by restoring a tested backup instead.');
        }
        Schema::table('platform_invoices', function (Blueprint $table) {
            $table->dropUnique('platform_credit_request_unique');
            $table->dropColumn(['credit_request_key', 'credit_request_hash']);
        });
    }
};
