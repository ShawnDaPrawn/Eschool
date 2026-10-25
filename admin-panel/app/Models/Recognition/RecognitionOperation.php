<?php

declare(strict_types=1);

namespace App\Models\Recognition;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class RecognitionOperation extends Model
{
    use BelongsToSchool;

    protected $table = 'recognition_operations';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => abort(409, 'Recognition history is immutable.'));
        static::deleting(fn () => abort(409, 'Recognition history must be retained.'));
    }
}
