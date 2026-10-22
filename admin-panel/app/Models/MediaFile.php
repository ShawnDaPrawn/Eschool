<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use BelongsToSchool, HasFactory;

    public function getFileUrlAttribute($value)
    {
        return url(Storage::url($value));
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
