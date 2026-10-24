<?php

declare(strict_types=1);

namespace App\Models\Communication;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class MessageRecipient extends Model
{
    use BelongsToSchool;

    protected $table = 'message_recipients';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['access_reason'];

    protected function casts(): array
    {
        return ['access_reason' => 'encrypted:array', 'processed_at' => 'datetime', 'notification_queued' => 'boolean'];
    }
}
