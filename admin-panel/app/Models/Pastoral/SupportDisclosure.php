<?php

declare(strict_types=1);

namespace App\Models\Pastoral;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SupportDisclosure extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['accommodations', 'reason', 'revoke_reason'];

    protected function casts(): array
    {
        return ['expires_on' => 'date', 'active' => 'boolean', 'revision' => 'integer', 'source_accommodation_revision' => 'integer', 'accommodations' => 'encrypted', 'reason' => 'encrypted', 'revoke_reason' => 'encrypted'];
    }
}
