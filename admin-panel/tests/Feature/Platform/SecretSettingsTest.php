<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Settings;
use App\Services\Payment\PaymentService;
use App\Services\Platform\Notifications\FcmClient;
use App\Services\Platform\Secrets\SecretSettings;
use App\Services\SettingsConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Secrets come from the environment (Secret Manager); the settings row is only
 * a fallback, the settings pages neither show nor save a secret the
 * environment provides, and secrets:audit-settings --purge clears the rows.
 */
class SecretSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const ROW = [
        'mail_password' => 'row-mail-pass',
        'stripe_secret_key' => 'sk_row_secret',
        'stripe_webhook_secret' => 'whsec_row_secret',
        'recaptcha_secret_key' => 'recaptcha-row-secret',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        // No secret in the environment unless a test sets one.
        foreach (SecretSettings::SECRETS as [$key]) {
            config([$key => null]);
        }
        foreach (self::ROW as $type => $value) {
            Settings::updateOrInsert(['type' => $type], ['message' => $value]);
        }
    }

    public function test_the_environment_wins_and_the_row_is_the_fallback(): void
    {
        foreach (self::ROW as $type => $value) {
            $this->assertFalse(SecretSettings::fromEnvironment($type));
            $this->assertSame($value, SecretSettings::get($type));
        }
        $this->assertSame('whsec_row_secret', PaymentService::webhookSecret('stripe'));

        config(['secrets.stripe_webhook_secret' => 'whsec_env', 'secrets.stripe_secret_key' => 'sk_env']);
        $this->assertSame('whsec_env', PaymentService::webhookSecret('stripe'));
        $this->assertSame('sk_env', SecretSettings::get('stripe_secret_key'));
    }

    public function test_the_mail_password_from_the_environment_is_not_overridden(): void
    {
        config(['secrets.mail_password' => 'env-mail-pass', 'mail.mailers.smtp.password' => 'env-mail-pass']);

        SettingsConfigService::apply();

        $this->assertSame('env-mail-pass', config('mail.mailers.smtp.password'));
    }

    public function test_settings_pages_neither_show_nor_save_a_secret_from_the_environment(): void
    {
        config([
            'secrets.stripe_secret_key' => 'sk_env', 'secrets.stripe_webhook_secret' => 'whsec_env',
            'secrets.mail_password' => 'env-mail-pass', 'mail.mailers.smtp.password' => 'env-mail-pass',
            'secrets.recaptcha_secret_key' => 'recaptcha-env',
        ]);
        $admin = $this->superAdmin();
        $admin->forceFill(['password' => bcrypt('Admin-pass-2026')])->save();

        foreach (['/fees-config', '/email-settings', '/settings'] as $page) {
            $html = $this->actingAs($admin)->get($page)->assertOk()->assertSee(trans('set_in_environment'))->getContent();
            foreach ([...array_values(self::ROW), 'sk_env', 'whsec_env', 'env-mail-pass', 'recaptcha-env'] as $secret) {
                $this->assertStringNotContainsString($secret, $html, "$page shows a secret");
            }
        }

        // Saving the fees page without the secret fields works, and a posted value is ignored.
        $this->actingAs($admin)->postJson('/fees-config/update', [
            'stripe_status' => 1, 'stripe_publishable_key' => 'pk_test', 'stripe_webhook_url' => 'https://school.example/webhook/stripe',
            'stripe_currency_code' => 'ZAR', 'currency_code' => 'ZAR', 'currency_symbol' => 'R',
            'compulsory_fee_payment_mode' => 0, 'is_student_can_pay_fees' => 1, 'stripe_secret_key' => 'sk_posted',
        ])->assertJson(['error' => false]);
        $this->assertSame('sk_row_secret', Settings::where('type', 'stripe_secret_key')->value('message'));

        $this->actingAs($admin)->postJson('/email-settings', [
            'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.org', 'mail_port' => 587, 'mail_username' => 'mailer',
            'mail_encryption' => 'tls', 'mail_send_from' => 'office@example.org', 'mail_password' => 'posted-pass',
        ])->assertJson(['error' => false]);
        $this->assertSame('row-mail-pass', Settings::where('type', 'mail_password')->value('message'));
    }

    public function test_without_the_environment_the_pages_work_as_before(): void
    {
        $admin = $this->superAdmin();
        $admin->forceFill(['password' => bcrypt('Admin-pass-2026')])->save();

        $this->actingAs($admin)->get('/fees-config')->assertOk()->assertSee('sk_row_secret')->assertDontSee(trans('set_in_environment'));
        $this->actingAs($admin)->postJson('/email-settings', [
            'mail_mailer' => 'smtp', 'mail_host' => 'smtp.example.org', 'mail_port' => 587, 'mail_username' => 'mailer',
            'mail_encryption' => 'tls', 'mail_send_from' => 'office@example.org',
        ])->assertJsonValidationErrors('mail_password');
    }

    public function test_the_fcm_service_account_can_come_from_the_environment(): void
    {
        $this->assertNull(SecretSettings::fcmCredentials());

        config(['secrets.fcm_service_account' => json_encode([
            'type' => 'service_account', 'project_id' => 'navigateed-test', 'private_key' => 'k', 'client_email' => 'x@example.iam.gserviceaccount.com',
        ])]);

        $this->assertSame('navigateed-test', SecretSettings::fcmCredentials()['project_id']);
        $this->assertSame('navigateed-test', SecretSettings::fcmProjectId());
        Settings::updateOrInsert(['type' => 'project_id'], ['message' => 'from-settings']);
        $this->assertSame('from-settings', SecretSettings::fcmProjectId());

        config(['secrets.fcm_service_account' => '{"not": "a service account"}']);
        $this->assertNull(SecretSettings::fcmCredentials());
    }

    public function test_the_push_helpers_read_the_fcm_service_account_through_secret_settings(): void
    {
        // With nothing configured the helper answers null (and push is reported
        // as not configured) instead of failing on how it looks the secret up.
        $this->assertNull(getAccessToken());
        $this->assertNull(app(FcmClient::class)->projectId());

        config(['secrets.fcm_service_account' => json_encode([
            'type' => 'service_account', 'project_id' => 'navigateed-test', 'private_key' => 'k', 'client_email' => 'x@example.iam.gserviceaccount.com',
        ])]);
        $this->assertSame('navigateed-test', app(FcmClient::class)->projectId());
    }

    public function test_the_audit_lists_rows_without_values_and_purge_clears_only_what_is_replaced(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('firebase/service_account.json', '{"private_key":"pk"}');
        Settings::updateOrInsert(['type' => 'service_account_file'], ['message' => 'firebase/service_account.json']);
        foreach (['razorpay_secret_key' => 'rzp_secret', 'paystack_secret_key' => 'ps_secret', 'flutterwave_hash_key' => 'flw_hash', 'razorpay_status' => '1'] as $type => $value) {
            Settings::updateOrInsert(['type' => $type], ['message' => $value]);
        }
        config(['secrets.stripe_secret_key' => 'sk_env', 'secrets.fcm_service_account' => '{"private_key":"k","client_email":"e"}']);

        $this->artisan('secrets:audit-settings')
            ->expectsOutputToContain('STRIPE_SECRET_KEY')
            ->doesntExpectOutputToContain('sk_row_secret')
            ->doesntExpectOutputToContain('rzp_secret')
            ->assertExitCode(0);
        $this->assertSame('sk_row_secret', Settings::where('type', 'stripe_secret_key')->value('message'), 'listing changes nothing');

        $this->artisan('secrets:audit-settings', ['--purge' => true])->assertExitCode(0);

        // Replaced by the environment: cleared, the Firebase file too.
        $this->assertFalse(Settings::where('type', 'stripe_secret_key')->exists());
        $this->assertFalse(Settings::where('type', 'service_account_file')->exists());
        Storage::disk('local')->assertMissing('firebase/service_account.json');
        // Removed gateways: every row cleared.
        $this->assertSame(0, Settings::where('type', 'like', 'razorpay_%')->orWhere('type', 'like', 'paystack_%')->orWhere('type', 'like', 'flutterwave_%')->count());
        // Still only in settings: kept, so nothing stops working.
        foreach (['mail_password', 'stripe_webhook_secret', 'recaptcha_secret_key'] as $type) {
            $this->assertSame(self::ROW[$type], Settings::where('type', $type)->value('message'), $type);
        }
        $this->assertSame('sk_env', SecretSettings::get('stripe_secret_key'));
    }
}
