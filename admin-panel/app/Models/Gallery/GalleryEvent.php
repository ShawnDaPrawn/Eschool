<?php

declare(strict_types=1);

namespace App\Models\Gallery;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GalleryEvent extends Model
{
    use BelongsToSchool;

    protected $table = 'gallery_events';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['path', 'thumbnail_path', 'caption', 'request_hash', 'receipt'];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }
}
