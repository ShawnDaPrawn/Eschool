<?php

declare(strict_types=1);

namespace App\Models\Gallery;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GalleryPhoto extends Model
{
    use BelongsToSchool;

    protected $table = 'gallery_photos';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['path', 'thumbnail_path', 'caption', 'request_hash', 'receipt'];

    protected function casts(): array
    {
        return ['caption' => 'encrypted', 'active' => 'boolean', 'captured_on' => 'date', 'scanned_at' => UtcInstant::class, 'erased_at' => UtcInstant::class, 'revision' => 'integer'];
    }
}
