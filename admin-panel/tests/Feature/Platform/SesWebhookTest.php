<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\NotificationDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

/**
 * POST /webhook/mail/ses: SNS-signed SES feedback (bounces, complaints,
 * deliveries) updates notification_deliveries by SES message id, once per SNS
 * message, and only from our topic. Each test signs its fixtures with a key
 * pair made on the spot; the certificate download is faked.
 */
class SesWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOPIC = 'arn:aws:sns:af-south-1:123456789012:navigateed-ses-feedback';

    private const CERT_URL = 'https://sns.af-south-1.amazonaws.com/SimpleNotificationService-test.pem';

    private \OpenSSLAsymmetricKey $key;

    private string $certificate = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr = openssl_csr_new(['commonName' => 'sns.amazonaws.com'], $this->key, ['digest_alg' => 'sha256']);
        openssl_x509_export(openssl_csr_sign($csr, null, $this->key, 1, ['digest_alg' => 'sha256']), $this->certificate);

        config(['services.ses_notifications.topic_arns' => [self::TOPIC]]);
        Http::fake([self::CERT_URL => Http::response($this->certificate), 'https://sns.af-south-1.amazonaws.com/?Action=ConfirmSubscription*' => Http::response('<ConfirmSubscriptionResponse/>')]);
    }

    public function test_a_bounce_marks_the_delivery_bounced(): void
    {
        $row = $this->sentDelivery('0100018abc-bounce');

        $this->postSns($this->notification(['notificationType' => 'Bounce', 'mail' => ['messageId' => '0100018abc-bounce'],
            'bounce' => ['bounceType' => 'Permanent', 'bounceSubType' => 'General', 'bouncedRecipients' => [['emailAddress' => 'father@example.org']]]]))
            ->assertOk()->assertJson(['error' => false, 'message' => 'Recorded']);

        $row->refresh();
        $this->assertSame('bounced', $row->status);
        $this->assertSame('Bounce: Permanent/General', $row->error_summary);
        $this->assertNotNull($row->feedback_at);
    }

    public function test_complaints_and_deliveries_are_recorded_and_a_late_delivery_does_not_undo_a_bounce(): void
    {
        $complained = $this->sentDelivery('msg-complaint');
        $delivered = $this->sentDelivery('msg-delivery');
        $bounced = $this->sentDelivery('msg-bounced', 'bounced');

        $this->postSns($this->notification(['notificationType' => 'Complaint', 'mail' => ['messageId' => 'msg-complaint'], 'complaint' => ['complaintFeedbackType' => 'abuse']]))->assertOk();
        // Configuration set events name the type eventType.
        $this->postSns($this->notification(['eventType' => 'Delivery', 'mail' => ['messageId' => 'msg-delivery']]))->assertOk();
        $this->postSns($this->notification(['notificationType' => 'Delivery', 'mail' => ['messageId' => 'msg-bounced']]))->assertOk();

        $this->assertSame('complained', $complained->refresh()->status);
        $this->assertSame('Complaint: abuse', $complained->error_summary);
        $this->assertSame('delivered', $delivered->refresh()->status);
        $this->assertSame('bounced', $bounced->refresh()->status);
    }

    public function test_the_same_sns_message_is_processed_once(): void
    {
        $row = $this->sentDelivery('msg-once');
        $message = $this->notification(['notificationType' => 'Delivery', 'mail' => ['messageId' => 'msg-once']]);

        $this->postSns($message)->assertOk()->assertJson(['message' => 'Recorded']);
        $this->assertSame('delivered', $row->refresh()->status);
        $row->forceFill(['status' => 'sent'])->save();
        $this->postSns($message)->assertOk()->assertJson(['message' => 'Already processed']);

        $this->assertSame('sent', $row->refresh()->status);
        $this->assertSame(1, DB::table('webhook_receipts')->where('event_id', $message['MessageId'])->count());
    }

    public function test_an_unknown_message_id_or_type_is_accepted_and_changes_nothing(): void
    {
        $row = $this->sentDelivery('msg-known');

        $this->postSns($this->notification(['notificationType' => 'Bounce', 'mail' => ['messageId' => 'msg-unknown'], 'bounce' => ['bounceType' => 'Transient']]))->assertOk();
        $this->postSns($this->notification(['eventType' => 'Open', 'mail' => ['messageId' => 'msg-known']]))->assertOk()->assertJson(['message' => 'Ignored']);

        $this->assertSame('sent', $row->refresh()->status);
    }

    public function test_a_tampered_message_is_refused(): void
    {
        $row = $this->sentDelivery('msg-tampered');
        $message = $this->notification(['notificationType' => 'Bounce', 'mail' => ['messageId' => 'msg-other'], 'bounce' => ['bounceType' => 'Permanent']]);
        $message['Message'] = str_replace('msg-other', 'msg-tampered', $message['Message']);

        $this->postSns($message)->assertStatus(401)->assertJson(['error' => true]);

        $this->assertSame('sent', $row->refresh()->status);
        $this->assertSame(0, DB::table('webhook_receipts')->count());
    }

    public function test_a_certificate_from_anywhere_but_sns_is_refused_without_fetching_it(): void
    {
        $this->sentDelivery('msg-evil');
        $message = $this->notification(['notificationType' => 'Bounce', 'mail' => ['messageId' => 'msg-evil']], 'https://evil.example.com/SimpleNotificationService.pem');

        $this->postSns($message)->assertStatus(401);

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'evil.example.com'));
        $this->assertSame('sent', NotificationDelivery::sole()->status);
    }

    public function test_another_topic_is_refused_and_nothing_is_accepted_until_a_topic_is_configured(): void
    {
        $this->sentDelivery('msg-topic');
        $other = $this->notification(['notificationType' => 'Bounce', 'mail' => ['messageId' => 'msg-topic']], self::CERT_URL, 'arn:aws:sns:af-south-1:999999999999:someone-else');

        $this->postSns($other)->assertStatus(403);

        config(['services.ses_notifications.topic_arns' => []]);
        $this->postSns($this->notification(['notificationType' => 'Bounce', 'mail' => ['messageId' => 'msg-topic']]))->assertStatus(401);
        $this->assertSame('sent', NotificationDelivery::sole()->status);
    }

    public function test_a_subscription_request_is_confirmed_only_on_an_sns_host(): void
    {
        $confirm = $this->signed([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => (string) Str::uuid(),
            'Token' => 'token-123',
            'TopicArn' => self::TOPIC,
            'Message' => 'You have chosen to subscribe to the topic.',
            'SubscribeURL' => 'https://sns.af-south-1.amazonaws.com/?Action=ConfirmSubscription&TopicArn='.self::TOPIC.'&Token=token-123',
            'Timestamp' => now()->toIso8601ZuluString(),
        ]);
        $this->postSns($confirm)->assertOk()->assertJson(['message' => 'Subscription confirmed']);
        Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://sns.af-south-1.amazonaws.com/?Action=ConfirmSubscription'));

        $evil = $this->signed([
            'Type' => 'SubscriptionConfirmation',
            'MessageId' => (string) Str::uuid(),
            'Token' => 'token-456',
            'TopicArn' => self::TOPIC,
            'Message' => 'You have chosen to subscribe to the topic.',
            'SubscribeURL' => 'https://internal.example.com/admin',
            'Timestamp' => now()->toIso8601ZuluString(),
        ]);
        $this->postSns($evil)->assertStatus(400);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'internal.example.com'));
    }

    public function test_a_body_that_is_not_an_sns_message_is_refused(): void
    {
        $this->call('POST', '/webhook/mail/ses', [], [], [], ['CONTENT_TYPE' => 'text/plain'], 'not json')->assertStatus(400);
    }

    public function test_the_route_is_rate_limited_and_outside_csrf(): void
    {
        $route = Route::getRoutes()->getByName('webhook.mail.ses');

        $this->assertSame('webhook/mail/ses', $route->uri());
        $this->assertContains('throttle:mail-webhook', $route->gatherMiddleware());
        // bootstrap/app.php exempts webhook/* from CSRF; signatures authenticate them.
        $this->assertStringContainsString("'webhook/*'", (string) file_get_contents(base_path('bootstrap/app.php')));
    }

    public function test_the_ses_mailer_uses_cape_town_and_smtp_still_works(): void
    {
        $manager = app('mail.manager');

        $ses = $manager->mailer('ses')->getSymfonyTransport();
        $this->assertInstanceOf(SesTransport::class, $ses);
        $this->assertSame('af-south-1', $ses->ses()->getRegion());
        $this->assertInstanceOf(SesTransport::class, $manager->mailer('amazon_ses')->getSymfonyTransport());
        $this->assertInstanceOf(EsmtpTransport::class, $manager->mailer('smtp')->getSymfonyTransport());
    }

    private function sentDelivery(string $sesMessageId, string $status = 'sent'): NotificationDelivery
    {
        return NotificationDelivery::create([
            'delivery_key' => (string) Str::uuid(),
            'type' => 'mail.student_registration',
            'channel' => 'mail',
            'recipient_hash' => hash('sha256', 'father@example.org'),
            'status' => $status,
            'provider' => 'ses',
            'provider_message_id' => $sesMessageId,
            'attempt' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $ses  the SES notification
     * @return array<string, string>
     */
    private function notification(array $ses, string $certUrl = self::CERT_URL, string $topic = self::TOPIC): array
    {
        return $this->signed([
            'Type' => 'Notification',
            'MessageId' => (string) Str::uuid(),
            'TopicArn' => $topic,
            'Message' => json_encode($ses),
            'Timestamp' => now()->toIso8601ZuluString(),
        ], $certUrl);
    }

    /**
     * Signs a message the way SNS does (SignatureVersion 2: SHA256 over the
     * "Key\nValue\n" pairs of the signed keys, in this order).
     *
     * @param  array<string, string>  $message
     * @return array<string, string>
     */
    private function signed(array $message, string $certUrl = self::CERT_URL): array
    {
        $toSign = '';
        foreach (['Message', 'MessageId', 'Subject', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'] as $key) {
            if (isset($message[$key])) {
                $toSign .= $key."\n".$message[$key]."\n";
            }
        }
        openssl_sign($toSign, $signature, $this->key, OPENSSL_ALGO_SHA256);

        return $message + ['SignatureVersion' => '2', 'Signature' => base64_encode($signature), 'SigningCertURL' => $certUrl];
    }

    /** SNS posts JSON as text/plain. */
    private function postSns(array $message): \Illuminate\Testing\TestResponse
    {
        return $this->call('POST', '/webhook/mail/ses', [], [], [], ['CONTENT_TYPE' => 'text/plain; charset=UTF-8', 'HTTP_X_AMZ_SNS_MESSAGE_TYPE' => $message['Type']], json_encode($message));
    }
}
