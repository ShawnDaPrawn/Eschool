<?php

declare(strict_types=1);

namespace Tests\Feature\Cards;

use App\Models\Cards\CardKey;
use App\Models\Cards\CardQrUse;
use App\Models\Cards\CardScanLog;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardOfflineSnapshots;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Cards\CardScans;
use App\Services\Cards\Crypto\SignedOfflineManifest;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class CardOfflineSnapshotsTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo('2026-02-10 12:00:00 UTC');
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

    private string $readerSecret;

    private function reader(string $kind = 'gate'): array
    {
        $this->readerSecret = random_bytes(32);

        return app(CardReaders::class)->register($this->admin, [
            'operation_id' => (string) Str::uuid(), 'name' => 'Synthetic reader', 'kind' => $kind,
            'public_key' => base64_encode(sodium_crypto_scalarmult_base($this->readerSecret)), 'days' => 1,
        ]);
    }

    private function opened(array $page): array
    {
        $envelope = $page['envelope'];
        $shared = sodium_crypto_scalarmult($this->readerSecret, base64_decode($envelope['ephemeral_public_key'], true));
        $aad = json_encode(['suite' => $envelope['suite']] + $envelope['context'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $key = hash_hkdf('sha256', $shared, 32, $aad, base64_decode($envelope['salt'], true));
        $plain = openssl_decrypt(base64_decode($envelope['ciphertext'], true), 'aes-256-gcm', $key, OPENSSL_RAW_DATA,
            base64_decode($envelope['nonce'], true), base64_decode($envelope['tag'], true), $aad);

        return json_decode($plain, true, 12, JSON_THROW_ON_ERROR);
    }

    private function refused(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected the offline authority to refuse.');
        } catch (HttpException $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    public function test_encrypted_snapshot_contains_only_current_bounded_portraits_and_public_keys(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $page = app(CardOfflineSnapshots::class)->page($reader['credential']);
        $opened = $this->opened($page);
        $metadata = app(SignedOfflineManifest::class)->verify($opened['manifest'], base64_decode($page['signing_public_key'], true));
        $this->assertSame(60, $metadata['e'] - $metadata['i']);
        $this->assertSame($reader['id'], $metadata['d']);
        $this->assertSame([$card['id']], array_column($metadata['cards'], 'id'));
        $photo = base64_decode($opened['portraits'][$card['id']], true);
        $this->assertSame($metadata['cards'][0]['p'], hash('sha256', $photo));
        $size = getimagesizefromstring($photo);
        $this->assertLessThanOrEqual(128, max($size[0], $size[1]));
        $this->assertStringNotContainsString($opened['portraits'][$card['id']], json_encode($page, JSON_THROW_ON_ERROR));
        $this->assertArrayNotHasKey('secret_key', $metadata['keys'][0]);
        $this->assertNull($page['next_after']);
        SchoolMembership::where('user_id', $this->child->id)->where('role', 'Student')->update(['status' => 'inactive']);
        $this->assertSame([], app(SignedOfflineManifest::class)->verify($this->opened(app(CardOfflineSnapshots::class)->page($reader['credential']))['manifest'], base64_decode($page['signing_public_key'], true))['cards']);
    }

    public function test_offline_reconciliation_is_idempotent_and_globally_consumes_a_nonce_without_gate_authority(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $proof = app(Cards::class)->credential($this->parent, $card['id']);
        $opened = $this->opened(app(CardOfflineSnapshots::class)->page($reader['credential']));
        $when = now()->getTimestamp();
        $this->travel(10)->seconds();
        $input = ['operation_id' => (string) Str::uuid(), 'manifest' => $opened['manifest'], 'credential' => $proof['credential'], 'scanned_at' => (string) $when];
        $service = app(CardOfflineSnapshots::class);
        $result = $service->reconcile($reader['credential'], $input);
        $this->assertSame('offline_accepted', $result['outcome']);
        $this->assertFalse($result['collection_authorized']);
        $this->assertFalse($result['sign_in_authorized']);
        $this->assertSame($result, $service->reconcile($reader['credential'], $input));
        $this->assertSame(1, CardScanLog::count());
        $this->assertSame('duplicate', $service->reconcile($reader['credential'], array_replace($input, ['operation_id' => (string) Str::uuid()]))['outcome']);
        $this->assertSame(1, CardQrUse::count());
        $this->refused(409, fn () => app(CardScans::class)->qr($reader['credential'], ['operation_id' => (string) Str::uuid(), 'credential' => $proof['credential']]));
        $this->expectException(ModelNotFoundException::class);
        app(CardPortraits::class)->scanned($reader['credential'], $result['scan_id']);
    }

    public function test_current_freeze_is_reported_without_accepting_a_stale_snapshot(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $proof = app(Cards::class)->credential($this->parent, $card['id']);
        $opened = $this->opened(app(CardOfflineSnapshots::class)->page($reader['credential']));
        $input = ['operation_id' => (string) Str::uuid(), 'manifest' => $opened['manifest'], 'credential' => $proof['credential'], 'scanned_at' => now()->getTimestamp()];
        app(Cards::class)->freeze($this->parent, $card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic lost card']);
        $result = app(CardOfflineSnapshots::class)->reconcile($reader['credential'], $input);
        $this->assertSame('status_changed', $result['outcome']);
        $this->assertSame(0, CardQrUse::count());
        $this->assertSame([], app(SignedOfflineManifest::class)->verify($this->opened(app(CardOfflineSnapshots::class)->page($reader['credential']))['manifest'], base64_decode(CardKey::firstOrFail()->public_key, true))['cards']);
    }

    public function test_approval_withdrawal_and_issuer_withdrawal_cannot_reconcile_as_current_identity(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $proof = app(Cards::class)->credential($this->parent, $card['id']);
        $opened = $this->opened(app(CardOfflineSnapshots::class)->page($reader['credential']));
        $input = ['operation_id' => (string) Str::uuid(), 'manifest' => $opened['manifest'], 'credential' => $proof['credential'], 'scanned_at' => now()->getTimestamp()];
        LearnerApproval::query()->update(['withdrawn_at' => now()]);
        $result = app(CardOfflineSnapshots::class)->reconcile($reader['credential'], $input);
        $this->assertSame('access_changed', $result['outcome']);
        $this->assertSame(0, CardQrUse::count());
        SchoolMembership::where('user_id', $this->admin->id)->update(['status' => 'inactive']);
        $this->refused(403, fn () => app(CardOfflineSnapshots::class)->page($reader['credential']));
        $this->refused(403, fn () => app(CardOfflineSnapshots::class)->reconcile($reader['credential'], $input));
        $this->assertSame(1, CardScanLog::count());
    }

    public function test_other_device_school_expired_or_tampered_manifest_and_revoked_delegation_are_refused(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $proof = app(Cards::class)->credential($this->parent, $card['id']);
        $opened = $this->opened(app(CardOfflineSnapshots::class)->page($reader['credential']));
        $input = ['operation_id' => (string) Str::uuid(), 'manifest' => $opened['manifest'], 'credential' => $proof['credential'], 'scanned_at' => now()->getTimestamp()];
        $service = app(CardOfflineSnapshots::class);
        $other = $this->reader();
        $this->refused(403, fn () => $service->reconcile($other['credential'], $input));
        $tampered = $opened['manifest'];
        $index = strrpos($tampered, '.') + 1;
        $tampered[$index] = $tampered[$index] === 'A' ? 'B' : 'A';
        $this->refused(403, fn () => $service->reconcile($reader['credential'], array_replace($input, ['manifest' => $tampered])));
        $this->refused(403, fn () => $service->reconcile($reader['credential'], array_replace($input, ['scanned_at' => now()->getTimestamp() + 60])));
        $this->travel(12)->minutes();
        $this->refused(403, fn () => $service->reconcile($reader['credential'], $input));
        app(CardReaders::class)->revoke($this->admin, $reader['id']);
        $this->refused(403, fn () => $service->page($reader['credential']));
        app(CurrentSchool::class)->run(2, fn () => $this->refused(401, fn () => $service->page($other['credential'])));
    }

    public function test_recorded_scan_time_must_be_within_both_signature_verification_epochs(): void
    {
        $card = $this->issue();
        $reader = $this->reader();
        $proof = app(Cards::class)->credential($this->parent, $card['id']);
        $opened = $this->opened(app(CardOfflineSnapshots::class)->page($reader['credential']));
        $input = ['operation_id' => (string) Str::uuid(), 'manifest' => $opened['manifest'], 'credential' => $proof['credential'], 'scanned_at' => now()->getTimestamp()];
        CardKey::query()->update(['verify_until' => now()->utc()->subSecond()]);
        $this->refused(403, fn () => app(CardOfflineSnapshots::class)->reconcile($reader['credential'], $input));
        $this->assertSame(0, CardScanLog::count());
        $this->assertSame(0, CardQrUse::count());
    }
}
