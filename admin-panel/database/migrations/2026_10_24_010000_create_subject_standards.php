<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_standards', function (Blueprint $table) {
            $table->id();
            $table->string('framework', 8);
            $table->string('code', 32);
            $table->string('name', 191);
            $table->string('phase', 16);
            $table->unsignedTinyInteger('grade_from');
            $table->unsignedTinyInteger('grade_to');
            $table->string('version', 40);
            $table->string('source_url', 2048);
            $table->date('effective_on');
            $table->json('component_weights')->nullable();
            $table->string('fingerprint', 64);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
            $table->unique(['framework', 'code', 'grade_from', 'grade_to', 'version'], 'subject_standard_revision');
        });
        Schema::table('subjects', fn (Blueprint $table) => $table->foreignId('standard_id')->nullable()->constrained('subject_standards')->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('subjects', fn (Blueprint $table) => $table->dropConstrainedForeignId('standard_id'));
        Schema::dropIfExists('subject_standards');
    }
};
