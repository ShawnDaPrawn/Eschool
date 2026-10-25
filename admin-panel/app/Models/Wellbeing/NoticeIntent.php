<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class NoticeIntent extends Model
{
    use BelongsToSchool;

    protected $table = 'wellbeing_notice_intents';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['event_id', 'recipient_user_id', 'incident_report_id', 'support_case_id'];

    protected function casts(): array
    {
        return ['event_id' => 'integer', 'recipient_user_id' => 'integer', 'incident_report_id' => 'integer', 'support_case_id' => 'integer', 'notification_id' => 'integer', 'queued_at' => 'immutable_datetime', 'read_at' => 'immutable_datetime'];
    }
}
