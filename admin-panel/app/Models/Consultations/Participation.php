<?php

declare(strict_types=1);

namespace App\Models\Consultations;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Participation extends Model
{
    use BelongsToSchool;

    protected $table = 'consultation_participation';

    protected $guarded = ['id', 'school_id'];

    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'user_id';
}
