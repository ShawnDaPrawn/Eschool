<?php

declare(strict_types=1);

namespace App\Models\Communication;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CommunicationRoleRule extends Model
{
    use BelongsToSchool;

    protected $table = 'communication_role_rules';

    protected $guarded = ['id', 'school_id'];

    protected $hidden = [];

    protected function casts(): array
    {
        return ['allow_school' => 'boolean', 'requires_approval' => 'boolean', 'revision' => 'integer'];
    }
}
