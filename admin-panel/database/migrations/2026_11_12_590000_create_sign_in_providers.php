<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Google and Microsoft sign-in (Action Plan 5.9, part 2). School-owned:
 * - sign_in_provider_settings: per school and provider, whether it is on
 *   (off by default), the email domains or Microsoft tenants it allows and
 *   who may use it.
 * - sign_in_provider_links: a provider account (its stable subject, never
 *   only the email) linked to one NavigateEd account at one school.
 * - sign_in_provider_challenges: the one-use state, nonce and PKCE verifier
 *   of each sign-in or link attempt (the verifier encrypted), kept a day.
 *
 * Links are what lets someone in, so the rollback refuses while any exist.
 * engineering/specs/platform/google-microsoft-sign-in.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sign_in_provider_settings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('provider', 20);
            $t->boolean('enabled')->default(false);
            $t->json('allowed_domains')->nullable()->comment('lower-case email domains; Google needs at least one');
            $t->json('allowed_tenants')->nullable()->comment('Microsoft Entra tenant ids (GUIDs); Microsoft needs at least one');
            $t->boolean('require_hosted_domain')->default(true)->comment('Google: the account must be a Workspace account of an allowed domain (hd claim)');
            $t->json('staff_roles')->nullable()->comment('staff role names allowed; null means every staff role');
            $t->boolean('learners_allowed')->default(false);
            $t->json('learner_stages')->nullable()->comment('age stages of learners allowed; null means high school');
            $t->foreignId('updated_by')->nullable()->constrained('users');
            $t->timestamps();
            $t->unique(['school_id', 'provider'], 'sign_in_provider_setting_school');
        });

        Schema::create('sign_in_provider_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->foreignId('user_id')->constrained('users');
            $t->string('provider', 20);
            $t->string('tenant_id', 64)->default('')->comment('Microsoft tid; empty for Google');
            $t->string('subject', 255)->comment('Google sub or Microsoft oid: stable, never reassigned');
            $t->string('email_at_link', 254)->comment('the verified email the provider gave when the link was made, shown to the person');
            $t->string('linked_via', 10)->comment('panel or app');
            $t->timestamp('last_used_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'provider', 'tenant_id', 'subject'], 'sign_in_provider_link_subject');
            $t->unique(['school_id', 'user_id', 'provider'], 'sign_in_provider_link_user');
        });

        Schema::create('sign_in_provider_challenges', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained('schools');
            $t->string('provider', 20);
            $t->string('purpose', 10)->comment('sign_in or link');
            $t->string('channel', 10)->comment('web or app');
            $t->foreignId('user_id')->nullable()->constrained('users')->comment('the signed-in account, for a link');
            $t->char('nonce_hash', 64)->comment('SHA-256 of the nonce; the nonce itself is never stored');
            $t->char('state_hash', 64)->nullable()->comment('SHA-256 of the state (web)');
            $t->text('code_verifier')->nullable()->comment('PKCE verifier (web), encrypted');
            $t->string('app', 20)->nullable()->comment('the app the sign-in is for (apps)');
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
            $t->unique('nonce_hash', 'sign_in_provider_challenge_nonce');
            $t->unique('state_hash', 'sign_in_provider_challenge_state');
            $t->index('expires_at', 'sign_in_provider_challenge_expiry');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('sign_in_provider_links') && DB::table('sign_in_provider_links')->exists()) {
            throw new RuntimeException('Linked sign-in accounts exist. Ask their schools to remove them, or restore the approved database backup.');
        }
        Schema::dropIfExists('sign_in_provider_challenges');
        Schema::dropIfExists('sign_in_provider_links');
        Schema::dropIfExists('sign_in_provider_settings');
    }
};
