<?php

declare(strict_types=1);

namespace App\Models\Growth;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class LearnerNoteOperation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['request_hash', 'receipt'];

    protected function casts(): array
    {
        return ['receipt' => 'encrypted:array'];
    }
}
