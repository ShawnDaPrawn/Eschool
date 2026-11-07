<?php

declare(strict_types=1);

namespace App\Models\Reading;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** This school's reading badge switches (both off by default). School-owned. */
class ReadingSettings extends Model
{
    use BelongsToSchool;

    protected $table = 'reading_settings';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['finished_badge' => 'boolean', 'days_badge' => 'boolean', 'days_required' => 'integer', 'switched_on_by' => 'integer', 'revision' => 'integer'];
    }
}
