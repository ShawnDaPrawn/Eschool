<?php

declare(strict_types=1);

namespace App\Models\Platform;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ProfileFieldValue extends Model
{
    use Auditable, BelongsToSchool;

    protected $guarded = ['id'];

    protected $hidden = ['value'];

    protected function casts(): array
    {
        return [
            'encrypted' => 'boolean',
        ];
    }
}
