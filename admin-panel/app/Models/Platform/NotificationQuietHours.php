<?php

declare(strict_types=1);

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

/**
 * A person's quiet hours for push (Action Plan 1.5). Global, not school-owned.
 *
 * @property int $user_id
 * @property bool $enabled
 * @property string $starts_at
 * @property string $ends_at
 * @property string $timezone
 */
class NotificationQuietHours extends Model
{
    protected $table = 'notification_quiet_hours';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'enabled' => 'boolean'];
    }
}
