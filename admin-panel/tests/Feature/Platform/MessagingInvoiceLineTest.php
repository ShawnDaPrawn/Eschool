<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\NotificationDelivery;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformInvoice;
use App\Models\Platform\PlatformInvoiceLine;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMessagingSetting;
use App\Models\User;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Saas\MessagingCharges;
use App\Services\Platform\Saas\PlatformBilling;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The SMS and WhatsApp pass-through line on Navigate Ed's invoice to a school
 * (Action Plan 2.7 with SA.8): the invoice for a month carries the texts the
 * school sent in the month before, at what Twilio charged, in integer cents,
 * converted at the configured rate, once, and never another school's texts.
 * engineering/specs/platform/sms-whatsapp.md.
 */
class MessagingInvoiceLineTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        DB::table('school_memberships')->where('starts_on', '>', '2026-01-01')->update(['starts_on' => '2026-01-01']);
        $this->travelTo(CarbonImmutable::parse('2026-11-03 10:00', 'Africa/Johannesburg'));
        app(CurrentSchool::class)->forget();
        config(['platform_billing.messaging.exchange_rates' => ['USD' => '']]);
    }

    private function finance(): User
    {
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => 'Finance', 'last_name' => 'Operator', 'email' => 'finance@messaging-billing.example', 'mobile' => '', 'password' => 'Synthetic-billing-password-731!', 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => 'finance', 'expires_at' => now()->addYear()]);

        return $user;
    }

    /** A text Twilio accepted, sent at a South African time. */
    private function text(int $school, string $sentAt, ?int $cents, string $channel = 'sms', string $currency = 'USD', int $segments = 1, bool $accepted = true): NotificationDelivery
    {
        return NotificationDelivery::query()->create([
            'school_id' => $school, 'delivery_key' => (string) Str::uuid(), 'type' => $channel.'.comms.urgent', 'channel' => $channel,
            'recipient_user_id' => null, 'recipient_hash' => DeliveryLog::hash('+27821234567'), 'status' => $accepted ? 'delivered' : 'failed',
            'provider' => 'twilio', 'provider_message_id' => $accepted ? 'SM'.Str::random(32) : null, 'attempt' => 1,
            'queued_at' => now(), 'sent_at' => CarbonImmutable::parse($sentAt, 'Africa/Johannesburg')->setTimezone((string) config('app.timezone')),
            'segments' => $segments, 'price_cents' => $cents, 'price_currency' => $cents === null ? null : $currency,
        ]);
    }

    private function invoice(int $school, string $slot = '2026-11'): PlatformInvoice
    {
        return PlatformInvoice::acrossSchools()->where('school_id', $school)->where('slot', $slot)->sole();
    }

    /** @return list<array<string, mixed>> */
    private function textLines(int $school, string $slot = '2026-11'): array
    {
        return PlatformInvoiceLine::acrossSchools()->where('invoice_id', $this->invoice($school, $slot)->id)->where('kind', 'messaging')->orderBy('position')
            ->get(['description', 'amount_cents', 'vat_cents'])->map(fn ($line) => $line->only(['description', 'amount_cents', 'vat_cents']))->all();
    }

    public function test_the_invoice_carries_last_months_texts_once_in_cents_and_never_another_schools(): void
    {
        $finance = $this->finance();
        app(CurrentSchool::class)->run(1, fn () => DB::table('school_contracts')->insert(['school_id' => 1, 'starts_on' => '2026-01-01', 'contracted_learners' => 400,
            'price_per_learner_cents' => 24500, 'signatory' => 'Synthetic signatory', 'operator_agreement_version' => 'v1', 'operator_agreement_signed_on' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]));
        DB::table('platform_prices')->insert(['package_id' => DB::table('platform_packages')->where('package_key', 'legacy')->value('id'), 'kind' => 'base',
            'product_module' => null, 'amount_cents' => 5000, 'effective_from' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]);

        // School A in October (South African time), and texts that are not October's or were never accepted.
        $this->text(1, '2026-10-10 12:00', 8, segments: 2);
        $this->text(1, '2026-10-31 23:30', 8);
        $waiting = $this->text(1, '2026-10-30 09:00', null);
        $this->text(1, '2026-10-02 09:00', null);
        $this->text(1, '2026-10-15 09:00', 1, 'whatsapp');
        $this->text(1, '2026-11-01 00:30', 50);
        $this->text(1, '2026-09-30 23:00', 99);
        $this->text(1, '2026-10-12 09:00', null, accepted: false);
        // School B in October: one in USD, one Twilio priced in rand.
        $this->text(2, '2026-10-20 09:00', 24);
        $this->text(2, '2026-10-21 09:00', 300, currency: 'ZAR');

        // No exchange rate yet, and one School A message still waiting for its price: both drafts say why and cannot be issued.
        app(PlatformBilling::class)->run(['period' => '2026-11'], $finance);
        $this->assertStringContainsString('1 SMS or WhatsApp message sent in October 2026 has no Twilio price yet', (string) $this->invoice(1)->blocked_reason);
        $this->assertStringContainsString('charged in USD and no exchange rate from USD is set', (string) $this->invoice(2)->blocked_reason);

        // The price arrives and the rate is set: recomputing bills them.
        $waiting->forceFill(['price_cents' => 8, 'price_currency' => 'USD'])->save();
        config(['platform_billing.messaging.exchange_rates' => ['USD' => '18.50']]);
        app(PlatformBilling::class)->recompute(1, $this->invoice(1)->id, $finance);
        app(PlatformBilling::class)->recompute(2, $this->invoice(2)->id, $finance);
        $this->assertNull($this->invoice(1)->blocked_reason);
        $this->assertNull($this->invoice(2)->blocked_reason);

        $this->assertSame([
            // 24 US cents at R 18.50 is R 4.44; VAT 66.6 cents rounds to 67.
            ['description' => 'SMS in October 2026: 4 messages (segments: 5), USD 0.24 at R 18.5000 per USD; 1 not priced by Twilio', 'amount_cents' => 444, 'vat_cents' => 67],
            // 1 US cent at R 18.50 is 18.5 cents, rounded half up to 19; VAT 2.85 rounds to 3.
            ['description' => 'WhatsApp in October 2026: 1 message (segments: 1), USD 0.01 at R 18.5000 per USD', 'amount_cents' => 19, 'vat_cents' => 3],
        ], $this->textLines(1));
        // School B: only its own two texts, the rand price passed on as it is.
        $this->assertSame([
            ['description' => 'SMS in October 2026: 2 messages (segments: 2), USD 0.24 at R 18.5000 per USD; ZAR 3.00', 'amount_cents' => 744, 'vat_cents' => 112],
        ], $this->textLines(2));
        $a = $this->invoice(1);
        $lines = PlatformInvoiceLine::acrossSchools()->where('invoice_id', $a->id)->get();
        $this->assertSame((int) $lines->sum('amount_cents'), (int) $a->subtotal_cents);
        $this->assertSame((int) $lines->sum('vat_cents'), (int) $a->vat_cents);
        $this->assertSame(['base', 'messaging', 'messaging'], $lines->sortBy('position')->pluck('kind')->values()->all());

        // Recomputing or running again bills the month's texts once.
        app(PlatformBilling::class)->recompute(1, $a->id, $finance);
        $this->assertSame(['created' => 0, 'existing' => 2, 'skipped' => 0], app(PlatformBilling::class)->run(['period' => '2026-11'], $finance));
        $this->assertCount(2, $this->textLines(1));
        $this->assertSame(463, array_sum(array_column($this->textLines(1), 'amount_cents')));

        // Issued, it shows on the invoice and the statement; a late October price changes nothing issued.
        app(PlatformBilling::class)->issue(1, $a->id, $finance);
        $this->signInAs($finance)->get('/saas/billing/schools/1')->assertOk()->assertSee('includes SMS and WhatsApp: R 4.63 excluding VAT');
        $this->get('/saas/billing/schools/1/invoices/'.$a->id)->assertOk()->assertSee('SMS in October 2026')->assertSee('USD 0.24 at R 18.5000 per USD')
            ->assertSee('pass on, at cost, what Twilio charged')->assertDontSee('+27821234567');
        $this->get('/saas/billing/schools/2')->assertOk()->assertDontSee('includes SMS and WhatsApp');
        $this->text(1, '2026-10-29 09:00', 40);
        $this->assertSame(463, array_sum(array_column($this->textLines(1), 'amount_cents')));

        // December's invoice carries November's texts only.
        app(PlatformBilling::class)->run(['period' => '2026-12'], $finance);
        $this->assertSame([['description' => 'SMS in November 2026: 1 message (segments: 1), USD 0.50 at R 18.5000 per USD', 'amount_cents' => 925, 'vat_cents' => 139]], $this->textLines(1, '2026-12'));
        $this->assertSame([], $this->textLines(2, '2026-12'));
    }

    public function test_next_months_invoice_prepared_early_cannot_be_issued_until_its_texting_month_has_closed_and_it_is_recomputed(): void
    {
        $finance = $this->finance();
        config(['platform_billing.messaging.exchange_rates' => ['USD' => '18.50']]);
        app(CurrentSchool::class)->run(1, fn () => DB::table('school_contracts')->insert(['school_id' => 1, 'starts_on' => '2026-01-01', 'contracted_learners' => 400,
            'price_per_learner_cents' => 24500, 'signatory' => 'Synthetic signatory', 'operator_agreement_version' => 'v1', 'operator_agreement_signed_on' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]));
        DB::table('platform_prices')->insert(['package_id' => DB::table('platform_packages')->where('package_key', 'legacy')->value('id'), 'kind' => 'base',
            'product_module' => null, 'amount_cents' => 5000, 'effective_from' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]);
        // School A texts (it has opted in); School B never has.
        app(CurrentSchool::class)->run(1, fn () => SchoolMessagingSetting::query()->create(['sms_enabled' => true, 'sms_types' => ['comms.urgent'], 'whatsapp_enabled' => false, 'whatsapp_types' => [], 'revision' => 1]));
        $this->text(1, '2026-11-02 09:00', 8);

        // On 3 November Finance prepares December's invoices, whose texting month (November) is still running.
        app(PlatformBilling::class)->run(['period' => '2026-12'], $finance);
        $december = $this->invoice(1, '2026-12');
        $this->assertStringContainsString('November 2026', (string) $december->blocked_reason);
        $this->assertNotNull(app(PlatformBilling::class)->issueProblem($december));
        $this->assertSame(['issued' => 1, 'left' => 1, 'skipped' => 0, 'changed' => 0], app(PlatformBilling::class)->issueMonth(['period' => '2026-12'], $finance));
        $this->assertTrue($this->invoice(1, '2026-12')->isDraft(), 'A school that texts is left as a draft.');
        $this->assertTrue($this->invoice(2, '2026-12')->isIssued(), 'A school that never texts is billed as before.');

        // More texts later in November; a draft computed before the month closed is refused even without a stored reason.
        $this->text(1, '2026-11-20 09:00', 16);
        PlatformInvoice::acrossSchools()->whereKey($december->id)->update(['blocked_reason' => null]);
        $this->travelTo(CarbonImmutable::parse('2026-12-01 08:00', 'Africa/Johannesburg'));
        $this->assertNotNull(app(PlatformBilling::class)->issueProblem($this->invoice(1, '2026-12')));
        try {
            app(PlatformBilling::class)->issue(1, $december->id, $finance);
            $this->fail('A draft computed before its texting month closed was issued.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Recompute', $e->errors()['invoice'][0]);
        }

        // Recomputed after the month closed, it carries every November text and can be issued.
        app(PlatformBilling::class)->recompute(1, $december->id, $finance);
        $this->assertNull($this->invoice(1, '2026-12')->blocked_reason);
        $this->assertSame(444, array_sum(array_column($this->textLines(1, '2026-12'), 'amount_cents')));
        app(PlatformBilling::class)->issue(1, $december->id, $finance);
        $this->assertTrue($this->invoice(1, '2026-12')->isIssued());
    }

    public function test_rates_are_read_without_floating_point_and_a_bad_rate_is_refused(): void
    {
        $charges = app(MessagingCharges::class);
        config(['platform_billing.messaging.exchange_rates' => ['USD' => '18.123456']]);
        $this->assertSame(18123456, $charges->rate('usd'));
        config(['platform_billing.messaging.exchange_rates' => ['USD' => '']]);
        $this->assertNull($charges->rate('USD'));
        $this->assertNull($charges->rate('EUR'));
        config(['platform_billing.messaging.exchange_rates' => ['USD' => '18,50']]);
        $this->expectException(\RuntimeException::class);
        $charges->rate('USD');
    }
}
