<?php

declare(strict_types=1);

namespace App\Models\Activities\Chat;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GroupChatThread extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_chat_threads';

    protected $guarded = ['school_id'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['title' => 'encrypted', 'private' => 'boolean', 'closed' => 'boolean', 'revision' => 'integer', 'opened_at' => UtcInstant::class, 'last_message_at' => UtcInstant::class, 'redacted_at' => UtcInstant::class];
    }
}
