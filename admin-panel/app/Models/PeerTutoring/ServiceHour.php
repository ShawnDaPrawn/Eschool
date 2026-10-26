<?php

declare(strict_types=1);

namespace App\Models\PeerTutoring;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ServiceHour extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['minutes' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Service history is immutable.'));
        static::deleting(fn () => abort(409, 'Service history must be retained.'));
    }
}
