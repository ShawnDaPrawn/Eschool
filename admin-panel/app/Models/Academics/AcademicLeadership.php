<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\User;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AcademicLeadership extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'academic_leaderships';

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['revision' => 1];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'active' => 'boolean'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
