<?php

declare(strict_types=1);

namespace App\Models\Assistance;

use App\Casts\UtcInstant;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class KnowledgeDocument extends Model
{
    use BelongsToSchool;

    protected $table = 'school_knowledge_documents';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = ['school_id'];

    protected $hidden = ['markdown', 'content_hash'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'identifiers_reviewed' => 'boolean', 'markdown' => 'encrypted', 'published_at' => UtcInstant::class];
    }

    protected static function booted(): void
    {
        static::updating(function (self $row): void {
            if (array_diff(array_keys($row->getDirty()), ['state', 'updated_at']) !== []) {
                throw new LogicException('Publish a new immutable knowledge document version.');
            }
        });
    }
}
