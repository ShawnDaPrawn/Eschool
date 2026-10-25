<?php

declare(strict_types=1);

namespace App\Models\Gallery;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class GalleryTransferChoice extends Model
{
    use BelongsToSchool;

    protected $table = 'gallery_transfer_choices';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['selection', 'fingerprint'];

    protected function casts(): array
    {
        return ['selection' => 'encrypted:array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'A signed photo transfer choice is immutable.'));
        static::deleting(fn () => abort(409, 'Retain signed photo transfer choices.'));
    }
}
