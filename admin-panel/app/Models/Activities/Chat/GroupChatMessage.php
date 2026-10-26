<?php

declare(strict_types=1);

namespace App\Models\Activities\Chat;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GroupChatMessage extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_chat_messages';

    protected $guarded = ['school_id'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'removal_reason' => 'encrypted', 'revision' => 'integer', 'sent_at' => UtcInstant::class, 'removed_at' => UtcInstant::class];
    }
}
