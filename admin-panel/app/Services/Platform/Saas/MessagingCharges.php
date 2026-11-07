<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\PlatformInvoice;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Notifications\DeliveryLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The SMS and WhatsApp pass-through lines of a school's platform invoice
 * (Action Plan 2.7 with SA.8). The invoice for a month carries the texts the
 * school sent in the month before it (South African months), because a
 * month's texts are known only once it has ended. Each message counts once
 * (the attempt Twilio accepted), at the price Twilio charged in whole cents,
 * one line per channel. Only this school's deliveries are read.
 *
 * Twilio prices in its own currency (usually USD). A price in the invoice's
 * currency is billed as it is; another currency is converted at the rate in
 * config/platform_billing.php (messaging.exchange_rates, rands per unit as a
 * decimal string), rounded half up once per currency on the line, and the
 * rate is written on the line. Without a rate, or while a message of the
 * month may still be priced by the hourly lookup, the draft is blocked until
 * it is recomputed. Integer arithmetic only.
 */
class MessagingCharges
{
    /** Rates are read to millionths. */
    private const RATE_SCALE = 1000000;

    /**
     * A school that never texted may already have an early-issued invoice.
     * Its usage month cannot receive new costs after the invoice was sealed.
     * Called while holding the school's account lock, also used by issuance.
     */
    public function activationProblem(int $schoolId): ?string
    {
        $sealed = DB::table('platform_invoices')->where('school_id', $schoolId)
            ->where('kind', PlatformInvoice::INVOICE)
            ->whereNotIn('status', [PlatformInvoice::DRAFT, PlatformInvoice::DISCARDED])
            ->where('period_start', '>', CarbonImmutable::now(PlatformBilling::TIMEZONE)->startOfMonth()->toDateString())
            ->orderByDesc('period_start')->lockForUpdate()->first(['period_start']);

        return $sealed === null ? null : __('Texting can be switched on from :date because an invoice covering its usage month has already been issued.', ['date' => $sealed->period_start]);
    }

    /**
     * @return array{lines: list<array<string, mixed>>, blocked: string|null}
     */
    public function forInvoice(int $schoolId, string $periodStart, string $currency): array
    {
        $usage = self::usageMonth($periodStart);
        $label = $usage->format('F Y');
        // Prepared before its texting month has ended (Finance may draft next month early): the
        // lines so far are shown, but the draft cannot be issued until it is recomputed after.
        $open = $this->unclosed($schoolId, $periodStart, CarbonImmutable::now());
        $zone = (string) config('app.timezone');
        $rows = DB::table('notification_deliveries')
            ->where('school_id', $schoolId)
            ->whereIn('channel', [DeliveryLog::SMS, DeliveryLog::WHATSAPP])
            ->whereNotNull('provider_message_id')
            ->where('sent_at', '>=', $usage->setTimezone($zone))->where('sent_at', '<', $usage->addMonth()->setTimezone($zone))
            ->groupBy('channel', 'price_currency')
            ->orderBy('channel')->orderBy('price_currency')
            ->get([
                'channel', 'price_currency',
                DB::raw('COUNT(*) as messages'),
                DB::raw('COALESCE(SUM(segments), 0) as segments'),
                DB::raw('COALESCE(SUM(price_cents), 0) as cents'),
                DB::raw('SUM(CASE WHEN price_cents IS NULL THEN 1 ELSE 0 END) as unpriced'),
            ]);
        if ($rows->isEmpty()) {
            return ['lines' => [], 'blocked' => $open];
        }

        $waiting = (int) DB::table('notification_deliveries')->where('school_id', $schoolId)
            ->whereIn('channel', [DeliveryLog::SMS, DeliveryLog::WHATSAPP])->whereNotNull('provider_message_id')->whereNull('price_cents')
            ->where('sent_at', '>=', $usage->setTimezone($zone))->where('sent_at', '<', $usage->addMonth()->setTimezone($zone))
            ->where('sent_at', '>=', now()->subDays((int) config('urgent_messaging.price_lookup.within_days', 7)))
            ->count();
        $blocked = $open ?? ($waiting > 0
            ? trans_choice(':count SMS or WhatsApp message sent in :month has no Twilio price yet. Recompute once the hourly price lookup has priced it.|:count SMS and WhatsApp messages sent in :month have no Twilio price yet. Recompute once the hourly price lookup has priced them.', $waiting, ['count' => $waiting, 'month' => $label])
            : null);

        $lines = [];
        foreach ($rows->groupBy('channel') as $channel => $byCurrency) {
            $amount = 0;
            $messages = 0;
            $segments = 0;
            $unpriced = 0;
            $prices = [];
            foreach ($byCurrency as $row) {
                $messages += (int) $row->messages;
                $segments += (int) $row->segments;
                $unpriced += (int) $row->unpriced;
                $from = strtoupper((string) $row->price_currency);
                $cents = (int) $row->cents;
                if ($from === '' || $cents === 0) {
                    continue;
                }
                if ($from === strtoupper($currency)) {
                    $amount += $cents;
                    $prices[] = Money::ofCents($cents, $from)->format();

                    continue;
                }
                $rate = $this->rate($from);
                if ($rate === null) {
                    $blocked ??= __('SMS and WhatsApp messages sent in :month were charged in :from and no exchange rate from :from is set. Set it in the billing configuration and recompute.', ['month' => $label, 'from' => $from]);

                    continue;
                }
                $converted = Money::ofFraction($cents * $rate, self::RATE_SCALE)->cents;
                $amount += $converted;
                $prices[] = __(':price at :rate per :from', ['price' => Money::ofCents($cents, $from)->format(), 'rate' => $this->rateLabel($rate), 'from' => $from]);
            }
            $description = trans_choice(':channel in :month: :count message (segments: :segments)|:channel in :month: :count messages (segments: :segments)', $messages, [
                'channel' => $channel === DeliveryLog::SMS ? 'SMS' : 'WhatsApp', 'month' => $label, 'count' => $messages, 'segments' => $segments,
            ]);
            if ($prices !== []) {
                $description .= ', '.implode('; ', $prices);
            }
            if ($unpriced > 0) {
                $description .= '; '.trans_choice(':count not priced by Twilio|:count not priced by Twilio', $unpriced, ['count' => $unpriced]);
            }
            $lines[] = ['kind' => 'messaging', 'product_module' => null, 'learners' => null, 'unit_cents' => null,
                'description' => mb_substr($description, 0, 191), 'amount_cents' => $amount];
        }

        return ['lines' => $lines, 'blocked' => $blocked];
    }

