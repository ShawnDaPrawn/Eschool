<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\ChatFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ChatMessage extends Model
{
    use HasFactory;

    /** The apps read attachments as `files` (URLs), e.g. in a chat's last-message preview. */
    protected $appends = ['files'];

    public function modal()
    {
        return $this->morphTo();
    }

    public function file()
    {
        return $this->hasMany(ChatFile::class, 'message_id','id');
    }

    public function getFilesAttribute(): array
    {
        if (! $this->relationLoaded('file')) {
            return [];
        }

        return $this->file->map(fn ($file) => asset('storage/' . $file->file_name))->values()->all();
    }

    public function getFileUrlAttribute($value)
    {
        return url(Storage::url($value));
    }
}
