<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class HousePoint extends Model
{
    use BelongsToSchool;

    protected $table = 'house_points';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['on_date' => 'date', 'points' => 'integer', 'public' => 'boolean', 'reason' => 'encrypted'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Recognition history is immutable.'));
        static::deleting(fn () => abort(409, 'Recognition history must be retained.'));
    }

    protected $hidden = ['reason'];
}
