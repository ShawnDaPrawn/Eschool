<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ProfileFile extends Model
{
    use Auditable, BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $hidden = ['name', 'path'];

    protected function casts(): array
    {
        return ['name' => 'encrypted', 'size' => 'integer'];
    }
}
