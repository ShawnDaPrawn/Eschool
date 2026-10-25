<?php

declare(strict_types=1);

namespace App\Models\Gallery;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GalleryOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'gallery_operations';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['path', 'thumbnail_path', 'caption', 'request_hash', 'receipt'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
