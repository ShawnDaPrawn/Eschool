<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    use HasFactory;

    public function members()
    {
        return $this->hasMany(ChatMember::class, 'chat_room_id');
    }

    public function messages()
    {
        return $this->morphMany(ChatMessage::class, 'modal');
    }
}
