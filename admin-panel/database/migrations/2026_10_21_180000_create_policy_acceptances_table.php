<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('document', 32);
            $table->char('version', 64);
            $table->timestamp('accepted_at');
            $table->unique(['user_id', 'document']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_acceptances');
    }
};
