<?php

declare(strict_types=1);

namespace App\Models\Assistance;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AssistanceOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'school_assistance_operations';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['response', 'request_hash'];

    protected function casts(): array
    {
        return ['response' => 'encrypted:array'];
    }
}
