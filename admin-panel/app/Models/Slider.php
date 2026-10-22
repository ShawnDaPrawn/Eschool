<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Slider extends Model
{
    use BelongsToSchool, HasFactory;

    // Getter Attributes
    public function getImageAttribute($value)
    {
        return url(Storage::url($value));
    }
}
