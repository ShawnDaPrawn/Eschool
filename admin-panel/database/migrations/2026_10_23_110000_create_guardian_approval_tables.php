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
        Schema::create('guardian_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->unsignedBigInteger('learner_id');
            $table->foreignId('guardian_user_id')->constrained('users');
            $table->string('relationship', 32);
            $table->boolean('can_see_fees')->default(true);
            $table->boolean('can_approve')->default(true);
            $table->string('status', 16)->default('active');
            $table->timestamps();
            $table->unique(['school_id', 'learner_id', 'guardian_user_id'], 'guardian_link_unique');
        });
        Schema::create('learner_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->unsignedBigInteger('learner_id');
            $table->foreignId('guardian_user_id')->constrained('users');
            $table->string('consent_version', 32);
            $table->timestamp('consented_at');
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'learner_id', 'withdrawn_at'], 'learner_approval_state');
        });
        Schema::create('guardian_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('guardian_user_id')->constrained('users');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->string('token_hash', 64)->unique();
            $table->text('encrypted_token');
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('reminded_day_three_at')->nullable();
            $table->timestamp('reminded_day_seven_at')->nullable();
            $table->timestamps();
        });

        // Relationships are migration evidence, never evidence of consent.
        DB::table('students')->orderBy('id')->chunkById(200, function ($learners) {
            foreach ($learners as $learner) {
                foreach (['father_id' => 'father', 'mother_id' => 'mother', 'guardian_id' => 'guardian'] as $column => $relationship) {
                    if (! $learner->{$column}) {
                        continue;
                    }
                    $guardianId = DB::table('parents')->where('id', $learner->{$column})->where('school_id', $learner->school_id)->value('user_id');
                    if ($guardianId !== null) {
                        DB::table('guardian_links')->insertOrIgnore([
                            'school_id' => $learner->school_id, 'learner_id' => $learner->id,
                            'guardian_user_id' => $guardianId, 'relationship' => $relationship,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_invitations');
        Schema::dropIfExists('learner_approvals');
        Schema::dropIfExists('guardian_links');
    }
};