    /** The first moment of the month whose texts an invoice for $periodStart's month carries, in SA time. */
    public static function usageMonth(string $periodStart): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $periodStart, PlatformBilling::TIMEZONE)->startOfMonth()->subMonth();
    }

    /**
     * Why a draft cannot be issued yet because of its texts, or null: its
     * texting month had not ended (South African time) when it was last
     * computed, so texts sent later in that month are missing. Checked again
     * at issue time, so a school that switched texting on after the draft was
     * made is caught too. A school that never opted in and sent nothing that
     * month is not held back.
     */
    public function issueProblem(int $schoolId, string $periodStart, ?\DateTimeInterface $computedAt): ?string
    {
        return $this->unclosed($schoolId, $periodStart, $computedAt === null ? null : CarbonImmutable::instance($computedAt));
    }

    private function unclosed(int $schoolId, string $periodStart, ?CarbonImmutable $computedAt): ?string
    {
        $usage = self::usageMonth($periodStart);
        $closes = $usage->addMonth();
        if ($computedAt !== null && $computedAt->greaterThanOrEqualTo($closes)) {
            return null;
        }
        $texts = DB::table('school_messaging_settings')->where('school_id', $schoolId)->exists()
            || DB::table('notification_deliveries')->where('school_id', $schoolId)->whereIn('channel', [DeliveryLog::SMS, DeliveryLog::WHATSAPP])
                ->where('sent_at', '>=', $usage->setTimezone((string) config('app.timezone')))->exists();
        if (! $texts) {
            return null;
        }

        return __('SMS and WhatsApp for :month are billed once the month has ended. Recompute this draft on or after :date, then issue it.', [
            'month' => $usage->format('F Y'), 'date' => $closes->format('j F Y'),
        ]);
    }

    /** Rands per unit of $currency in millionths, or null when no rate is set. */
    public function rate(string $currency): ?int
    {
        $value = trim((string) (((array) config('platform_billing.messaging.exchange_rates', []))[strtoupper($currency)] ?? ''));
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{1,4})(?:\.(\d{1,6}))?$/D', $value, $match) !== 1) {
            throw new RuntimeException('platform_billing.messaging.exchange_rates must be decimal strings such as 18.25.');
        }
        $rate = (int) $match[1] * self::RATE_SCALE + (int) str_pad($match[2] ?? '0', 6, '0');

        return $rate > 0 ? $rate : null;
    }

    /** "R 18.2500" for a rate in millionths, without floating point. */
    private function rateLabel(int $rate): string
    {
        $fraction = str_pad((string) ($rate % self::RATE_SCALE), 6, '0', STR_PAD_LEFT);

        return 'R '.intdiv($rate, self::RATE_SCALE).'.'.(rtrim(substr($fraction, 4), '0') === '' ? substr($fraction, 0, 4) : $fraction);
    }
}
