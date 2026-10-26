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
        Schema::create('activity_material_files', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('activity_group_id')->constrained('activity_groups');
            $t->foreignId('material_id')->constrained('activity_materials');
            $t->foreignId('uploaded_by')->constrained('users');
            $t->text('name');
            $t->string('mime', 80);
            $t->unsignedInteger('bytes');
            $t->string('path', 255);
            $t->string('sha256', 64);
            $t->string('scanner', 120);
            $t->dateTime('scanned_at');
            $t->dateTime('removed_at')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'material_id', 'removed_at'], 'activity_material_file_lookup');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('activity_material_files') && DB::table('activity_material_files')->exists()) {
            throw new RuntimeException('Retained activity attachments require an explicit export and retention decision before rollback.');
        }
        Schema::dropIfExists('activity_material_files');
    }
};
