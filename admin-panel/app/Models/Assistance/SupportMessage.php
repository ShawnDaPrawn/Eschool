<?php

declare(strict_types=1);

namespace App\Models\Assistance;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class SupportMessage extends Model
{
    use BelongsToSchool;

    protected $table = 'school_support_messages';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['text', 'content_hash'];

    protected function casts(): array
    {
        return ['text' => 'encrypted', 'sent_at' => UtcInstant::class, 'purged_at' => UtcInstant::class];
    }

    protected static function booted(): void
    {
        static::updating(function (self $row): void {
            if ($row->getOriginal('purged_at') === null && $row->purged_at !== null && $row->text === null
                && array_diff(array_keys($row->getDirty()), ['text', 'purged_at', 'updated_at']) === []) {
                return;
            }
            throw new LogicException('Sent school-support messages are immutable.');
        });
        static::deleting(fn () => throw new LogicException('Retain the school-support message ledger.'));
    }
}
