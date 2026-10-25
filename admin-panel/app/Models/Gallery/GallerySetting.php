<?php

declare(strict_types=1);

namespace App\Models\Gallery;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GallerySetting extends Model
{
    use BelongsToSchool;

    protected $table = 'gallery_settings';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['path', 'thumbnail_path', 'caption', 'request_hash', 'receipt'];

    protected function casts(): array
    {
        return ['guardian_required' => 'boolean', 'revision' => 'integer'];
    }
}
