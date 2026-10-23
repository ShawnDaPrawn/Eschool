<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->string('handle', 20)->nullable()->unique());
        Schema::create('handle_claims', function (Blueprint $table) {
            $table->string('handle', 20)->primary();
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('active')->default(false);
            $table->timestamp('reserved_until')->nullable();
            $table->timestamps();
        });
        Schema::create('handle_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('old_handle', 20)->nullable();
            $table->string('new_handle', 20);
            $table->foreignId('changed_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->date('term_ends_on');
            $table->timestamps();
        });
        Schema::create('handle_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('learner_id')->constrained('students');
            $table->string('handle', 20);
            $table->string('status', 16)->default('pending');
            $table->timestamp('expires_at');
            $table->foreignId('decided_by')->nullable()->constrained('users');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'learner_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('handle_proposals');
        Schema::dropIfExists('handle_changes');
        Schema::dropIfExists('handle_claims');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('handle'));
    }
};
