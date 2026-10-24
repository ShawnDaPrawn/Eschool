<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ContentVersion extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected $hidden = ['metadata', 'markdown', 'review_reason'];

    protected function casts(): array
    {
        return ['review_reason' => 'encrypted', 'metadata' => 'encrypted:array', 'markdown' => 'encrypted', 'number' => 'integer', 'revision' => 'integer', 'duration_seconds' => 'integer', 'lease_until' => 'datetime', 'reviewed_at' => 'datetime', 'published_at' => 'datetime'];
    }

    public function item()
    {
        return $this->belongsTo(ContentItem::class, 'content_item_id');
    }

    public function source()
    {
        return $this->belongsTo(ContentSource::class, 'content_source_id');
    }

    protected static function booted(): void
    {
        static::updating(fn (ContentVersion $row) => abort_if($row->getRawOriginal('published_at') !== null, 409, __('Published content is immutable. Prepare a new draft.')));
        static::deleting(fn () => abort(409, __('Content version history must be retained.')));
    }
}
