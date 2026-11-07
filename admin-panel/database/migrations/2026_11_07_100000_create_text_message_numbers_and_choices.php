<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SMS and WhatsApp follow-ups (Action Plan 2.7): verified numbers, each
 * person's own SMS and WhatsApp choice, and the school's opt-in for staff.
 * No phone number and no code is stored: only SHA-256 hashes of the number
 * and a keyed hash of the code. engineering/specs/platform/sms-whatsapp.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messaging_numbers', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // parent: the number on the person's parent record at this school; account: the number on their own account (staff).
            $t->string('source', 8);
            // The number this row is about (a code waiting, or verified): SHA-256 of the E.164 number.
            $t->char('number_hash', 64)->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->char('code_hash', 64)->nullable();
            $t->timestamp('code_expires_at')->nullable();
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->timestamp('locked_until')->nullable();
            $t->unsignedSmallInteger('codes_sent')->default(0);
            $t->timestamp('codes_window_started_at')->nullable();
            $t->timestamp('last_code_sent_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'user_id'], 'messaging_numbers_person_unique');
            $t->index(['user_id', 'source'], 'messaging_numbers_user_source');
        });

        Schema::create('messaging_choices', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('type', 80);
            $t->boolean('sms');
            $t->boolean('whatsapp');
            $t->timestamps();
            $t->unique(['school_id', 'user_id', 'type'], 'messaging_choices_person_type_unique');
        });

        Schema::table('school_messaging_settings', function (Blueprint $t): void {
            $t->json('staff_sms_types')->nullable()->after('whatsapp_types');
            $t->json('staff_whatsapp_types')->nullable()->after('staff_sms_types');
        });
    }

    public function down(): void
    {
        foreach (['messaging_numbers', 'messaging_choices'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('People have verified numbers or SMS and WhatsApp choices: restore a tested backup instead of rolling back.');
            }
        }
        if (Schema::hasColumn('school_messaging_settings', 'staff_sms_types')
            && DB::table('school_messaging_settings')->where(fn ($q) => $q->whereNotNull('staff_sms_types')->orWhereNotNull('staff_whatsapp_types'))->exists()) {
            throw new RuntimeException('Schools texted staff: restore a tested backup instead of rolling back.');
        }
        Schema::table('school_messaging_settings', function (Blueprint $t): void {
            $t->dropColumn(['staff_sms_types', 'staff_whatsapp_types']);
        });
        Schema::dropIfExists('messaging_choices');
        Schema::dropIfExists('messaging_numbers');
    }
};
