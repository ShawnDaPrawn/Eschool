<?php

declare(strict_types=1);

namespace Tests\Feature\Cards;

use App\Models\Cards\Card;
use App\Models\Cards\CardKey;
use App\Models\Cards\CardPortrait;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\Cards;
use App\Services\Cards\CardSigningKeys;
use App\Services\Cards\Crypto\SignedCardQr;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class CardDomainTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['modules.cards' => ['always_on' => true]]);
        Storage::fake(CardPortraits::DISK);
        $this->app->bind(UploadScanner::class, fn () => new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-test-only', 'signature' => null];
            }
        });
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        $this->child = User::findOrFail($this->schoolA->ids['asha_user']);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($this->parent, Students::findOrFail($this->schoolA->ids['asha']), 'approve', config('identity.consent_version'));
    }

    private function issue(): array
    {
        app(CardPortraits::class)->upload($this->parent, (int) $this->child->id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('portrait.jpg', 120, 160));

        return app(Cards::class)->issue($this->admin, ['operation_id' => (string) Str::uuid(), 'person_id' => $this->child->id, 'type' => 'learner']);
    }

    public function test_issue_portrait_and_signed_qr_are_current_private_and_retry_safe(): void
    {
        $photo = app(CardPortraits::class)->upload($this->parent, (int) $this->child->id, 'learner', (string) Str::uuid(), UploadedFile::fake()->image('portrait.jpg', 120, 160));
        $input = ['operation_id' => (string) Str::uuid(), 'person_id' => $this->child->id, 'type' => 'learner'];
        $card = app(Cards::class)->issue($this->admin, $input);
        $this->assertSame($card, app(Cards::class)->issue($this->admin, $input));
        $this->assertSame(1, Card::count());
        $digital = app(Cards::class)->credential($this->parent, $card['id']);
        $key = CardKey::findOrFail($digital['key_id']);
        $claims = app(SignedCardQr::class)->verify($digital['credential'], base64_decode($key->public_key), 1, $card['id'], $key->id, now()->getTimestamp());
        $this->assertSame(1, $claims['r']);
        $this->assertStringNotContainsString($key->secret_key, $key->toJson());
        $this->assertStringNotContainsString($key->secret_key, DB::table('card_keys')->value('secret_key'));
        $portrait = CardPortrait::findOrFail($photo['portrait_id']);
        $bytes = app(CardPortraits::class)->owner($this->parent, (int) $this->child->id, 'learner')['bytes'];
        $this->assertSame('image/jpeg', (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes));
        $this->assertNotSame($bytes, Storage::disk(CardPortraits::DISK)->get($portrait->path));
        $this->assertStringNotContainsString('credential', DB::table('card_operations')->value('receipt'));
    }

    public function test_pending_and_withdrawn_approval_never_activate_card_even_with_legacy_flag_off(): void
    {
        $card = $this->issue();
        config(['identity.approval_enforced' => false]);
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $this->expectException(HttpException::class);
        app(Cards::class)->credential($this->parent, $card['id']);
    }

    public function test_parent_freeze_is_immediate_and_replacement_retains_old_identity(): void
    {
        $card = $this->issue();
        $this->assertSame('frozen', app(Cards::class)->freeze($this->parent, $card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic lost card'])['status']);
        try {
            app(Cards::class)->credential($this->child, $card['id']);
            $this->fail('Frozen card produced a credential.');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $result = app(Cards::class)->replace($this->admin, $card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic replacement']);
        $this->assertSame('replaced', $result['previous']['status']);
        $this->assertSame($result['current']['id'], $result['previous']['replaced_by']);
        $this->assertNotSame($card['id'], $result['current']['id']);
        $this->assertSame(2, Card::count());
        $this->assertNotEmpty(app(Cards::class)->credential($this->child, $result['current']['id'])['credential']);
    }

    public function test_teacher_and_other_parent_cannot_issue_or_read_a_child_card(): void
    {
        $card = $this->issue();
        foreach ([User::findOrFail($this->schoolA->ids['tara_user']), User::findOrFail($this->schoolA->ids['gina_user']), app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['farid_user']))] as $actor) {
            try {
                app(Cards::class)->credential($actor, $card['id']);
                $this->fail('Another account read the card.');
            } catch (HttpException|ModelNotFoundException $error) {
                $this->assertContains($error instanceof HttpException ? $error->getStatusCode() : 404, [403, 404]);
            }
        }
        $this->expectException(HttpException::class);
        app(Cards::class)->issue(User::findOrFail($this->schoolA->ids['tara_user']), ['operation_id' => (string) Str::uuid(), 'person_id' => $this->child->id, 'type' => 'learner']);
    }

    public function test_current_child_membership_is_required_despite_retained_links(): void
    {
        $card = $this->issue();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->child->id)->where('role', 'Student')->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->child->id, 'role' => 'Student', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->expectException(ModelNotFoundException::class);
        app(Cards::class)->credential($this->parent, $card['id']);
    }

    public function test_parent_facilitator_dual_role_keeps_own_card_scope_but_converted_parent_does_not(): void
    {
        $card = $this->issue();
        SchoolMembership::create(['school_id' => 1, 'user_id' => $this->parent->id, 'role' => 'Facilitator', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->assertNotEmpty(app(Cards::class)->credential($this->parent, $card['id'])['credential']);
        SchoolMembership::where('school_id', 1)->where('user_id', $this->parent->id)->where('role', 'Parent')->update(['status' => 'ended']);
        $this->expectException(HttpException::class);
        app(Cards::class)->credential($this->parent, $card['id']);
    }

    public function test_signing_key_rotation_preserves_only_the_bounded_credential_overlap(): void
    {
        $card = $this->issue();
        $first = app(Cards::class)->credential($this->parent, $card['id']);
        $oldKey = CardKey::findOrFail($first['key_id']);
        $this->travelTo($oldKey->sign_until->subSeconds(10));
        $last = app(Cards::class)->credential($this->parent, $card['id']);
        $this->assertSame($oldKey->id, $last['key_id']);
        $this->travel(11)->seconds();
        $new = app(Cards::class)->credential($this->parent, $card['id']);
        $this->assertNotSame($oldKey->id, $new['key_id']);
        $claims = app(SignedCardQr::class)->verify($last['credential'], base64_decode($oldKey->public_key), 1, $card['id'], $oldKey->id, now()->getTimestamp());
        $this->assertSame($oldKey->id, app(CardSigningKeys::class)->forVerification($oldKey->id, $claims['i'])->id);
        $this->travel(15)->seconds();
        $this->expectException(\UnexpectedValueException::class);
        app(SignedCardQr::class)->verify($last['credential'], base64_decode($oldKey->public_key), 1, $card['id'], $oldKey->id, now()->getTimestamp());
    }

    public function test_platform_and_impersonated_managers_cannot_open_card_credentials(): void
    {
        $card = $this->issue();
        PlatformStaffIdentity::create(['user_id' => $this->admin->id, 'active' => true]);
        try {
            app(Cards::class)->credential($this->admin, $card['id']);
            $this->fail('Platform staff entered cards.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        PlatformStaffIdentity::where('user_id', $this->admin->id)->delete();
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        try {
            app(Cards::class)->credential($this->admin, $card['id']);
            $this->fail('Impersonation entered cards.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        app(CurrentSchool::class)->set(1);
        app(CurrentSchool::class)->acrossSchools(function () use ($card) {
            try {
                app(Cards::class)->credential($this->admin, $card['id']);
                $this->fail('Across-school context entered cards.');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        });
    }

    public function test_card_identity_and_key_history_are_not_dropped_by_rollback(): void
    {
        $card = $this->issue();
        $migration = require database_path('migrations/2026_10_25_230000_create_secure_cards.php');
        try {
            $migration->down();
            $this->fail('Issued card history was dropped.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('rollback refused', $error->getMessage());
        }
        $this->assertSame('active', Card::findOrFail($card['id'])->status);
    }
}
