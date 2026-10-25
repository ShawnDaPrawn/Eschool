<?php

declare(strict_types=1);

namespace Tests\Feature\Cards;

use App\Models\Cards\Card;
use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardKey;
use App\Models\Cards\CardPortrait;
use App\Models\Cards\CardQrUse;
use App\Models\Cards\CardScanLog;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Cards\CardScans;
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

    private function reader(string $kind = 'gate'): array
    {
        return app(CardReaders::class)->register($this->admin, [
            'operation_id' => (string) Str::uuid(), 'name' => 'Synthetic reader', 'kind' => $kind,
            'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1,
        ]);
    }

    public function test_online_qr_consumption_is_global_and_exact_transport_retry_does_not_scan_twice(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $proof = app(Cards::class)->credential($this->parent, $card['id']);
        $input = ['operation_id' => (string) Str::uuid(), 'credential' => $proof['credential']];
        $scans = app(CardScans::class);
        $result = $scans->qr($reader['credential'], $input);
        $this->assertSame($result, $scans->qr($reader['credential'], $input));
        $this->assertSame(1, CardScanLog::count());
        $this->assertSame(1, CardQrUse::count());
        $input['operation_id'] = (string) Str::uuid();
        try {
            $scans->qr($this->reader()['credential'], $input);
            $this->fail('Different device reused a credential.');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $this->assertSame(1, CardScanLog::count());
    }

    public function test_frozen_cards_and_revoked_devices_refuse_even_an_existing_scan_receipt(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $input = ['operation_id' => (string) Str::uuid(), 'credential' => app(Cards::class)->credential($this->parent, $card['id'])['credential']];
        $scans = app(CardScans::class);
        $scans->qr($reader['credential'], $input);
        app(Cards::class)->freeze($this->parent, $card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Lost card']);
        try {
            $scans->qr($reader['credential'], $input);
            $this->fail('Frozen receipt replayed.');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        app(CardReaders::class)->revoke($this->admin, $reader['id']);
        try {
            $scans->qr($reader['credential'], $input);
            $this->fail('Revoked reader accepted.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
    }

    public function test_reader_credentials_are_school_bound_and_encoder_cannot_scan(): void
    {
        $reader = $this->reader('encoder');
        $readers = app(CardReaders::class);
        try {
            $readers->authenticate($reader['credential'], 'gate');
            $this->fail('Encoder became a gate.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
        try {
            $readers->authenticate('ordinary-account-token', 'gate');
            $this->fail('Account token became a reader.');
        } catch (HttpException $error) {
            $this->assertSame(401, $error->getStatusCode());
        }
        app(CurrentSchool::class)->run(2, function () use ($readers, $reader) {
            try {
                $readers->authenticate($reader['credential'], 'encoder');
                $this->fail('Reader crossed school.');
            } catch (HttpException $error) {
                $this->assertSame(401, $error->getStatusCode());
            }
        });
        $this->travel(2)->days();
        try {
            $readers->authenticate($reader['credential'], 'encoder');
            $this->fail('Expired reader accepted.');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
    }

    public function test_sun_counter_is_atomic_and_tampered_proof_never_advances_it(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $encoder = $this->reader('encoder');
        // Published NXP table 4 fixture, not a claim of encoded hardware.
        $profile = CardChipProfile::create(['card_id' => $card['id'], 'encoder_device_id' => $encoder['id'],
            'uid' => '04DE5F1EACC040', 'uid_hash' => hash('sha256', '04DE5F1EACC040'), 'metadata_key' => base64_encode(str_repeat("\0", 16)),
            'file_read_key' => base64_encode(str_repeat("\0", 16)), 'encoding_package' => 'synthetic-encrypted-plan', 'encoded_at' => now()->utc()]);
        $input = ['operation_id' => (string) Str::uuid(), 'card_id' => $card['id'], 'picc' => 'EF963FF7828658A599F3041510671E88', 'mac' => '94EED9EE65337086'];
        $scans = app(CardScans::class);
        $bad = [...$input, 'mac' => '0000000000000000'];
        try {
            $scans->sun($reader['credential'], $bad);
            $this->fail('Forged SUN accepted.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame(-1, $profile->fresh()->last_counter);
        $scan = $scans->sun($reader['credential'], $input);
        $this->assertSame($scan, $scans->sun($reader['credential'], $input));
        $this->assertSame(61, $profile->fresh()->last_counter);
        try {
            $scans->sun($reader['credential'], [...$input, 'operation_id' => (string) Str::uuid()]);
            $this->fail('SUN replay accepted.');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame(1, CardScanLog::count());
    }

    public function test_outer_rollback_does_not_consume_proof_and_expired_new_operation_is_refused(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $input = ['operation_id' => (string) Str::uuid(), 'credential' => app(Cards::class)->credential($this->parent, $card['id'])['credential']];
        $scans = app(CardScans::class);
        DB::beginTransaction();
        $scans->qr($reader['credential'], $input);
        DB::rollBack();
        $this->assertSame(0, CardQrUse::count());
        $scans->qr($reader['credential'], $input);
        $this->assertSame(1, CardQrUse::count());
        $this->travel(26)->seconds();
        $this->assertSame('accepted', $scans->qr($reader['credential'], $input)['outcome']);
        try {
            $scans->qr($reader['credential'], [...$input, 'operation_id' => (string) Str::uuid()]);
            $this->fail('Expired proof accepted.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_scanner_portrait_requires_own_recent_scan_and_fresh_active_source(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $input = ['operation_id' => (string) Str::uuid(), 'credential' => app(Cards::class)->credential($this->parent, $card['id'])['credential']];
        $scan = app(CardScans::class)->qr($reader['credential'], $input);
        $portraits = app(CardPortraits::class);
        $this->assertSame('image/jpeg', $portraits->scanned($reader['credential'], $scan['scan_id'])['mime']);
        try {
            $portraits->scanned($this->reader()['credential'], $scan['scan_id']);
            $this->fail('Other reader obtained face.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }
        SchoolMembership::where('user_id', $this->child->id)->where('role', 'Student')->update(['ends_on' => '2026-02-09']);
        try {
            $portraits->scanned($reader['credential'], $scan['scan_id']);
            $this->fail('Ended source retained face.');
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }
    }

    public function test_reader_delegation_stops_on_issuer_role_withdrawal_before_receipt_or_portrait(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $input = ['operation_id' => (string) Str::uuid(), 'credential' => app(Cards::class)->credential($this->parent, $card['id'])['credential']];
        $scan = app(CardScans::class)->qr($reader['credential'], $input);
        SchoolMembership::where('user_id', $this->admin->id)->update(['ends_on' => '2026-02-09']);
        try {
            app(CardScans::class)->qr($reader['credential'], $input);
            $this->fail('Removed issuer retained scanner delegation.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        try {
            app(CardPortraits::class)->scanned($reader['credential'], $scan['scan_id']);
            $this->fail('Removed issuer retained portrait.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
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
