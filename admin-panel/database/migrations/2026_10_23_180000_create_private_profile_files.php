<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('definition_id')->constrained('profile_field_definitions');
            $table->text('name');
            $table->string('path', 255);
            $table->string('mime', 80);
            $table->string('extension', 8);
            $table->unsignedInteger('size');
            $table->timestamps();
            $table->index(['school_id', 'user_id', 'definition_id']);
        });
    }

    public function down(): void
    {
        // Ciphertext objects remain private. Back up metadata/APP_KEY before rollback.
        Schema::dropIfExists('profile_files');
    }
};
