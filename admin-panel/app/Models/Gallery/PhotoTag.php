<?php

declare(strict_types=1);

namespace App\Models\Gallery;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PhotoTag extends Model
{
    use BelongsToSchool;

    protected $table = 'photo_tags';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['path', 'thumbnail_path', 'caption', 'request_hash', 'receipt'];

    protected function casts(): array
    {
        return ['approved_at' => UtcInstant::class, 'retained_on_transfer' => 'boolean', 'revision' => 'integer'];
    }
}
