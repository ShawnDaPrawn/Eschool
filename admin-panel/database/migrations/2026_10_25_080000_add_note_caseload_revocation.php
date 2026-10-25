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
        Schema::table('learner_note_assignments', fn (Blueprint $t) => $t->timestamp('revoked_at')->nullable());
    }

    public function down(): void
    {
        if (DB::table('learner_note_assignments')->whereNotNull('revoked_at')->exists()) {
            throw new RuntimeException('Retained note access revocations block destructive rollback.');
        }
        Schema::table('learner_note_assignments', fn (Blueprint $t) => $t->dropColumn('revoked_at'));
    }
};
