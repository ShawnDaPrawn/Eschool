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
        Schema::table('wellbeing_notice_intents', fn (Blueprint $t) => $t->timestamp('read_at')->nullable());
    }

    public function down(): void
    {
        if (DB::table('wellbeing_notice_intents')->whereNotNull('read_at')->exists()) {
            throw new RuntimeException('Retained private notice read receipts prevent destructive rollback.');
        }
        Schema::table('wellbeing_notice_intents', fn (Blueprint $t) => $t->dropColumn('read_at'));
    }
};
