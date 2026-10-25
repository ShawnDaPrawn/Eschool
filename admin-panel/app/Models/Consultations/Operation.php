<?php

declare(strict_types=1);

namespace App\Models\Consultations;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Operation extends Model
{
    use BelongsToSchool;

    protected $table = 'consultation_operations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
