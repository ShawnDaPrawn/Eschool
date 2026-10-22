<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ChatFile extends Model
{
    use BelongsToSchool, HasFactory;

    public function message()
    {
        return $this->belongsTo(ChatMessage::class, 'message_id');
    }

    public function getFileUrlAttribute($value)
    {
        return url(Storage::url($value));
    }
}
