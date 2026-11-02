<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * One school's SMS and WhatsApp opt-in for urgent notices. Written only
 * through App\Services\Platform\Notifications\UrgentMessaging.
 *
 * @property bool $sms_enabled
 * @property bool $whatsapp_enabled
 * @property list<string>|null $sms_types
 * @property list<string>|null $whatsapp_types
 * @property int $revision
 */
class SchoolMessagingSetting extends Model
{
    use BelongsToSchool;

    protected $table = 'school_messaging_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return [
            'sms_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'sms_types' => 'array',
            'whatsapp_types' => 'array',
            'whatsapp_consent_confirmed_at' => 'datetime',
            'changed_by' => 'integer',
            'revision' => 'integer',
        ];
    }
}
