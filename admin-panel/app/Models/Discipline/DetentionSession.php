<?php

declare(strict_types=1);

namespace App\Models\Discipline;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class DetentionSession extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['location'];

    public $timestamps = true;

    protected function casts(): array
    {
        return ['location' => 'encrypted', 'on_date' => 'date', 'capacity' => 'integer', 'revision' => 'integer'];
    }
}
