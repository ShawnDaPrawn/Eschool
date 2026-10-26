<?php

declare(strict_types=1);

namespace App\Models\Activities\Chat;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GroupChatRead extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_chat_reads';

    protected $guarded = ['school_id'];
}
