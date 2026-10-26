<?php

declare(strict_types=1);

namespace App\Models\Assistance;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class AssistanceTurn extends Model
{
    use BelongsToSchool;

    protected $table = 'school_assistance_turns';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['source_request', 'source_manifest', 'approved_input', 'citations', 'response'];

    protected function casts(): array
    {
        return ['office' => 'boolean', 'source_request' => 'encrypted:array', 'source_manifest' => 'encrypted:array',
            'approved_input' => 'encrypted', 'citations' => 'encrypted:array', 'response' => 'encrypted:array',
            'approved_at' => UtcInstant::class, 'expires_at' => UtcInstant::class, 'purged_at' => UtcInstant::class];
    }

    protected static function booted(): void
    {
        static::updating(function (self $row): void {
            $allowed = ['state', 'approved_at', 'response', 'updated_at'];
            if ($row->purged_at !== null) {
                $allowed = [...$allowed, 'purged_at', 'source_request', 'source_manifest', 'approved_input', 'citations'];
                foreach (['source_request', 'source_manifest', 'approved_input', 'citations', 'response'] as $field) {
                    if ($row->$field !== null) {
                        throw new LogicException('Erase all private assistance turn fields together.');
                    }
                }
            }
            if (array_diff(array_keys($row->getDirty()), $allowed) !== []) {
                throw new LogicException('Prepared assistance sources and purpose are immutable.');
            }
        });
        static::deleting(fn () => throw new LogicException('Retain minimal assistance turn history.'));
    }
}
