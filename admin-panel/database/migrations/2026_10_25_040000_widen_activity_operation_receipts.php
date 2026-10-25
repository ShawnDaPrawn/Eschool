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
        Schema::table('activity_operations', function (Blueprint $table) {
            $table->longText('receipt')->change();
        });
    }

    public function down(): void
    {
        // Never truncate encrypted receipts: that loses both retry safety and
        // the ability to decrypt otherwise committed attendance history.
        if (DB::table('activity_operations')->whereRaw('OCTET_LENGTH(receipt) > 65535')->exists()) {
            throw new RuntimeException('Retained activity receipts exceed TEXT capacity; keep LONGTEXT or archive through an approved retention process.');
        }
        Schema::table('activity_operations', function (Blueprint $table) {
            $table->text('receipt')->change();
        });
    }
};
