<?php

declare(strict_types=1);

namespace App\Models\EarlyYears;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/**
 * A development area an early years observation is tagged to (Action Plan
 * 4.3). Each school keeps its own list; areas are switched off, never deleted.
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $position
 * @property bool $active
 * @property int $revision
 */
class DevelopmentArea extends Model
{
    use BelongsToSchool;

    protected $table = 'early_years_development_areas';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'position' => 'integer', 'revision' => 'integer'];
    }
}
