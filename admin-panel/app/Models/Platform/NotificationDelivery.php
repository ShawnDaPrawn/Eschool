<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

/**
 * One attempt to deliver one mail or push message. Written only through
 * App\Services\Platform\Notifications\DeliveryLog.
 *
 * @property int $id
 * @property string $delivery_key
 * @property string $type
 * @property string $channel
 * @property int|null $recipient_user_id
 * @property string|null $recipient_hash
 * @property string $status
 * @property string|null $provider
 * @property string|null $provider_message_id
 * @property int $attempt
 * @property string|null $error_summary
 */
class NotificationDelivery extends Model
{
    public const QUEUED = 'queued';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const DELIVERED = 'delivered';

    public const BOUNCED = 'bounced';

    public const COMPLAINED = 'complained';

    protected $guarded = ['id'];

    protected $casts = [
        'school_id' => 'integer',
        'recipient_user_id' => 'integer',
        'attempt' => 'integer',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'feedback_at' => 'datetime',
    ];
}
