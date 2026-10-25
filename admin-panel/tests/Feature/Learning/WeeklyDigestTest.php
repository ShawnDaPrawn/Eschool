<?php

declare(strict_types=1);

namespace Tests\Feature\Learning;

use App\Jobs\Platform\DispatchOutbox;
use App\Models\Academics\AttendanceEntry;
use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\TeachingGroup;
use App\Models\Event;
use App\Models\Learning\DigestLanguageTemplate;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\WeeklyParentDigest;
use App\Models\Notification;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Learning\Digests\DigestNotices;
use App\Services\Learning\Digests\DigestQueries;
use App\Services\Learning\Digests\DigestTemplates;
use App\Services\Learning\Digests\WeeklyDigests;
use App\Services\Platform\Notifications\PrivateNoticeScopes;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\UsesPracticeScenario;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class WeeklyDigestTest extends TestCase
{
    use UsesPracticeScenario;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpPracticeScenario();
        SchoolModule::updateOrCreate(['module' => 'digest'], ['in_package' => true, 'enabled' => true]);
        Bus::fake([DispatchOutbox::class]);
    }

    private function operation(array $data): array
    {
        return ['operation_id' => (string) Str::uuid()] + $data;
    }

    private function language(string $code = 'en', bool $approve = true): DigestLanguageTemplate
    {
        $template = config('weekly_digests.starting_templates.en');
        $data = $this->operation(['language' => $code] + $template);
        $ref = app(DigestTemplates::class)->create($this->superAdmin(), $data);
        if ($approve) {
            app(DigestTemplates::class)->review($this->superAdmin(), $ref['id'], $this->operation(['revision' => 1, 'decision' => 'approve']));
        }

        return DigestLanguageTemplate::findOrFail($ref['id']);
    }

    private function monday(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-02-16T08:00:00+02:00'));
    }

    private function makeDigest(): WeeklyParentDigest
    {
        $this->language();
        $this->monday();

        return app(WeeklyDigests::class)->generate($this->actor('farid'), $this->schoolA->ids['asha'], CarbonImmutable::parse('2026-02-09'));
    }

    private function apiAs(User $actor, int $school = 1): void
    {
        Sanctum::actingAs($actor);
        $this->withHeaders(['Accept' => 'application/json', 'X-School-Id' => (string) $school, 'X-Account-Id' => (string) $actor->id]);
    }

    public function test_no_unreviewed_language_or_silent_translation_fallback_is_delivered(): void
    {
        $this->language(approve: false);
        $this->monday();
        $this->assertNull(app(WeeklyDigests::class)->generate($this->actor('farid'), $this->schoolA->ids['asha'], CarbonImmutable::parse('2026-02-09')));
        $this->assertSame(0, WeeklyParentDigest::count());
        $this->assertSame(0, Notification::where('type', 'digest.weekly')->count());
        app(DigestQueries::class)->preferences($this->actor('farid'), $this->operation(['language' => 'zu', 'revision' => 0]));
        $this->language();
        $this->assertNull(app(WeeklyDigests::class)->generate($this->actor('farid'), $this->schoolA->ids['asha'], CarbonImmutable::parse('2026-02-09')));
    }

    public function test_school_evidence_is_frozen_encrypted_and_readable_in_under_one_minute(): void
    {
        $this->capture();
        $this->release();
        $this->approve();
        $set = $this->issue();
        $this->review($set);
        PracticeDailySet::create(['learner_id' => $this->schoolA->ids['asha'], 'teaching_group_id' => $this->group,
            'practice_on' => '2026-02-11', 'offline_until' => '2026-02-11 23:59:59']);
        $register = AttendanceRegister::create(['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'],
            'grade_level_id' => TeachingGroup::findOrFail($this->group)->grade_level_id, 'on_date' => '2026-02-10', 'scope_key' => 'synthetic-digest-homeroom', 'label' => 'Synthetic register']);
        AttendanceEntry::create(['attendance_register_id' => $register->id, 'learner_id' => $this->schoolA->ids['asha'], 'state' => 'absent', 'source' => 'teacher', 'captured_by' => $this->actor()->id]);
        Event::insert(['school_id' => 1, 'session_year_id' => $this->year, 'title' => 'Synthetic school event', 'start_date' => '2026-02-20', 'end_date' => '2026-02-20', 'type' => 'single']);
        $digest = $this->makeDigest();
        $data = app(DigestQueries::class)->show($this->actor('farid'), (int) $digest->id);
        $this->assertCount(1, $data['evidence']['marks']);
        $this->assertSame(1, $data['evidence']['attendance']['absent']);
        $this->assertSame(['total' => 2, 'completed' => 1], $data['evidence']['practice']);
        $this->assertSame(1, $data['evidence']['event_count']);
        $this->assertSame('practice', $data['home_action']['kind']);
        $this->assertLessThan(2000, mb_strlen($data['summary']));
        $raw = DB::table('weekly_parent_digests')->where('id', $digest->id)->first();
        $this->assertStringNotContainsString('Released results', $raw->summary);
        $this->assertStringNotContainsString('Synthetic school event', $raw->snapshot);
        $notice = Notification::findOrFail($digest->notification_id);
        $this->assertSame('digest.weekly', $notice->type);
        $this->assertSame(['link' => '/learning/weekly-digest', 'digest_id' => (int) $digest->id], $notice->delivery_payload);
        $this->assertStringNotContainsString('Asha', $notice->message);
        $this->assertSame('2026-02-09', $data['week_start']);
        $this->assertSame('2026-02-15', $data['week_end']);
    }

    public function test_exact_week_and_template_retries_do_not_duplicate_or_mutate_receipts(): void
    {
        $data = $this->operation(['language' => 'en'] + config('weekly_digests.starting_templates.en'));
        $templates = app(DigestTemplates::class);
        $ref = $templates->create($this->superAdmin(), $data);
        $this->assertSame($ref, $templates->create($this->superAdmin(), $data));
        $this->refuses(fn () => $templates->create($this->superAdmin(), array_replace($data, ['body' => 'Different reviewed text'])), 409);
        $templates->review($this->superAdmin(), $ref['id'], $this->operation(['revision' => 1, 'decision' => 'approve']));
        $this->monday();
        $first = app(WeeklyDigests::class)->generate($this->actor('farid'), $this->schoolA->ids['asha'], CarbonImmutable::parse('2026-02-09'));
        $second = app(WeeklyDigests::class)->generate($this->actor('farid'), $this->schoolA->ids['asha'], CarbonImmutable::parse('2026-02-09'));
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WeeklyParentDigest::count());
        $this->assertSame(1, Notification::where('type', 'digest.weekly')->count());
    }

    public function test_prepared_private_reads_and_delivery_recheck_ended_guardians(): void
    {
        $digest = $this->makeDigest();
        $parent = $this->actor('farid');
        $prepared = app(DigestQueries::class)->owned($parent);
        $notices = app(PrivateNoticeScopes::class)->apply(Notification::query(), (int) $parent->id);
        $this->assertSame(1, (clone $prepared)->count());
        $this->assertSame(1, (clone $notices)->where('type', 'digest.weekly')->count());
        GuardianLink::where('learner_id', $this->schoolA->ids['asha'])->where('guardian_user_id', $parent->id)->update(['status' => 'ended']);
        $this->assertSame(0, (clone $prepared)->count());
        $this->assertSame(0, (clone $notices)->where('type', 'digest.weekly')->count());
        $this->assertFalse(app(DigestNotices::class)->allows($parent, 'digest.weekly', ['digest_id' => $digest->id]));
        $this->apiAs($parent);
        $this->getJson('/api/v2/digests/'.$digest->id)->assertNotFound()->assertDontSee('Released results');
    }

    public function test_staff_permissions_other_owners_and_other_schools_never_grant_family_summary(): void
    {
        $digest = $this->makeDigest();
        foreach ([$this->superAdmin(), $this->actor()] as $staff) {
            $this->apiAs($staff);
            $this->getJson('/api/v2/digests/'.$digest->id)->assertForbidden()->assertDontSee('Released results');
        }
        $this->apiAs(User::findOrFail($this->schoolA->ids['gina_user']));
        $this->getJson('/api/v2/digests/'.$digest->id)->assertNotFound()->assertDontSee('Released results');
        $this->apiAs($this->actor('farid'), 2);
        $this->getJson('/api/v2/digests/'.$digest->id)->assertForbidden()->assertDontSee('Released results');
    }

    public function test_ended_student_membership_hides_digest_even_with_stale_enrolment(): void
    {
        $digest = $this->makeDigest();
        $query = app(DigestQueries::class)->owned($this->actor('farid'));
        SchoolMembership::where('user_id', $this->actor('asha')->id)->where('role', 'Student')->update(['status' => 'inactive']);
        $this->assertSame(0, $query->count());
        $this->assertFalse(app(DigestNotices::class)->allows($this->actor('farid'), 'digest.weekly', ['digest_id' => $digest->id]));
    }

    public function test_language_revision_and_revocation_are_effective_for_old_receipts(): void
    {
        $digest = $this->makeDigest();
        $template = DigestLanguageTemplate::findOrFail($digest->template_id);
        app(DigestTemplates::class)->review($this->superAdmin(), (int) $template->id, $this->operation(['revision' => $template->revision, 'decision' => 'revoke']));
        $this->assertSame(0, app(DigestQueries::class)->owned($this->actor('farid'))->count());
        $this->assertFalse(app(DigestNotices::class)->allows($this->actor('farid'), 'digest.weekly', ['digest_id' => $digest->id]));
        $this->apiAs($this->actor('farid'));
        $this->postJson('/api/v2/digests/preferences', $this->operation(['revision' => 0, 'language' => 'zu']))->assertOk();
        $this->postJson('/api/v2/digests/preferences', $this->operation(['revision' => 0, 'language' => 'en']))->assertConflict();
    }

    public function test_scheduler_uses_school_monday_and_each_child_parent_week_is_once_only(): void
    {
        $this->language();
        $this->travelTo(CarbonImmutable::parse('2026-02-15T23:00:00Z'));
        $this->assertSame(0, app(WeeklyDigests::class)->monday()['created']);
        $this->travelTo(CarbonImmutable::parse('2026-02-16T05:00:00Z'));
        $this->assertSame(2, app(WeeklyDigests::class)->monday()['created']);
        $this->assertEqualsCanonicalizing([$this->actor('farid')->id, $this->actor('mona')->id], WeeklyParentDigest::pluck('recipient_id')->all());
        $this->assertSame(0, app(WeeklyDigests::class)->monday()['created']);
    }

    public function test_template_authoring_is_manager_only_and_unknown_placeholders_are_refused(): void
    {
        $data = $this->operation(['language' => 'en'] + config('weekly_digests.starting_templates.en'));
        $this->refuses(fn () => app(DigestTemplates::class)->create($this->actor('farid'), $data), 403);
        $this->refuses(fn () => app(DigestTemplates::class)->create($this->superAdmin(), array_replace($data, ['body' => '{other_learner_private_notes}'])), 422);
        $this->assertSame(0, DigestLanguageTemplate::count());
    }

    public function test_expired_private_copies_are_erased_even_when_module_is_disabled(): void
    {
        $digest = $this->makeDigest();
        $digest->update(['expires_at' => now()->subMinute()]);
        SchoolModule::where('module', 'digest')->update(['enabled' => false]);
        $this->artisan('digest:weekly', ['--retention' => true])->assertSuccessful();
        $this->assertSame(0, WeeklyParentDigest::count());
    }

    public function test_http_parent_reads_exact_child_week_and_cannot_substitute_account(): void
    {
        $digest = $this->makeDigest();
        $this->apiAs($this->actor('farid'));
        $this->getJson('/api/v2/digests/access')->assertOk()->assertJsonPath('data.parent', true)->assertJsonPath('data.account_id', $this->actor('farid')->id);
        $this->getJson('/api/v2/digests/learners/'.$this->schoolA->ids['asha'].'/weeks')->assertOk()->assertJsonPath('data.items.0.id', (int) $digest->id)->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson('/api/v2/digests/'.$digest->id)->assertOk()->assertJsonPath('data.learner_id', $this->schoolA->ids['asha'])->assertJsonPath('data.language', 'en');
        $this->withHeader('X-Account-Id', (string) $this->actor()->id)->getJson('/api/v2/digests/'.$digest->id)->assertForbidden()->assertDontSee('Released results');
    }

    public function test_http_manager_template_contracts_and_disabled_module_do_not_reveal_family_copy(): void
    {
        $this->apiAs($this->superAdmin());
        $data = $this->operation(['language' => 'en'] + config('weekly_digests.starting_templates.en'));
        $created = $this->postJson('/api/v2/digests/templates', $data)->assertOk();
        $id = $created->json('data.id');
        $this->getJson('/api/v2/digests/templates')->assertOk()->assertJsonPath('data.items.0.approved', false);
        $this->postJson('/api/v2/digests/templates/'.$id.'/review', $this->operation(['revision' => 1, 'decision' => 'approve']))->assertOk();
        $this->getJson('/api/v2/digests/templates')->assertOk()->assertJsonPath('data.items.0.approved', true);
        $this->apiAs($this->actor('farid'));
        $this->getJson('/api/v2/digests/templates')->assertForbidden();
        SchoolModule::where('module', 'digest')->update(['enabled' => false]);
        $this->getJson('/api/v2/digests/access')->assertNotFound();
    }

    public function test_approved_language_templates_render_deterministic_school_evidence_without_free_generation(): void
    {
        // Synthetic wording checks the language/template plumbing. It does not
        // certify translation quality or approve English as another language.
        $values = ['learner' => 'Synthetic learner', 'week_start' => '2026-02-09', 'week_end' => '2026-02-15',
            'marks_count' => 1, 'attendance_present' => 4, 'attendance_absent' => 1,
            'practice_completed' => 2, 'practice_total' => 3, 'event_count' => 1, 'home_action' => 'Synthetic home instruction'];
        foreach (config('weekly_digests.languages') as $language) {
            $template = $this->language($language);
            $actual = app(DigestTemplates::class)->render($template, $values);
            $this->assertSame('This week for Synthetic learner', $actual['title'], $language);
            $this->assertSame('2026-02-09 to 2026-02-15. Released results: 1. Attendance recorded: 4 present, 1 absent. Practice sets: 2 completed of 3. Upcoming school events: 1. At home: Synthetic home instruction', $actual['summary'], $language);
        }
    }

    public function test_suspended_learner_and_foreign_context_cannot_reuse_a_prepared_summary(): void
    {
        $digest = $this->makeDigest();
        $query = app(DigestQueries::class)->owned($this->actor('farid'));
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => (clone $query)->count()));
        User::acrossSchools()->whereKey($this->actor('asha')->id)->update(['status' => 0]);
        $this->assertSame(0, (clone $query)->count());
        $this->assertFalse(app(DigestNotices::class)->allows($this->actor('farid'), 'digest.weekly', ['digest_id' => $digest->id]));
    }

    public function test_panel_manager_authors_reviews_and_withdraws_an_immutable_language_version(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin)->get('/weekly-digest-templates')->assertOk()->assertSee('Weekly digest templates')->assertHeader('Cache-Control', 'no-store, private');
        $data = $this->operation(['language' => 'en', 'expected_school_id' => 1, 'expected_account_id' => $admin->id] + config('weekly_digests.starting_templates.en'));
        $this->post('/weekly-digest-templates/versions', $data)->assertRedirect();
        $template = DigestLanguageTemplate::firstOrFail();
        $this->assertNull($template->approved_at);
        $review = $this->operation(['expected_school_id' => 1, 'expected_account_id' => $admin->id, 'revision' => 1, 'decision' => 'approve']);
        $this->post('/weekly-digest-templates/versions/'.$template->id.'/review', $review)->assertRedirect();
        $this->assertNotNull($template->fresh()->approved_at);
        $this->get('/weekly-digest-templates')->assertOk()->assertSee('Approved for weekly delivery');
        $this->post('/weekly-digest-templates/versions/'.$template->id.'/review', $this->operation(['expected_school_id' => 1, 'expected_account_id' => $admin->id, 'revision' => 2, 'decision' => 'revoke']))->assertRedirect();
        $this->assertNotNull($template->fresh()->revoked_at);
    }

    public function test_panel_invalid_draft_is_private_and_stale_account_cannot_reflect_it(): void
    {
        $admin = $this->superAdmin();
        $data = $this->operation(['language' => 'en', 'expected_school_id' => 1, 'expected_account_id' => $admin->id] + config('weekly_digests.starting_templates.en'));
        $data['body'] = 'Private school language draft <script>unsafe</script>';
        $this->actingAs($admin)->post('/weekly-digest-templates/versions', $data)->assertStatus(422)->assertSee('Private school language draft &lt;script&gt;', false)->assertDontSee('<script>unsafe</script>', false)->assertSessionMissing('_old_input.body');
        $data['expected_account_id'] = $this->actor()->id;
        $this->post('/weekly-digest-templates/versions', $data)->assertForbidden()->assertDontSee('Private school language draft')->assertSessionMissing('_old_input');
        $this->assertSame(0, DigestLanguageTemplate::count());
    }

    public function test_teacher_cannot_review_school_digest_languages(): void
    {
        $this->actingAs($this->actor())->get('/weekly-digest-templates')->assertForbidden()->assertDontSee('Starting text is English');
    }

    public function test_historical_panel_year_refuses_language_write_without_session_flash(): void
    {
        $admin = $this->superAdmin();
        $old = SessionYear::create(['name' => 'Digest past year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        $data = $this->operation(['language' => 'en', 'expected_school_id' => 1, 'expected_account_id' => $admin->id] + config('weekly_digests.starting_templates.en'));
        $data['body'] = 'Private historical template draft';
        $this->actingAs($admin)->withSession(['session_year' => $old->id])->post('/weekly-digest-templates/versions', $data)->assertStatus(423)->assertSee('Private historical template draft')->assertSessionMissing('_old_input.body');
        $this->assertSame(0, DigestLanguageTemplate::count());
    }
}
