<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\NotificationDelivery;
use App\Services\Platform\Notifications\DeliveryLog;
use App\Services\Platform\Notifications\TwilioClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Twilio prices a message after it is sent, and its status callbacks do not
 * carry the price, so this asks Twilio for the price and segments of each
 * recently sent SMS or WhatsApp message that has none yet (Action Plan 2.7,
 * pass-through pricing). Scheduled hourly.
 */
class FetchMessagePrices extends Command
{
    protected $signature = 'messaging:fetch-prices';

    protected $description = 'Record Twilio\'s price and segments for recently sent SMS and WhatsApp messages';

    public function handle(TwilioClient $twilio): int
    {
        if (! filled(config('services.twilio.account_sid')) || ! filled(config('services.twilio.auth_token'))) {
            $this->info('Twilio is not configured.');

            return self::SUCCESS;
        }
        $lookup = (array) config('urgent_messaging.price_lookup');
        $rows = NotificationDelivery::query()
            ->whereIn('channel', [DeliveryLog::SMS, DeliveryLog::WHATSAPP])
            ->where('provider', TwilioClient::PROVIDER)->whereNotNull('provider_message_id')->whereNull('price_cents')
            ->where('sent_at', '<=', now()->subMinutes((int) ($lookup['after_minutes'] ?? 10)))
            ->where('sent_at', '>=', now()->subDays((int) ($lookup['within_days'] ?? 7)))
            // Least recently looked-up first: a lookup that finds no price
            // touches the row, so it moves behind the rest of the batch.
            ->orderBy('updated_at')->orderBy('id')->limit((int) ($lookup['batch'] ?? 200))->get();

        $priced = 0;
        foreach ($rows as $row) {
            $message = $twilio->fetch((string) $row->provider_message_id);
            if ($message === null || $message['price_cents'] === null) {
                $row->touch();

                continue;
            }
            DeliveryLog::charged($row, $message['segments'], $message['price_cents'], $message['currency']);
            $priced++;
        }
        Log::info('messaging_prices_fetched', ['checked' => $rows->count(), 'priced' => $priced]);
        $this->info("Priced {$priced} of {$rows->count()} messages.");

        return self::SUCCESS;
    }
}
