<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveDetail extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $fillable = ['id', 'leave_id', 'date', 'type'];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function getLeaveDateAttribute()
    {
        return date('d - M', strtotime($this->date));
    }
}
