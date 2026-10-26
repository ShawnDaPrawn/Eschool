<?php

declare(strict_types=1);

namespace App\Models\Assistance;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AssistanceNoticeIntent extends Model
{
    use BelongsToSchool;

    protected $table = 'school_assistance_notice_intents';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['recipient_user_id', 'thread_id', 'message_id', 'source_key'];

    protected function casts(): array
    {
        return ['recipient_user_id' => 'integer', 'notification_id' => 'integer', 'queue_only' => 'boolean', 'expires_at' => UtcInstant::class];
    }
}
