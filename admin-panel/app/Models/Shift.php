<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use BelongsToSchool, HasFactory;
    use SoftDeletes;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];
}
