<?php

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'user_id',
        'session_year_id',
        'date',
        'type',
        'remark',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sessionYear()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id', 'id');
    }
}
