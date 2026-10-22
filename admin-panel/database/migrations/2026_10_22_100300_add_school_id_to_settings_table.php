<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * settings.school_id (Build Bible step 6): null for platform keys, the
 * school's id for school keys once Phase 1 adds schools, so settings can be
 * scoped without another migration. Every existing row stays null, and
 * getSettings() reads them as before.
 *
 * The table has no unique index on the setting name to widen (type is TEXT
 * and installs may hold duplicates); the new lookup index covers school and
 * name together, with the 191-character prefix MySQL needs for TEXT.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('id');
        });
        DB::statement('CREATE INDEX settings_school_id_type_index ON settings (school_id, type(191))');
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropIndex('settings_school_id_type_index');
            $table->dropColumn('school_id');
        });
    }
};
