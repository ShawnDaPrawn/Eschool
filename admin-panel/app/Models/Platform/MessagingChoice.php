<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A person's own choice of SMS and WhatsApp for one notice type at one school
 * (Action Plan 2.7). Only the person changes it; the school sees only that a
 * choice exists. Written only through UrgentMessaging::choose().
 *
 * @property string $type
 * @property bool $sms
 * @property bool $whatsapp
 */
class MessagingChoice extends Model
{
    use BelongsToSchool;

    protected $table = 'messaging_choices';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'sms' => 'boolean', 'whatsapp' => 'boolean'];
    }
}
