<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SchoolModule extends Model
{
    use Auditable, BelongsToSchool;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['in_package' => 'boolean', 'enabled' => 'boolean', 'read_only' => 'boolean', 'changed_at' => 'datetime'];
    }
}
