<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One SES configuration set a school (or, with no school, the platform)
 * sends one stream of mail through (Action Plan 2.3). Written only by
 * MailStreams; always read by an explicit school id, never the ambient
 * school. engineering/specs/platform/ses-streams.md.
 *
 * @property int $id
 * @property int|null $school_id
 * @property string $stream
 * @property string $configuration_set
 * @property string $status
 * @property bool|null $sending_enabled
 * @property bool $engagement_tracking
 * @property bool $optimized_delivery
 * @property bool $event_destination
 * @property string|null $tenant_status
 * @property string|null $tenant_status_origin
 * @property string|null $tenant_status_cause
 * @property Carbon|null $tenant_status_at
 * @property string|null $last_error
 * @property Carbon|null $ready_at
 * @property Carbon|null $checked_at
 */
class MailStream extends Model
{
    public const PENDING = 'pending';

    public const READY = 'ready';

    public const FAILED = 'failed';

    protected $table = 'mail_streams';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'school_id' => 'integer',
            'sending_enabled' => 'boolean',
            'engagement_tracking' => 'boolean',
            'optimized_delivery' => 'boolean',
            'event_destination' => 'boolean',
            'tenant_status_at' => 'datetime',
            'ready_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }
}
