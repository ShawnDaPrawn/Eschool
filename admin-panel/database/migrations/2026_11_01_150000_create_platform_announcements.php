<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Navigate Ed's announcements to schools (Action Plan SA.13): release notes,
 * maintenance windows and policy changes, written at /saas and delivered to
 * each targeted school's Super Admins as a notice and a panel banner, with a
 * read per Super Admin. These are not the school's own `announcements`.
 * Specification: engineering/specs/platform/hq-announcements.md.
 */
return new class extends Migration
{
    private const TABLES = ['platform_announcement_reads', 'platform_announcement_schools', 'platform_announcements'];

    public function up(): void
    {
        Schema::create('platform_announcements', function (Blueprint $t) {
            $t->id();
            // release_notes, maintenance or policy.
            $t->string('kind', 16);
            $t->string('severity', 8);
            $t->string('title', 150);
            // Plain text with a few markdown marks; escaped when shown.
            $t->text('body');
            $t->dateTime('shows_from');
            $t->dateTime('shows_until')->nullable();
            // A maintenance window's own start and end, when it has one.
            $t->dateTime('window_starts_at')->nullable();
            $t->dateTime('window_ends_at')->nullable();
            // all, packages, stages or schools; the chosen package keys, stages or school ids in audience_values.
            $t->string('audience', 12);
            $t->json('audience_values')->nullable();
            // draft, published or withdrawn. Published text is never changed.
            $t->string('status', 12)->default('draft');
            $t->dateTime('published_at')->nullable();
            $t->dateTime('withdrawn_at')->nullable();
            $t->string('withdrawn_reason', 500)->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('withdrawn_by')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('revision')->default(1);
            $t->timestamps();
            $t->index(['status', 'shows_from'], 'platform_announcements_status_index');
        });
        // The schools an announcement reached, fixed when it was published.
        Schema::create('platform_announcement_schools', function (Blueprint $t) {
            $t->id();
            $t->foreignId('announcement_id')->constrained('platform_announcements')->restrictOnDelete();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            // How many Super Admins the notice went to at publication.
            $t->unsignedInteger('recipients')->default(0);
            $t->timestamps();
            $t->unique(['announcement_id', 'school_id']);
        });
        // One row per Super Admin who dismissed the banner.
        Schema::create('platform_announcement_reads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('announcement_id')->constrained('platform_announcements')->restrictOnDelete();
            $t->foreignId('school_id')->index()->constrained('schools')->restrictOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->dateTime('read_at');
            $t->unique(['announcement_id', 'school_id', 'user_id'], 'platform_announcement_reads_unique');
        });
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && DB::table($table)->exists()) {
                throw new RuntimeException('Restore a tested backup to retain the announcements sent to schools.');
            }
        }
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
    }
};
