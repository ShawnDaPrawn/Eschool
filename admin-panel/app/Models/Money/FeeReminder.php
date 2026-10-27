<?php

declare(strict_types=1);

namespace App\Models\Money;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $fee_account_id
 * @property int $recipient_id
 * @property int|null $notification_id
 * @property Carbon $issued_on
 */
class FeeReminder extends Model
{
    use BelongsToSchool;

    protected $table = 'fee_reminders';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['fee_account_id' => 'integer', 'recipient_id' => 'integer', 'notification_id' => 'integer', 'policy_revision' => 'integer', 'issued_on' => 'date:Y-m-d', 'bound_at' => UtcInstant::class, 'expires_at' => UtcInstant::class, 'created_at' => UtcInstant::class];
    }
}
