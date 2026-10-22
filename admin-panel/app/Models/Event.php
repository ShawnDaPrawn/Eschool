<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    public function multipleEvent()
    {
        return $this->hasMany(MultipleEvent::class);
    }

    public function getImageAttribute($value)
    {
        if ($value) {
            return url(Storage::url($value));
        }

        return '';
    }
}
