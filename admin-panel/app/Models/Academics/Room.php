<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Room extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'rooms';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['capacity' => 'integer', 'revision' => 'integer', 'active' => 'boolean'];
    }
}
