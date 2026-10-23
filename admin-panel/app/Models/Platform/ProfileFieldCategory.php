<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfileFieldCategory extends Model
{
    use Auditable, BelongsToSchool, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }
}
