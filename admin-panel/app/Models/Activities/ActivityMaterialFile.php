<?php

declare(strict_types=1);

namespace App\Models\Activities;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ActivityMaterialFile extends Model
{
    use BelongsToSchool;

    protected $table = 'activity_material_files';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['path', 'sha256', 'name'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'scanned_at' => UtcInstant::class, 'removed_at' => UtcInstant::class, 'revision' => 'integer', 'bytes' => 'integer'];
    }
}
