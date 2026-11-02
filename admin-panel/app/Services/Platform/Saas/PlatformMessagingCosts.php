<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Notifications\DeliveryLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * HQ: each school's SMS and WhatsApp messages and their pass-through cost for
 * a month (Action Plan 2.7). Counts and sums only: no number, person or text.
 * A message is counted once, on the attempt Twilio accepted; prices are what
 * Twilio charged, in whole cents of its currency, once Twilio has reported it.
 */
class PlatformMessagingCosts
{
    public const TIMEZONE = 'Africa/Johannesburg';

    /**
     * @return array{month: string, label: string, rows: list<array<string, mixed>>, totals: array<string, array<string, mixed>>}
     */
    public function month(?string $month = null): array
    {
        $start = $month !== null && preg_match('/^\d{4}-\d{2}$/', $month)
            ? CarbonImmutable::createFromFormat('!Y-m', $month, self::TIMEZONE)
            : CarbonImmutable::now(self::TIMEZONE)->startOfMonth();
        $start = $start->startOfMonth();
        $end = $start->addMonth();

        $rows = DB::table('notification_deliveries as d')
            ->leftJoin('schools as s', 's.id', '=', 'd.school_id')
            ->whereIn('d.channel', [DeliveryLog::SMS, DeliveryLog::WHATSAPP])
            ->whereNotNull('d.provider_message_id')
            ->where('d.sent_at', '>=', $start->setTimezone((string) config('app.timezone')))->where('d.sent_at', '<', $end->setTimezone((string) config('app.timezone')))
            ->groupBy('d.school_id', 's.name', 'd.channel', 'd.price_currency')
            ->orderBy('s.name')->orderBy('d.channel')
            ->get([
                'd.school_id', 's.name as school', 'd.channel', 'd.price_currency',
                DB::raw('COUNT(*) as messages'),
                DB::raw('COALESCE(SUM(d.segments), 0) as segments'),
                DB::raw('COALESCE(SUM(d.price_cents), 0) as cost_cents'),
                DB::raw('SUM(CASE WHEN d.price_cents IS NULL THEN 1 ELSE 0 END) as unpriced'),
                DB::raw("SUM(CASE WHEN d.status = '".NotificationDelivery::DELIVERED."' THEN 1 ELSE 0 END) as delivered"),
                DB::raw("SUM(CASE WHEN d.status IN ('".NotificationDelivery::FAILED."', '".NotificationDelivery::UNDELIVERED."') THEN 1 ELSE 0 END) as failed"),
            ]);

        $schools = [];
        $totals = [];
        foreach ($rows as $row) {
            $id = (int) $row->school_id;
            $schools[$id] ??= ['school_id' => $id, 'school' => (string) ($row->school ?? '#'.$id), 'channels' => []];
            $channel = (string) $row->channel;
            $currency = $row->price_currency === null ? '' : (string) $row->price_currency;
            $line = $schools[$id]['channels'][$channel] ?? ['messages' => 0, 'segments' => 0, 'delivered' => 0, 'failed' => 0, 'unpriced' => 0, 'cost' => []];
            $line['messages'] += (int) $row->messages;
            $line['segments'] += (int) $row->segments;
            $line['delivered'] += (int) $row->delivered;
            $line['failed'] += (int) $row->failed;
            $line['unpriced'] += (int) $row->unpriced;
            if ($currency !== '') {
                $line['cost'][$currency] = ($line['cost'][$currency] ?? 0) + (int) $row->cost_cents;
            }
            $schools[$id]['channels'][$channel] = $line;

            $total = $totals[$channel] ?? ['messages' => 0, 'segments' => 0, 'unpriced' => 0, 'cost' => []];
            $total['messages'] += (int) $row->messages;
            $total['segments'] += (int) $row->segments;
            $total['unpriced'] += (int) $row->unpriced;
            if ($currency !== '') {
                $total['cost'][$currency] = ($total['cost'][$currency] ?? 0) + (int) $row->cost_cents;
            }
            $totals[$channel] = $total;
        }

        return ['month' => $start->format('Y-m'), 'label' => $start->format('F Y'), 'rows' => array_values($schools), 'totals' => $totals];
    }

    /** Whole cents as "USD 12.34". */
    public static function money(array $cost): string
    {
        if ($cost === []) {
            return '-';
        }
        $parts = [];
        foreach ($cost as $currency => $cents) {
            $parts[] = $currency.' '.intdiv((int) $cents, 100).'.'.str_pad((string) ((int) $cents % 100), 2, '0', STR_PAD_LEFT);
        }

        return implode(', ', $parts);
    }
}
