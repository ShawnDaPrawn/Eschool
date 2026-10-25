<?php

declare(strict_types=1);

namespace App\Models\Discipline;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Routing metadata only; private facts remain behind the discipline authority. */
class DisciplineNoticeIntent extends Model
{
    use BelongsToSchool;

    protected $table = 'discipline_notice_intents';

    protected $guarded = ['school_id'];

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return ['record_revision' => 'integer', 'detention_revision' => 'integer'];
    }
}
