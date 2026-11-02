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
        Schema::table('teaching_groups', function (Blueprint $t) {
            $t->unique(['school_id', 'id'], 'foundation_teaching_group_school_key');
        });
        Schema::create('foundation_classroom_groups', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('teaching_group_id');
            $t->boolean('eligible')->default(false);
            $t->unsignedInteger('revision')->default(1);
            $t->foreignId('reviewed_by')->constrained('users');
            $t->text('review_reason');
            $t->timestamps();
            $t->unique(['school_id', 'teaching_group_id'], 'foundation_group_once');
            $t->foreign(['school_id', 'teaching_group_id'], 'foundation_group_source')->references(['school_id', 'id'])->on('teaching_groups');
        });
        Schema::create('foundation_classroom_leases', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('school_id')->constrained('schools');
            $t->uuid('device_id');
            $t->foreignId('teacher_user_id')->constrained('users');
            $t->foreignId('teaching_group_id');
            $t->char('secret_hash', 64);
            $t->unsignedInteger('eligibility_revision');
            $t->dateTime('expires_at');
            $t->dateTime('revoked_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'id']);
            $t->foreign(['school_id', 'device_id'], 'foundation_lease_device')->references(['school_id', 'id'])->on('card_reader_devices');
            $t->foreign(['school_id', 'teaching_group_id'], 'foundation_lease_group')->references(['school_id', 'id'])->on('teaching_groups');
        });
        $this->scanNullability(true);
        Schema::table('card_classroom_sessions', function (Blueprint $t) {
            $t->uuid('classroom_lease_id')->nullable();
            $t->unsignedInteger('card_revision')->nullable();
            $t->char('approval_revision', 64)->nullable();
            $t->foreign(['school_id', 'classroom_lease_id'], 'foundation_session_lease')->references(['school_id', 'id'])->on('foundation_classroom_leases');
        });
    }

    public function down(): void
    {
        foreach (['foundation_classroom_groups', 'foundation_classroom_leases'] as $name) {
            if (Schema::hasTable($name) && DB::table($name)->exists()) {
                throw new RuntimeException('Foundation classroom eligibility and issued delegations must be retained; rollback refused.');
            }
        }
        if (DB::table('card_classroom_sessions')->whereNotNull('classroom_lease_id')->orWhereNull('scan_id')->exists()) {
            throw new RuntimeException('Issued classroom sessions must be retained; rollback refused.');
        }
        Schema::table('card_classroom_sessions', function (Blueprint $t) {
            $t->dropForeign('foundation_session_lease');
            $t->dropColumn(['classroom_lease_id', 'card_revision', 'approval_revision']);
        });
        $this->scanNullability(false);
        Schema::dropIfExists('foundation_classroom_leases');
        Schema::dropIfExists('foundation_classroom_groups');
        Schema::table('teaching_groups', function (Blueprint $t) {
            $t->dropUnique('foundation_teaching_group_school_key');
        });
    }

    /** Change the scan requirement atomically while retaining its school/source FK. */
    private function scanNullability(bool $nullable): void
    {
        $foreign = collect(Schema::getForeignKeys('card_classroom_sessions'))->first(fn (array $key): bool => $key['columns'] === ['school_id', 'scan_id'] && $key['foreign_table'] === 'card_scan_logs'
                && $key['foreign_columns'] === ['school_id', 'id']);
        if ($foreign === null) {
            throw new RuntimeException('The composite scan reference must exist before changing session nullability.');
        }
        $oldName = str_replace('`', '``', $foreign['name']);
        $newName = 'card_classroom_scan_'.($nullable ? 'optional' : 'required');
        if ($newName === $foreign['name']) {
            $newName .= '_restored';
        }
        $null = $nullable ? 'NULL' : 'NOT NULL';
        // A different FK name permits one atomic MySQL ALTER, with all foreign
        // key checks enabled and the same tenant/source and delete rules.
        DB::statement("ALTER TABLE card_classroom_sessions DROP FOREIGN KEY `$oldName`,
            MODIFY scan_id CHAR(36) $null,
            ADD CONSTRAINT `$newName` FOREIGN KEY (school_id, scan_id)
                REFERENCES card_scan_logs (school_id, id) ON DELETE NO ACTION");
    }
};
