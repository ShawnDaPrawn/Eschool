<?php

declare(strict_types=1);

namespace App\Models\Pathways;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AdmissionRequirementVersion extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'source_year' => 'integer', 'rules' => 'array', 'reviewed_on' => 'immutable_date', 'expires_on' => 'immutable_date'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('Published pathway source revisions are immutable.'));
        static::deleting(fn () => throw new \LogicException('Retain published pathway source revisions.'));
    }
}
