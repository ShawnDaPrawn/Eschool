<?php

declare(strict_types=1);

namespace Tests\Feature\Cards;

use App\Models\Cards\CardChipProfile;
use App\Models\Cards\CardEvent;
use App\Models\Cards\CardScanLog;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\Students;
use App\Models\User;
use App\Services\Cards\CardAccess;
use App\Services\Cards\CardEncoding;
use App\Services\Cards\CardPortraits;
use App\Services\Cards\CardReaders;
use App\Services\Cards\Cards;
use App\Services\Cards\CardScans;
use App\Services\Cards\Crypto\AesCmac;
use App\Services\Cards\Crypto\SunVerifier;
use App\Services\Platform\Files\UploadScanner;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class CardEncodingTest extends TestCase
{
    use UsesTwoSchools;
    use ValidatesOpenApiResponses;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo('2026-02-10 12:00:00 UTC');
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        config(['modules.cards.always_on' => true]);
        Storage::fake(CardPortraits::DISK);
        $this->app->bind(UploadScanner::class, fn () => new class implements UploadScanner
        {
            public function scan(string $bytes): array
            {
                return ['clean' => true, 'engine' => 'synthetic-encoding-test', 'signature' => null];
            }
        });
        $this->admin = $this->superAdmin();
    }

    private function card(?User $person = null, string $type = 'staff'): array
    {
        $person ??= $this->admin;
        app(CardPortraits::class)->upload($this->admin, (int) $person->id, $type, (string) Str::uuid(), UploadedFile::fake()->image('synthetic-encoder.jpg', 120, 150));

        return app(Cards::class)->issue($this->admin, ['operation_id' => (string) Str::uuid(), 'person_id' => $person->id, 'type' => $type]);
    }

    private function reader(string $kind = 'encoder'): array
    {
        $private = random_bytes(32);
        $device = app(CardReaders::class)->register($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Synthetic controlled '.$kind, 'kind' => $kind,
            'public_key' => base64_encode(sodium_crypto_scalarmult_base($private)), 'days' => 1]);

        return $device + ['private' => $private];
    }

    private function prepare(?array $card = null, ?array $reader = null): array
    {
        $card ??= $this->card();
        $reader ??= $this->reader();
        $input = ['operation_id' => (string) Str::uuid(), 'device_id' => $reader['id'], 'uid' => '04112233445566'];
        $plan = app(CardEncoding::class)->prepare($this->admin, $card['id'], $input);

        return [$card, $reader, $plan, $input];
    }

    private function proof(CardChipProfile $profile, int $counter): array
    {
        $bytes = substr(pack('V', $counter), 0, 3);
        $plain = "\xC7".hex2bin($profile->uid).$bytes.random_bytes(5);
        $encrypted = openssl_encrypt($plain, 'aes-128-cbc', hex2bin($profile->metadata_key), OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, str_repeat("\0", 16));
        $session = app(SunVerifier::class)->sessionKey(hex2bin($profile->file_read_key), hex2bin($profile->uid), $bytes);

        return ['operation_id' => (string) Str::uuid(), 'picc' => strtoupper(bin2hex($encrypted)), 'mac' => strtoupper(bin2hex(app(AesCmac::class)->truncated($session, '')))];
    }

    private function refused(callable $action, array $codes = [401, 403, 404, 409, 422]): void
    {
        try {
            $action();
            $this->fail('Encoding authority was not refused.');
        } catch (ModelNotFoundException) {
            $this->assertContains(404, $codes);
        } catch (HttpExceptionInterface $error) {
            $this->assertContains($error->getStatusCode(), $codes);
        }
    }

    public function test_plan_is_immutable_device_encrypted_and_contains_only_independent_random_keys(): void
    {
        [$card, $reader, $plan, $input] = $this->prepare();
        $this->assertSame($plan, app(CardEncoding::class)->prepare($this->admin, $card['id'], $input));
        $this->assertSame(1, CardChipProfile::count());
        $this->assertSame(1, CardEvent::where('action', 'encoding_prepared')->count());
        $profile = CardChipProfile::sole();
        $stored = json_decode(Crypt::decryptString($profile->encoding_package), true, 12, JSON_THROW_ON_ERROR);
        $this->assertCount(5, array_unique($stored['keys']));
        foreach ($stored['keys'] as $material) {
            $this->assertSame(32, strlen($material));
            $this->assertNotSame(str_repeat('0', 32), $material);
        }
        $packet = app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']);
        $aad = json_encode(['suite' => $packet['suite']] + $packet['context'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $shared = sodium_crypto_scalarmult($reader['private'], base64_decode($packet['ephemeral_public_key'], true));
        $key = hash_hkdf('sha256', $shared, 32, $aad, base64_decode($packet['salt'], true));
        $plain = openssl_decrypt(base64_decode($packet['ciphertext'], true), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, base64_decode($packet['nonce'], true), base64_decode($packet['tag'], true), $aad);
        $payload = json_decode($plain, true, 12, JSON_THROW_ON_ERROR);
        $this->assertSame($stored['keys'], $payload['keys']);
        $this->assertFalse($payload['confirmed']);
        $this->assertArrayNotHasKey('prepared_by', $payload);
        $this->assertSame(256, strlen(base64_decode($payload['ndef'], true)));
        $raw = json_encode(DB::table('card_chip_profiles')->where('id', $profile->id)->first(), JSON_THROW_ON_ERROR);
        foreach ($stored['keys'] as $material) {
            $this->assertStringNotContainsString($material, $raw);
            $this->assertStringNotContainsString($material, json_encode($packet));
        }
        $this->assertStringNotContainsString($profile->uid, $raw);
        $this->refused(fn () => app(CardEncoding::class)->prepare($this->admin, $card['id'], array_replace($input, ['operation_id' => (string) Str::uuid()])), [409]);
        $this->assertNull($profile->encoded_at);
    }

    public function test_sun_confirmation_precedes_gate_access_and_exact_retry_has_one_encoding_effect(): void
    {
        [$card, $reader, $plan] = $this->prepare();
        $gate = $this->reader('gate');
        $profile = CardChipProfile::sole();
        $input = $this->proof($profile, 1);
        $this->refused(fn () => app(CardScans::class)->sun($gate['credential'], ['card_id' => $card['id']] + $input), [404]);
        $first = app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], $input);
        $this->assertSame($first, app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], $input));
        $this->assertTrue($first['encoded']);
        $this->assertFalse($first['collection_authorized']);
        $this->assertFalse($first['sign_in_authorized']);
        $this->assertSame(1, CardEvent::where('action', 'encoded')->count());
        $this->assertSame(0, CardScanLog::count());
        $this->assertNotNull($profile->fresh()->encoded_at);
        $fresh = $this->proof($profile->fresh(), 2);
        $scan = app(CardScans::class)->sun($gate['credential'], ['card_id' => $card['id']] + $fresh);
        $this->assertSame('accepted', $scan['outcome']);
        $this->assertSame(1, CardScanLog::count());
        $this->refused(fn () => app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], array_replace($input, ['operation_id' => (string) Str::uuid()])), [409]);
        $again = app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], $this->proof($profile->fresh(), 3));
        $this->assertSame($first['encoded_at'], $again['encoded_at']);
        $this->assertSame(1, CardEvent::where('action', 'encoded')->count());
    }

    public function test_first_valid_zero_counter_and_unbound_disabled_source_download_have_exact_outcomes(): void
    {
        [$card, $reader, $plan] = $this->prepare();
        $proof = $this->proof(CardChipProfile::sole(), 0);
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($reader['credential'])->withHeader('X-School-Id', '1')
            ->postJson('/api/v2/card-encoder/plans/'.$plan['plan_id'].'/confirm', $proof)->assertOk()->assertJsonPath('data.verified_counter', 0);
        config(['modules.cards.always_on' => false]);
        SchoolModule::updateOrCreate(['module' => 'cards'], ['in_package' => false, 'enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->actingAs($this->admin)->get('/school-cards/encoder-runtime')->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_invalid_proof_wrong_encoder_wrong_school_and_nonmanager_never_read_or_confirm_keys(): void
    {
        [$card, $reader, $plan] = $this->prepare();
        $other = $this->reader();
        $this->refused(fn () => app(CardEncoding::class)->download($other['credential'], $plan['plan_id']), [404]);
        $this->refused(fn () => app(CardEncoding::class)->download($this->reader('gate')['credential'], $plan['plan_id']), [403]);
        $bad = $this->proof(CardChipProfile::sole(), 1);
        $bad['mac'] = str_repeat('0', 16);
        $this->refused(fn () => app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], $bad), [403]);
        $outside = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->refused(fn () => app(CardEncoding::class)->prepare($outside, $card['id'], []), [403]);
        $this->refused(fn () => app(CurrentSchool::class)->run(2, fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id'])), [401]);
        $this->assertNull(CardChipProfile::sole()->encoded_at);
        $this->assertSame(0, CardScanLog::count());
    }

    public function test_card_freeze_or_original_permission_withdrawal_refuses_old_preparation_and_confirmation_receipts(): void
    {
        [$card, $reader, $plan, $input] = $this->prepare();
        $proof = $this->proof(CardChipProfile::sole(), 1);
        app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], $proof);
        app(Cards::class)->freeze($this->admin, $card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic withdrawal']);
        $this->refused(fn () => app(CardEncoding::class)->prepare($this->admin, $card['id'], $input));
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']));
        $this->refused(fn () => app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], $proof));
        $this->assertSame(1, CardEvent::where('action', 'encoded')->count());
    }

    public function test_fresh_permission_and_module_state_are_required_for_registered_encoder_keys(): void
    {
        [$card, $reader, $plan] = $this->prepare();
        $role = Role::where('name', 'Super Admin')->firstOrFail();
        $role->revokePermissionTo(CardAccess::ENCODE);
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']), [403]);
        $role->givePermissionTo(CardAccess::ENCODE);
        config(['modules.cards.always_on' => false]);
        SchoolModule::updateOrCreate(['module' => 'cards'], ['in_package' => true, 'enabled' => true, 'read_only' => true]);
        app(ModuleGate::class)->forget();
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']), [404]);
        $this->assertNull(CardChipProfile::sole()->encoded_at);
    }

    public function test_distinct_preparer_and_device_issuer_are_both_reauthorized_before_secret_delivery(): void
    {
        $issuer = User::findOrFail($this->schoolA->ids['tara_user']);
        $issuer->givePermissionTo([CardAccess::ENCODE, CardAccess::MANAGE, CardAccess::READERS]);
        $device = app(CardReaders::class)->register($issuer, ['operation_id' => (string) Str::uuid(), 'name' => 'Separately delegated encoder', 'kind' => 'encoder',
            'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]);
        [$card, $reader, $plan] = $this->prepare(null, $device);
        $role = Role::where('name', 'Super Admin')->firstOrFail();
        $role->revokePermissionTo(CardAccess::ENCODE);
        // The encoder issuer still has all three explicit permissions.
        app(CardReaders::class)->authenticate($reader['credential'], 'encoder', true);
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']), [403]);
        $role->givePermissionTo(CardAccess::ENCODE);
        $issuer->revokePermissionTo(CardAccess::ENCODE);
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']), [403]);
        $this->assertNull(CardChipProfile::sole()->encoded_at);
    }

    public function test_converted_facilitator_cannot_keep_encoder_authority_from_retained_staff_permissions(): void
    {
        $issuer = User::findOrFail($this->schoolA->ids['tara_user']);
        $issuer->givePermissionTo([CardAccess::ENCODE, CardAccess::MANAGE, CardAccess::READERS]);
        $device = app(CardReaders::class)->register($issuer, ['operation_id' => (string) Str::uuid(), 'name' => 'Former staff encoder', 'kind' => 'encoder',
            'public_key' => base64_encode(sodium_crypto_box_publickey(sodium_crypto_box_keypair())), 'days' => 1]);
        [$card, $reader, $plan] = $this->prepare(null, $device);
        SchoolMembership::where('user_id', $issuer->id)->update(['status' => 'ended']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $issuer->id, 'role' => 'Facilitator', 'status' => 'active', 'starts_on' => '2026-01-01']);
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']), [403]);
        $this->assertNull(CardChipProfile::sole()->encoded_at);
    }

    public function test_restoring_a_confirmed_card_never_revives_its_old_encoding_job_revision(): void
    {
        [$card, $reader, $plan] = $this->prepare();
        $profile = CardChipProfile::sole();
        app(CardEncoding::class)->confirm($reader['credential'], $plan['plan_id'], $this->proof($profile, 1));
        app(Cards::class)->freeze($this->admin, $card['id'], ['operation_id' => (string) Str::uuid(), 'reason' => 'Synthetic recovery test']);
        app(Cards::class)->restore($this->admin, $card['id'], ['reason' => 'Recovered and checked chip'] + $this->proof($profile->fresh(), 2));
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']), [403]);
        $this->assertSame(1, CardEvent::where('action', 'encoded')->count());
    }

    public function test_permission_retirement_during_plan_creation_rolls_back_all_key_material(): void
    {
        $card = $this->card();
        $reader = $this->reader();
        $role = Role::where('name', 'Super Admin')->firstOrFail();
        $event = 'eloquent.created: '.CardChipProfile::class;
        Event::listen($event, fn () => $role->revokePermissionTo(CardAccess::ENCODE));
        try {
            $this->refused(fn () => app(CardEncoding::class)->prepare($this->admin, $card['id'], [
                'operation_id' => (string) Str::uuid(), 'device_id' => $reader['id'], 'uid' => '04112233445566',
            ]), [403]);
        } finally {
            Event::forget($event);
        }
        $this->assertSame(0, CardChipProfile::count());
        $this->assertSame(0, CardEvent::where('action', 'encoding_prepared')->count());
    }

    public function test_guardian_withdrawal_invalidates_an_unconfirmed_child_plan(): void
    {
        $child = User::findOrFail($this->schoolA->ids['asha_user']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $parent->forceFill(['email_verified_at' => now()])->save();
        $learner = Students::findOrFail($this->schoolA->ids['asha']);
        Enrolment::create(['learner_id' => $learner->id, 'session_year_id' => $this->schoolA->ids['session_year'], 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31']);
        GuardianLink::updateOrCreate(['learner_id' => $learner->id, 'guardian_user_id' => $parent->id], ['status' => 'active', 'relationship' => 'father', 'can_approve' => true]);
        app(ApprovalService::class)->decide($parent, $learner, 'approve', config('identity.consent_version'));
        [$card, $reader, $plan] = $this->prepare($this->card($child, 'learner'));
        app(ApprovalService::class)->decide($parent, $learner, 'withdraw', config('identity.consent_version'));
        $this->refused(fn () => app(CardEncoding::class)->download($reader['credential'], $plan['plan_id']));
        $this->assertNull(CardChipProfile::sole()->encoded_at);
    }

    public function test_real_encoder_http_is_stateless_and_validates_all_refusals_without_flashing_private_inputs(): void
    {
        [$card, $reader, $plan] = $this->prepare();
        $this->app['auth']->forgetGuards();
        $request = $this->flushHeaders()->withToken($reader['credential'])->withHeader('X-School-Id', '1')->withHeader('Origin', 'http://localhost');
        $request->getJson('/api/v2/card-encoder/plans/'.$plan['plan_id'])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertHeader('Referrer-Policy', 'no-referrer');
        $proof = $this->proof(CardChipProfile::sole(), 1);
        $request->postJson('/api/v2/card-encoder/plans/'.$plan['plan_id'].'/confirm', $proof)->assertOk()->assertJsonPath('data.encoded', true)->assertJsonPath('data.collection_authorized', false);
        $request->postJson('/api/v2/card-encoder/plans/'.$plan['plan_id'].'/confirm', $proof)->assertOk()->assertJsonPath('data.verified_counter', 1);
        $request->withHeader('Accept', 'text/html')->post('/api/v2/card-encoder/plans/'.$plan['plan_id'].'/confirm?mac=private-query', [])->assertUnprocessable()->assertSessionMissing('_old_input');
        $request->post('/api/v2/card-encoder/plans/'.$plan['plan_id'].'/confirm', ['operation_id' => 'invalid'])->assertUnprocessable()->assertSessionMissing('_old_input')->assertJsonPath('error', true);
        $request->postJson('/api/v2/card-encoder/plans/'.$plan['plan_id'].'/confirm', $proof + ['unrecognized' => 'field'])->assertUnprocessable();
        $request->withHeader('X-School-Id', '2')->getJson('/api/v2/card-encoder/plans/'.$plan['plan_id'])->assertForbidden();
        $request->withHeader('X-School-Id', '1');
        $request->getJson('/api/v2/card-device/offline/snapshot?request_id='.Str::uuid())->assertForbidden();
        $request->getJson('/api/v2/foundation-learner/context')->assertUnauthorized();
        $request->getJson('/api/v2/cards/mine')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->flushHeaders()->withToken($this->admin->createToken('synthetic-user')->plainTextToken)->withHeader('X-School-Id', '1')->getJson('/api/v2/card-encoder/plans/'.$plan['plan_id'])->assertUnauthorized();
    }

    public function test_private_workbench_binds_current_account_before_fields_and_never_exports_plaintext_keys(): void
    {
        $card = $this->card();
        $reader = $this->reader();
        $query = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $path = '/school-cards/cards/'.$card['id'].'/encoding';
        $this->actingAs($this->admin)->get($path.'?'.http_build_query($query))->assertOk()->assertSee('Prepare encrypted chip plan')->assertSee('assets/js/card-encoding-workbench.js', false)->assertSee('assets/css/card-encoding.css', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->post($path, $query + ['operation_id' => (string) Str::uuid(), 'device_id' => $reader['id'], 'uid' => '04112233445566'])->assertRedirect();
        $page = $this->get($path.'?'.http_build_query($query))->assertOk()->assertSee('Awaiting verified chip personalization');
        $plan = json_decode(Crypt::decryptString(CardChipProfile::sole()->encoding_package), true, 12, JSON_THROW_ON_ERROR);
        foreach ($plan['keys'] as $material) {
            $page->assertDontSee($material, false);
        }
        $this->get($path.'?'.http_build_query(['expected_account_id' => $this->admin->id + 1, 'expected_school_id' => 1]))->assertForbidden();
        $this->post($path, ['expected_account_id' => $this->admin->id + 1, 'expected_school_id' => 1, 'uid' => 'private-must-not-validate'])->assertForbidden()->assertSessionMissing('_old_input');
        $this->post($path, $query + ['operation_id' => 'invalid', 'uid' => 'private-invalid'])->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->get('/school-cards/encoder-runtime?'.http_build_query($query))->assertOk()->assertHeader('Content-Type', 'application/zip');
        $this->get('/card-tap')->assertOk()->assertDontSee($plan['uid']);
    }

    public function test_encoding_permission_upgrade_preserves_custom_roles_and_invalid_public_keys_create_no_plans(): void
    {
        $role = Role::where('name', 'Super Admin')->firstOrFail();
        $role->forceFill(['custom_role' => 1])->save();
        $role->revokePermissionTo(CardAccess::ENCODE);
        $migration = require database_path('migrations/2026_10_26_005000_add_card_encoding_permission.php');
        $migration->up();
        $this->assertFalse($role->fresh()->hasPermissionTo(CardAccess::ENCODE));
        $role->givePermissionTo(CardAccess::ENCODE);
        $this->refused(fn () => app(CardReaders::class)->register($this->admin, ['operation_id' => (string) Str::uuid(), 'name' => 'Invalid synthetic key', 'kind' => 'encoder', 'public_key' => base64_encode(str_repeat("\0", 32)), 'days' => 1]), [422]);
        $this->assertSame(0, CardChipProfile::count());
    }
}
