<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Navigate Ed's breach and incident register (Action Plan SA.12): each
 * security incident, the schools it affects and when each was told, the
 * Information Regulator decision, a timeline of actions, and closure. Written
 * and read only from /saas. Specification: engineering/specs/platform/hq-incidents.md.
 */
return new class extends Migration
{
    private const TABLES = ['platform_incident_entries', 'platform_incident_schools', 'platform_incidents'];

    public function up(): void
    {
        Schema::create('platform_incidents', function (Blueprint $t) {
            $t->id();
            $t->string('title', 191);
            $t->string('category', 24);
            $t->string('severity', 8);
            $t->string('status', 12)->default('open');
            // Whether personal information was, or may have been, reached by someone not authorised: yes, no or unknown.
            $t->string('personal_information', 8);
            $t->dateTime('detected_at');
            $t->dateTime('occurred_at')->nullable();
            $t->dateTime('contained_at')->nullable();
            $t->dateTime('closed_at')->nullable();
            $t->text('summary');
            // The Information Regulator decision: required or not_required; null until decided.
            $t->string('regulator', 12)->nullable();
            $t->string('regulator_reason', 500)->nullable();
            $t->date('regulator_notified_on')->nullable();
            $t->string('regulator_reference', 64)->nullable();
            $t->text('closure_summary')->nullable();
            $t->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['status', 'detected_at'], 'platform_incidents_status_index');
        });
        Schema::create('platform_incident_schools', function (Blueprint $t) {
            $t->id();
            $t->foreignId('incident_id')->constrained('platform_incidents')->restrictOnDelete();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->date('notified_on')->nullable();
            $t->string('notified_how', 16)->nullable();
            $t->string('notified_contact', 191)->nullable();
            $t->foreignId('notified_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['incident_id', 'school_id']);
        });
        Schema::create('platform_incident_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('incident_id')->constrained('platform_incidents')->restrictOnDelete();
            $t->string('kind', 16);
            $t->string('body', 1000);
            $t->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['incident_id', 'created_at'], 'platform_incident_entries_timeline_index');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain the incident register.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
