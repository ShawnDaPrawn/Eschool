<?php

declare(strict_types=1);

namespace App\Services\Live;

use App\Jobs\Live\DispatchLiveOutbox;
use App\Models\Platform\OutboxEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Best-effort acceleration; the transactional event remains the recovery source. */
class LiveDispatch
{
    public const EVENTS = ['house_points.changed', 'recognition.changed', 'consultations.counts_changed', 'activity.group_changed'];

    public static function afterCommit(OutboxEvent $event): void
    {
        if (! config('live.enabled', false) || ! in_array($event->type, self::EVENTS, true)) {
            return;
        }
        $id = (int) $event->id;
        DB::afterCommit(static function () use ($id) {
            try {
                // A dedicated queue avoids waiting behind ordinary mail/conversion work.
                DispatchLiveOutbox::dispatch($id)->onConnection('database')->onQueue('live');
            } catch (Throwable $error) {
                Log::warning('live_outbox_acceleration_failed', ['exception' => get_class($error)]);
            }
        });
    }
}
