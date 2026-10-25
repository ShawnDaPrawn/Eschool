<?php

declare(strict_types=1);

namespace Tests\Feature\Ai;

use App\Models\Ai\AiFlag;
use App\Models\Ai\AiFlagReview;
use App\Models\Ai\AiQuarantineSnapshot;
use App\Models\Ai\AiUsage;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Ai\Quarantine\AiFlagReviews;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Ai\Quarantine\QuarantineMediaResolver;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class AiFlagReviewTest extends TestCase
{
    use UsesTwoSchools;

    private User $reviewer;

    private AiUsage $usage;

    private int $flag;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->set(1);
        $this->reviewer = User::create(['first_name' => 'Synthetic', 'last_name' => 'Reviewer', 'email' => 'reviewer@example.test', 'password' => Hash::make('Synthetic2026'), 'status' => 1]);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $this->reviewer->id, 'role' => 'Safeguarding', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->reviewer->assignRole('Safeguarding');
        $this->usage = AiUsage::create(['user_id' => $this->schoolA->ids['tara_user'], 'feature' => 'synthetic_review', 'provider' => 'synthetic', 'operation_id' => (string) Str::uuid(), 'request_hash' => hash('sha256', 'synthetic'), 'usage_date' => now()->toDateString(), 'unit' => 'tokens', 'state' => 'reserved', 'reserved_units' => 50, 'reserved_cost_cents' => 2]);
        app(AiQuarantine::class)->capture($this->usage, 'input', ['safety'], 'Synthetic private <script>providerUnsafe()</script> disclosure');
        $this->flag = (int) AiFlag::firstOrFail()->id;
    }

    private function data(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'decision' => 'reviewed', 'reason' => 'Synthetic confidential review note'];
    }

    public function test_explicit_reviewer_can_read_and_record_encrypted_idempotent_review_without_releasing_output(): void
    {
        $service = app(AiFlagReviews::class);
        $this->assertStringContainsString('Synthetic private', $service->show($this->reviewer, $this->flag)['content']['text']);
        $data = $this->data();
        $result = $service->review($this->reviewer, $this->flag, $data);
        $this->assertSame(2, $result['revision']);
        $this->assertSame($result, $service->review($this->reviewer, $this->flag, $data));
        $this->assertSame(1, AiFlagReview::count());
        $this->assertStringNotContainsString($data['reason'], DB::table('ai_flag_reviews')->value('private_reason'));
        $this->assertSame($data['reason'], AiFlagReview::firstOrFail()->private_reason);
        $this->assertArrayNotHasKey('private_reason', AiFlagReview::firstOrFail()->toArray());
        $this->assertSame('quarantined', $this->usage->fresh()->state);
        $this->assertNull($this->usage->fresh()->inference_output);
        $this->expectException(HttpException::class);
        $service->review($this->reviewer, $this->flag, [...$data, 'reason' => 'Changed intent']);
    }

    public function test_parent_teacher_admin_and_platform_operator_cannot_read_private_flags(): void
    {
        foreach ([$this->superAdmin(), User::findOrFail($this->schoolA->ids['tara_user'])] as $actor) {
            $this->actingAs($actor)->get('/ai-flags/'.$this->flag)->assertForbidden()->assertDontSee('Synthetic private');
        }
        $this->actingAs(User::findOrFail($this->schoolA->ids['mona_user']))->get('/ai-flags/'.$this->flag)->assertRedirect(route('login'))->assertDontSee('Synthetic private');
        PlatformStaffIdentity::create(['user_id' => $this->reviewer->id, 'active' => true]);
        $this->actingAs($this->reviewer)->get('/ai-flags/'.$this->flag)->assertForbidden();
    }

    public function test_prepared_query_and_replay_recheck_withdrawn_membership(): void
    {
        $query = app(AiFlagReviews::class)->owned($this->reviewer);
        $data = $this->data();
        app(AiFlagReviews::class)->review($this->reviewer, $this->flag, $data);
        SchoolMembership::where('user_id', $this->reviewer->id)->update(['status' => 'inactive']);
        $this->assertSame(0, $query->count());
        $this->actingAs($this->reviewer)->post('/ai-flags/'.$this->flag.'/review', $data + ['expected_school_id' => 1, 'expected_account_id' => $this->reviewer->id])->assertRedirect(route('login'))->assertDontSee('Synthetic confidential review note');
    }

    public function test_expiry_hides_body_before_purge_and_cascades_encrypted_review_without_refunding_compute(): void
    {
        app(AiFlagReviews::class)->review($this->reviewer, $this->flag, $this->data());
        $this->travel(31)->days();
        $this->assertSame(0, app(AiFlagReviews::class)->listing($this->reviewer)['total']);
        $this->actingAs($this->reviewer)->get('/ai-flags/'.$this->flag)->assertNotFound()->assertDontSee('Synthetic private');
        $this->assertSame(1, app(AiQuarantine::class)->prune());
        $this->assertSame(0, AiFlagReview::count());
        $this->assertSame(50, $this->usage->fresh()->reserved_units);
        $this->assertSame(2, $this->usage->fresh()->reserved_cost_cents);
    }

    public function test_panel_escapes_provider_text_and_private_validation_never_flashes_reason(): void
    {
        $url = '/ai-flags/'.$this->flag;
        $this->actingAs($this->reviewer)->get($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('&lt;script&gt;providerUnsafe()', false)->assertDontSee('<script>providerUnsafe()', false);
        $data = $this->data() + ['expected_school_id' => 1, 'expected_account_id' => $this->reviewer->id];
        $data['decision'] = 'invalid';
        $this->post($url.'/review', $data)->assertStatus(422)->assertSee('Synthetic confidential review note')->assertSessionMissing('_old_input.reason');
        $data['expected_account_id']++;
        $this->post($url.'/review', $data)->assertForbidden()->assertDontSee('Synthetic confidential review note')->assertSessionMissing('_old_input.reason');
    }

    public function test_same_ids_in_wrong_school_and_shorter_retention_are_unreadable(): void
    {
        $query = app(AiFlagReviews::class)->owned($this->reviewer);
        app(CurrentSchool::class)->set(2);
        $this->assertSame(0, $query->count());
        app(CurrentSchool::class)->set(1);
        $this->travel(3)->days();
        config(['ai.quarantine.retention_days' => 2]);
        $this->assertSame(0, app(AiFlagReviews::class)->listing($this->reviewer)['total']);
    }

    public function test_photo_reader_requires_exact_retained_manifest_and_rechecks_expiry_after_adapter(): void
    {
        $bytes = ReviewMediaProbe::bytes();
        $usage = $this->usage->replicate();
        $usage->operation_id = (string) Str::uuid();
        $usage->state = 'reserved';
        $usage->save();
        app(AiQuarantine::class)->capture($usage, 'input', ['safety'], ['text' => 'Synthetic crop', 'media' => [['id' => 'crop-1', 'sha256' => hash('sha256', $bytes), 'mime' => 'image/png']]]);
        $id = (int) AiFlag::where('ai_usage_id', $usage->id)->value('id');
        config(['ai.quarantine.media_resolvers.synthetic_review' => ReviewMediaProbe::class]);
        ReviewMediaProbe::$expire = false;
        $this->actingAs($this->reviewer)->get('/ai-flags/'.$id.'/media/crop-1')->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('Cache-Control', 'no-store, private')->assertContent($bytes);
        $this->get('/ai-flags/'.$id.'/media/unknown')->assertNotFound();
        ReviewMediaProbe::$expire = true;
        $this->get('/ai-flags/'.$id.'/media/crop-1')->assertNotFound()->assertDontSee($bytes, false);
    }

    public function test_permission_withdrawal_is_applied_to_already_prepared_query(): void
    {
        $query = app(AiFlagReviews::class)->owned($this->reviewer);
        $this->assertSame(1, $query->count());
        $role = Role::where('name', 'Safeguarding')->firstOrFail();
        $role->revokePermissionTo('ai-flags-review');
        $this->assertSame(0, $query->count());
        $this->actingAs($this->reviewer)->get('/ai-flags')->assertForbidden()->assertDontSee('Synthetic private');
    }
}

class ReviewMediaProbe implements QuarantineMediaResolver
{
    public static bool $expire = false;

    public static function bytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a9F8AAAAASUVORK5CYII=', true);
    }

    public function read(User $reviewer, AiUsage $usage, array $reference): array
    {
        if (self::$expire) {
            AiQuarantineSnapshot::where('ai_usage_id', $usage->id)->update(['expires_at' => now()->subSecond()]);
        }

        return ['bytes' => self::bytes(), 'mime' => 'image/png'];
    }
}
