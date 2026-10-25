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
        Schema::create('gallery_settings', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->boolean('guardian_required')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('gallery_photos', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->uuid('public_id')->unique();
            $t->foreignId('session_year_id')->constrained('session_years')->restrictOnDelete();
            $t->foreignId('activity_group_id')->nullable()->constrained('activity_groups')->restrictOnDelete();
            $t->foreignId('owner_learner_id')->nullable()->constrained('students')->restrictOnDelete();
            $t->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $t->string('scope', 12);
            $t->text('caption')->nullable();
            $t->date('captured_on');
            $t->string('year_label', 160);
            $t->string('path', 255);
            $t->string('thumbnail_path', 255);
            $t->unsignedInteger('bytes');
            $t->unsignedInteger('thumbnail_bytes');
            $t->char('sha256', 64);
            $t->char('thumbnail_sha256', 64);
            $t->unsignedSmallInteger('width');
            $t->unsignedSmallInteger('height');
            $t->string('scanner', 120);
            $t->dateTime('scanned_at');
            $t->dateTime('erased_at')->nullable();
            $t->boolean('active')->default(true);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['school_id', 'activity_group_id', 'active', 'id'], 'gallery_group_photos');
        });
        Schema::create('photo_tags', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('gallery_photo_id')->constrained('gallery_photos')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->foreignId('tagged_by')->constrained('users')->restrictOnDelete();
            $t->string('status', 20);
            $t->string('approval_kind', 12)->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->dateTime('approved_at')->nullable();
            $t->boolean('retained_on_transfer')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->unique(['gallery_photo_id', 'learner_id']);
            $t->index(['school_id', 'learner_id', 'status', 'id'], 'gallery_owned_tags');
        });
        Schema::create('gallery_events', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('gallery_photo_id')->nullable()->constrained('gallery_photos')->restrictOnDelete();
            $t->foreignId('photo_tag_id')->nullable()->constrained('photo_tags')->restrictOnDelete();
            $t->foreignId('actor_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('kind', 64);
            $t->unsignedInteger('revision');
            $t->timestamps();
        });
        Schema::create('gallery_operations', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('operation_id');
            $t->char('request_hash', 64);
            $t->longText('receipt');
            $t->timestamps();
            $t->unique(['school_id', 'actor_id', 'operation_id']);
        });
        Schema::create('gallery_transfer_choices', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('transfer_consent_id')->unique()->constrained('transfer_consents')->restrictOnDelete();
            $t->foreignId('guardian_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('learner_id')->constrained('students')->restrictOnDelete();
            $t->char('fingerprint', 64);
            $t->longText('selection');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['gallery_photos', 'photo_tags', 'gallery_events', 'gallery_operations', 'gallery_transfer_choices'] as $table) {
            if (DB::table($table)->exists()) {
                throw new LogicException('Retain private gallery records.');
            }
        }
        foreach (['gallery_transfer_choices', 'gallery_operations', 'gallery_events', 'photo_tags', 'gallery_photos', 'gallery_settings'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
