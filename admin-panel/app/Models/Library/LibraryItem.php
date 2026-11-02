<?php

declare(strict_types=1);

namespace App\Models\Library;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A platform library item; its content lives in versions (Action Plan 3.3). */
class LibraryItem extends Model
{
    use PlatformLibraryRow;

    protected $table = 'library_items';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['library_topic_id' => 'integer', 'library_source_id' => 'integer', 'published_version_id' => 'integer', 'revision' => 'integer'];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(LibraryTopic::class, 'library_topic_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LibrarySource::class, 'library_source_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(LibraryItemVersion::class);
    }
}
