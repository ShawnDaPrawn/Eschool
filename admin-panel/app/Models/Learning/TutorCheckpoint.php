<?php

declare(strict_types=1);

namespace App\Models\Learning;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TutorCheckpoint extends Model
{
    use BelongsToSchool;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['answer', 'answer_hash', 'request_hash', 'definition_hash'];

    protected function casts(): array
    {
        return ['answer' => 'encrypted', 'correct' => 'boolean', 'answered_at' => UtcInstant::class];
    }

    protected static function booted(): void
    {
        static::updating(function (self $row) {
            abort_unless($row->getRawOriginal('answered_at') === null
                && array_diff(array_keys($row->getDirty()), ['answer_operation_id', 'answer_hash', 'answer', 'correct', 'answered_at', 'updated_at']) === [], 409, 'Verified checkpoint attempts are immutable.');
        });
        static::deleting(fn () => abort(409, 'Retain verified checkpoint evidence.'));
    }
}
