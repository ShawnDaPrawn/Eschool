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
        Schema::table('award_windows', function (Blueprint $table): void {
            $table->foreignId('activity_group_id')->nullable()->constrained('activity_groups')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('award_windows')->whereNotNull('activity_group_id')->exists()) {
            throw new LogicException('Retain the yearly award group mapping.');
        }
        Schema::table('award_windows', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('activity_group_id');
        });
    }
};
