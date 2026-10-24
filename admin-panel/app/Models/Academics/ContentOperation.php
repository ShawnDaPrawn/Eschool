<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ContentOperation extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['result', 'request_hash'];

    protected function casts(): array
    {
        return ['result' => 'encrypted:array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, __('Content operation receipts are immutable.')));
        static::deleting(fn () => abort(409, __('Content operation receipts must be retained.')));
    }
}
