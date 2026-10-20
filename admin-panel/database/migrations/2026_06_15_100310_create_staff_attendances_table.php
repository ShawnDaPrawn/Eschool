<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('session_year_id')
                ->constrained('session_years')
                ->cascadeOnDelete();

            $table->date('date');

            $table->enum('type', [
                'present',
                'absent',
                'first_half',
                'second_half',
                'holiday',
                'leave',
            ])->default('present');

            $table->text('remark')->nullable();

            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};
