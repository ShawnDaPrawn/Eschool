<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->string('student_number', 10)->unique();
            $table->date('first_enrolled_on');
            $table->timestamps();
        });
        Schema::create('platform_counters', function (Blueprint $table) {
            $table->string('name', 64)->primary();
            $table->unsignedBigInteger('value')->default(0);
        });
        Schema::table('students', fn (Blueprint $table) => $table->string('student_number', 10)->nullable()->index());
        Schema::create('enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('learner_id')->constrained('students');
            $table->foreignId('session_year_id')->constrained('session_years');
            $table->foreignId('class_section_id')->constrained('class_sections');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->string('reason', 255);
            $table->foreignId('previous_enrolment_id')->nullable()->constrained('enrolments');
            $table->boolean('legacy_dates')->default(false);
            $table->timestamps();
            $table->index(['school_id', 'learner_id', 'starts_on', 'ends_on'], 'enrolment_dates');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrolments');
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('student_number'));
        Schema::dropIfExists('platform_counters');
        Schema::dropIfExists('learner_identifiers');
    }
};
