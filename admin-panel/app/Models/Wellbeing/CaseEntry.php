<?php

declare(strict_types=1);

namespace App\Models\Wellbeing;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CaseEntry extends Model
{
    use BelongsToSchool;

    protected $table = 'support_case_entries';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'happened_at' => UtcInstant::class];
    }
}
