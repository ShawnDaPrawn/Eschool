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
        Schema::table('user_notifications', fn (Blueprint $table) => $table->dateTime('private_read_at')->nullable());
    }

    public function down(): void
    {
        if (DB::table('user_notifications')->whereNotNull('private_read_at')->exists()) {
            throw new LogicException('Private notice read history must be retained before rollback.');
        }
        Schema::table('user_notifications', fn (Blueprint $table) => $table->dropColumn('private_read_at'));
    }
};
