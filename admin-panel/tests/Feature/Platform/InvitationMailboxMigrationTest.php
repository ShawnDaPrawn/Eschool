<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Students;
use App\Services\Platform\Identity\GuardianInvitations;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Identity\LearnerInvitations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

class InvitationMailboxMigrationTest extends TestCase
{
    use SignInFixtures;

    public function test_upgrade_retires_unbound_email_links_and_preserves_printed_codes_on_rollback(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
        Queue::fake();
        app(GuardianService::class)->syncLegacy(Students::findOrFail($this->people['student']));
        $learner = $this->person('learner');
        $guardian = $this->person('parent');
        $account = app(GuardianInvitations::class)->issue($guardian, $this->superAdmin());
        [$link] = app(LearnerInvitations::class)->issueLink($learner, $guardian);
        [$printed] = app(LearnerInvitations::class)->issueCode($learner);
        $migration = require database_path('migrations/2026_10_23_160000_bind_invitations_to_mailboxes.php');
        try {
            $migration->down();
            $this->assertFalse(Schema::hasColumn('guardian_invitations', 'email_hash'));
            $migration->up();
            $this->assertNotNull(DB::table('guardian_invitations')->where('id', $account->id)->value('used_at'));
            $this->assertNotNull(DB::table('learner_invitations')->where('id', $link->id)->value('revoked_at'));
            $this->assertNull(DB::table('learner_invitations')->where('id', $printed->id)->value('revoked_at'));
            $migration->down();
            $this->assertNotNull(DB::table('guardian_invitations')->where('id', $account->id)->value('used_at'));
            $this->assertNotNull(DB::table('learner_invitations')->where('id', $link->id)->value('revoked_at'));
        } finally {
            if (! Schema::hasColumn('guardian_invitations', 'email_hash')) {
                $migration->up();
            }
            RefreshDatabaseState::$migrated = false;
        }
    }
}
