<?php

declare(strict_types=1);

namespace Tests\Feature\Assistance;

use App\Models\Academics\AcademicLeadership;
use App\Models\Assistance\KnowledgeDocument;
use App\Models\Assistance\SupportMessage;
use App\Models\Assistance\SupportThread;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\User;
use App\Services\Assistance\KnowledgeDocuments;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\Concerns\UsesPracticeScenario;
use Tests\TestCase;

class SchoolAssistanceTest extends TestCase
{
    use UsesPracticeScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'ask_school'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->actor('farid')->forceFill(['password_change_required' => false])->save();
        $this->actor('mona')->forceFill(['password_change_required' => false, 'email_verified_at' => now(), 'status' => 1])->save();
        $this->superAdmin()->forceFill(['password_change_required' => false])->save();
        Http::preventStrayRequests();
        Http::fake();
    }

    private function document(array $changes = []): array
    {
        return app(KnowledgeDocuments::class)->publish($this->superAdmin(), $changes + ['operation_id' => (string) Str::uuid(),
            'expected_revision' => 0, 'title' => 'School handbook', 'markdown' => '# School hours\nLessons start at 08:00.',
            'source_reference' => 'Human-approved school handbook, 2026', 'identifiers_reviewed' => true, 'expires_on' => '2026-12-31']);
    }

    private function opening(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE-PARENT: please clarify the school hours.', 'language' => 'en'];
    }

    private function thread(?array $input = null): array
    {
        return app(SchoolSupportThreads::class)->open($this->actor('farid'), $this->schoolA->ids['asha'], $input ?? $this->opening());
    }

    private function claim(array $thread): array
    {
        return app(SchoolSupportThreads::class)->claim($this->superAdmin(), $thread['id'], ['operation_id' => (string) Str::uuid(), 'expected_revision' => $thread['revision']]);
    }

    public function test_approved_school_documents_are_immutable_versioned_encrypted_and_family_scoped(): void
    {
        $first = $this->document();
        $parent = $this->actor('farid');
        $documents = app(KnowledgeDocuments::class);
        $this->assertSame($first['id'], $documents->familyList($parent, $this->schoolA->ids['asha'])['items'][0]['id']);
        $this->assertStringContainsString('08:00', $documents->familyRead($parent, $this->schoolA->ids['asha'], $first['id'])->markdown);
        $this->assertStringNotContainsString('08:00', DB::table('school_knowledge_documents')->value('markdown'));
        $next = $this->document(['document_key' => $first['document_key'], 'expected_revision' => 1, 'markdown' => '# Revised\nLessons start at 08:15.']);
        $this->assertSame(2, $next['revision']);
        $this->assertSame(2, KnowledgeDocument::count());
        $this->refuses(fn () => $documents->familyRead($parent, $this->schoolA->ids['asha'], $first['id']), 404);
        $this->refuses(fn () => $documents->publish($this->actor('tara'), ['operation_id' => (string) Str::uuid(), 'expected_revision' => 0,
            'title' => 'Unapproved', 'markdown' => 'No', 'source_reference' => 'No', 'identifiers_reviewed' => true]), 403);
        Http::assertNothingSent();
    }

    public function test_human_handoff_is_exact_parent_owned_and_office_claim_precedes_private_body_access(): void
    {
        $service = app(SchoolSupportThreads::class);
        $input = $this->opening();
        $thread = $this->thread($input);
        $this->assertSame($thread, $this->thread($input));
        $this->assertSame(1, SupportThread::count());
        $this->assertSame(1, SupportMessage::count());
        $this->assertStringNotContainsString('PRIVATE-PARENT', DB::table('school_support_messages')->value('text'));
        $this->assertSame($thread['id'], $service->familyList($this->actor('farid'), $this->schoolA->ids['asha'])['items'][0]['id']);
        $this->refuses(fn () => $service->read($this->actor('mona'), $thread['id']), 404);
        $this->refuses(fn () => $service->read($this->superAdmin(), $thread['id'], true), 404);
        $this->assertArrayNotHasKey('text', $service->officeQueue($this->superAdmin())['items'][0]);
        $this->claim($thread);
        $this->assertSame($input['text'], $service->read($this->superAdmin(), $thread['id'], true)['messages'][0]['text']);
        $this->refuses(fn () => $this->thread(array_replace($input, ['text' => 'Changed intent'])), 409);
        Http::assertNothingSent();
    }

    public function test_two_way_replies_and_closure_have_exact_replay_and_no_implicit_new_message(): void
    {
        $service = app(SchoolSupportThreads::class);
        $thread = $this->thread();
        $claimInput = ['operation_id' => (string) Str::uuid(), 'expected_revision' => 1];
        $claimed = $service->claim($this->superAdmin(), $thread['id'], $claimInput);
        $this->assertSame($claimed, $service->claim($this->superAdmin(), $thread['id'], $claimInput));
        $officeInput = ['operation_id' => (string) Str::uuid(), 'text' => 'PRIVATE-OFFICE: school starts at 08:00.', 'language' => 'en'];
        $office = $service->reply($this->superAdmin(), $thread['id'], $officeInput, true);
        $this->assertSame($office, $service->reply($this->superAdmin(), $thread['id'], $officeInput, true));
        $parentInput = ['operation_id' => (string) Str::uuid(), 'text' => 'Thank you.', 'language' => 'en'];
        $parent = $service->reply($this->actor('farid'), $thread['id'], $parentInput);
        $this->assertSame(3, SupportMessage::count());
        $closing = ['operation_id' => (string) Str::uuid(), 'expected_revision' => $parent['revision']];
        $receipt = $service->close($this->actor('farid'), $thread['id'], $closing);
        $this->assertSame($receipt, $service->close($this->actor('farid'), $thread['id'], $closing));
        $this->assertSame($parent, $service->reply($this->actor('farid'), $thread['id'], $parentInput));
        $this->refuses(fn () => $service->reply($this->actor('farid'), $thread['id'], array_replace($parentInput, ['operation_id' => (string) Str::uuid()])), 409);
        Http::assertNothingSent();
    }

    public function test_module_password_platform_and_current_parent_grant_are_rechecked_before_replay(): void
    {
        $service = app(SchoolSupportThreads::class);
        $input = $this->opening();
        $thread = $this->thread($input);
        $parent = $this->actor('farid');
        $parent->forceFill(['password_change_required' => true])->save();
        $this->refuses(fn () => $service->open($parent, $this->schoolA->ids['asha'], $input), 403);
        $parent->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $parent->id]);
        $this->refuses(fn () => $service->read($parent, $thread['id']), 403);
        PlatformStaffIdentity::where('user_id', $parent->id)->delete();
        GuardianLink::where('guardian_user_id', $parent->id)->update(['status' => 'revoked']);
        $this->refuses(fn () => $service->open($parent, $this->schoolA->ids['asha'], $input), 404);
    }

    public function test_wrong_school_and_role_do_not_load_private_thread_messages(): void
    {
        $thread = $this->thread();
        $service = app(SchoolSupportThreads::class);
        $this->refuses(fn () => $service->read($this->actor('asha'), $thread['id']), 403);
        $this->refuses(fn () => $service->read($this->actor('tara'), $thread['id'], true), 403);
        $parent = $this->actor('farid');
        app(CurrentSchool::class)->run(2, function () use ($service, $parent, $thread): void {
            SchoolModule::updateOrCreate(['module' => 'ask_school'], ['in_package' => true, 'enabled' => true]);
            app(ModuleGate::class)->forget();
            $this->refuses(fn () => $service->read($parent, $thread['id']), 404);
            $otherParent = User::findOrFail($this->schoolB->ids['farid_user']);
            $otherParent->forceFill(['password_change_required' => false])->save();
            SchoolMembership::where('user_id', $otherParent->id)->update(['starts_on' => '2026-01-01']);
            $this->refuses(fn () => $service->read($otherParent, $thread['id']), 404);
        });
    }

    public function test_expiry_blocks_reads_immediately_and_tenant_purge_erases_bodies_retaining_receipts(): void
    {
        $thread = $this->thread();
        SupportThread::findOrFail($thread['id'])->update(['expires_at' => now()->utc()->subSecond()]);
        $service = app(SchoolSupportThreads::class);
        $this->refuses(fn () => $service->read($this->actor('farid'), $thread['id']), 404);
        SchoolModule::where('module', 'ask_school')->update(['enabled' => false]);
        $this->assertSame(0, Artisan::call('assistance:prune-private'));
        $this->assertStringContainsString('erased: 1', Artisan::output());
        $this->assertNull(DB::table('school_support_messages')->value('text'));
        $this->assertNotNull(SupportThread::findOrFail($thread['id'])->purged_at);
        $this->assertSame(1, DB::table('school_assistance_operations')->count());
        $this->assertSame(0, $service->purgeExpired());
    }

    public function test_family_role_cannot_inherit_office_authority_from_a_retained_principal_appointment(): void
    {
        AcademicLeadership::create(['user_id' => $this->actor('farid')->id,
            'session_year_id' => $this->year, 'grade_level_id' => null, 'kind' => 'principal',
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic retained appointment after role transition']);
        $this->refuses(fn () => app(SchoolSupportThreads::class)->officeQueue($this->actor('farid')), 403);
    }

    public function test_reasoned_office_handoff_revokes_the_previous_reader_and_keeps_parent_messages_unchanged(): void
    {
        $thread = $this->thread();
        $claimed = $this->claim($thread);
        $other = $this->actor('mona');
        $other->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'web')->firstOrFail());
        SchoolMembership::updateOrCreate(['school_id' => 1, 'user_id' => $other->id, 'role' => 'Super Admin'], ['starts_on' => '2026-01-01', 'status' => 'active']);
        $input = ['operation_id' => (string) Str::uuid(), 'expected_revision' => $claimed['revision'],
            'assigned_user_id' => $other->id, 'reason' => 'PRIVATE-OFFICE-REASON: designated cover for office leave.'];
        $service = app(SchoolSupportThreads::class);
        $receipt = $service->reassign($this->superAdmin(), $thread['id'], $input);
        $this->assertSame($receipt, $service->reassign($this->superAdmin(), $thread['id'], $input));
        $this->refuses(fn () => $service->read($this->superAdmin(), $thread['id'], true), 404);
        $this->assertCount(1, $service->read($other, $thread['id'], true)['messages']);
        $family = $service->read($this->actor('farid'), $thread['id']);
        $this->assertStringNotContainsString('PRIVATE-OFFICE-REASON', json_encode($family, JSON_THROW_ON_ERROR));
        $this->assertSame(1, SupportMessage::count());
    }
}
