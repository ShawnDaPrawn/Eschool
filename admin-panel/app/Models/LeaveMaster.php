<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveMaster extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class);
    }

    public function leave()
    {
        return $this->hasMany(Leave::class);
    }
}
