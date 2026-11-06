<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Action Plan 5.9 (part 1): OneRoster 1.2 CSV exports a school starts from its
 * panel, and the Open Badges 3.0 credentials issued for recognition awards.
 * engineering/specs/platform/open-standards.md.
 */
return new class extends Migration
{
    private const PERMISSION = 'oneroster-export';

    public function up(): void
    {
        Schema::create('oneroster_exports', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('requested_by')->constrained('users');
            $t->foreignId('session_year_id')->constrained('session_years');
            // Why the school is sharing its roster (encrypted): it is audited and shown to the school.
            $t->longText('reason');
            $t->boolean('include_demographics')->default(false);
            $t->string('state', 16)->default('preparing');
            $t->uuid('generation_id');
            $t->string('artifact_path', 191)->nullable();
            $t->char('artifact_sha256', 64)->nullable();
            $t->char('cipher_sha256', 64)->nullable();
            $t->unsignedBigInteger('artifact_bytes')->nullable();
            $t->text('row_counts')->nullable();
            $t->string('failure_code', 32)->nullable();
            $t->timestamp('ready_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->unsignedInteger('download_count')->default(0);
            $t->timestamp('last_downloaded_at')->nullable();
            // When the stored file was deleted at the end of its retention; the row and its audit stay.
            $t->timestamp('purged_at')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'state'], 'oneroster_exports_state');
        });
        Schema::create('open_badge_credentials', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('award_presentation_id')->constrained('award_presentations');
            $t->foreignId('learner_id')->constrained('students');
            // The credential's own id (urn:uuid:...), the one public lookup key.
            $t->uuid('credential_id')->unique('open_badge_credential_identity');
            $t->char('key_id', 64);
            // Salts the hashed recipient name in the credential, so the document carries no plain name.
            $t->char('recipient_salt', 32);
            // The signed credential exactly as issued (encrypted JSON): every download returns these bytes.
            $t->longText('document');
            $t->timestamp('issued_at');
            $t->timestamp('revoked_at')->nullable();
            $t->foreignId('revoked_by')->nullable()->constrained('users');
            $t->string('revocation_code', 32)->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'award_presentation_id'], 'open_badge_credential_once');
            $t->index(['school_id', 'learner_id'], 'open_badge_credentials_learner');
        });

        DB::table('permissions')->insertOrIgnore(['name' => self::PERMISSION, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $permission = DB::table('permissions')->where('name', self::PERMISSION)->where('guard_name', 'web')->value('id');
        foreach (DB::table('roles')->where('name', 'Super Admin')->where(fn ($q) => $q->whereNull('custom_role')->orWhere('custom_role', 0))->pluck('id') as $role) {
            DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // An issued credential is verified against its row: keep it.
        if (DB::table('open_badge_credentials')->exists() || DB::table('oneroster_exports')->exists()) {
            throw new RuntimeException('Retained open standards exports or issued badge credentials prevent rollback.');
        }
        Schema::dropIfExists('open_badge_credentials');
        Schema::dropIfExists('oneroster_exports');
        $ids = DB::table('permissions')->where('name', self::PERMISSION)->where('guard_name', 'web')->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('model_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
