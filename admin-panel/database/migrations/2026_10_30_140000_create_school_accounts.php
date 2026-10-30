<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Navigate Ed's own record of each customer school (HQ): its profile and
 * lifecycle stage, its contacts, its contracts and operator agreement, and a
 * timeline. Written only from /saas; schools never read these tables.
 * Specification: engineering/specs/platform/hq-school-accounts.md.
 */
return new class extends Migration
{
    private const TABLES = ['school_account_events', 'school_contracts', 'school_contacts', 'school_accounts'];

    public function up(): void
    {
        Schema::create('school_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->unique()->constrained('schools')->restrictOnDelete();
            $t->string('stage', 16);
            $t->string('school_type', 16)->nullable();
            $t->string('phase', 32)->nullable();
            $t->string('curriculum', 16)->nullable();
            $t->string('province', 32)->nullable();
            $t->string('district', 100)->nullable();
            $t->string('emis_number', 32)->nullable();
            $t->string('address', 500)->nullable();
            $t->unsignedInteger('expected_learners')->nullable();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
        });
        Schema::create('school_contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->string('role', 32);
            $t->string('name', 191);
            $t->string('email', 191)->nullable();
            $t->string('phone', 32)->nullable();
            $t->timestamps();
        });
        Schema::create('school_contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->date('starts_on');
            $t->date('ends_on')->nullable();
            $t->date('renewal_on')->nullable();
            $t->unsignedInteger('contracted_learners');
            // Monthly price per learner before VAT, in cents.
            $t->unsignedInteger('price_per_learner_cents');
            $t->string('signatory', 191);
            $t->string('operator_agreement_version', 32);
            $t->date('operator_agreement_signed_on');
            $t->date('data_processing_signed_on')->nullable();
            $t->string('notes', 500)->nullable();
            $t->timestamps();
        });
        Schema::create('school_account_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools')->restrictOnDelete();
            $t->string('kind', 16);
            $t->string('body', 1000);
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['school_id', 'created_at'], 'school_account_events_timeline_index');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain school account history.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
